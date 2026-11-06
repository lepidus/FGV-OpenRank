<?php

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.cache.CacheManager');
import('classes.submission.Submission');
import('classes.submission.SubmissionDAO');
import('plugins.generic.fgvOpenRank.classes.factory.RankingSubmission');
import('plugins.generic.fgvOpenRank.classes.HookCallback');
import('plugins.generic.fgvOpenRank.FgvOpenRankPlugin');

class UnpublishedSubmissionsTest extends PKPTestCase
{
    private const CONTEXT_ID = 1;
    private const OTHER_CONTEXT_ID = 2;
    private const DOI = '10.4322/2179-7560.2024.001';

    protected function getMockedDAOs()
    {
        return array_merge(parent::getMockedDAOs(), ['SubmissionDAO']);
    }

    private function registerSubmissionDaoReturning($submission)
    {
        $submissionDao = $this->createMock(SubmissionDAO::class);
        $submissionDao->method('getByPubId')->willReturn($submission);
        DAORegistry::registerDAO('SubmissionDAO', $submissionDao);
    }

    private function buildSubmission($status, $contextId = self::CONTEXT_ID)
    {
        $submission = new Submission();
        $submission->setData('status', $status);
        $submission->setData('contextId', $contextId);
        return $submission;
    }

    private function getCache($contextId, $cacheName)
    {
        return CacheManager::getManager()->getFileCache($contextId, $cacheName, [$this, 'cacheDismiss']);
    }

    public function cacheDismiss()
    {
        return null;
    }

    /**
     * @test
     */
    public function itShouldLeaveUnpublishedSubmissionsOutOfMostCited()
    {
        $this->registerSubmissionDaoReturning($this->buildSubmission(STATUS_QUEUED));

        $this->assertSame([], RankingSubmission::get('mostCited', [
            'request' => null,
            'mostCitedDois' => [self::DOI],
            'contextId' => self::CONTEXT_ID,
            'contextPath' => 'journal',
        ]));
    }

    /**
     * @test
     */
    public function itShouldLeaveUnpublishedSubmissionsOutOfTrending()
    {
        $this->registerSubmissionDaoReturning($this->buildSubmission(STATUS_SCHEDULED));

        $this->assertSame([], RankingSubmission::get('trending', [
            'request' => null,
            'bestScoreDois' => [self::DOI],
            'contextId' => self::CONTEXT_ID,
            'contextPath' => 'journal',
        ]));
    }

    /**
     * @test
     */
    public function itShouldForgetTheSubmissionListsOfTheContextWhenAPublicationIsUnpublished()
    {
        foreach (HookCallback::SUBMISSION_LIST_CACHES as $cacheName) {
            $this->getCache(self::CONTEXT_ID, $cacheName)->setEntireCache([['title' => 'Unpublished']]);
            $this->getCache(self::OTHER_CONTEXT_ID, $cacheName)->setEntireCache([['title' => 'Other journal']]);
        }
        $this->getCache(self::CONTEXT_ID, 'most_cited_dois')->setEntireCache([self::DOI]);

        $newPublication = null;
        $hookCallback = new HookCallback($this->createMock(FgvOpenRankPlugin::class));
        $hookCallback->forgetSubmissionListCaches(
            'Publication::unpublish',
            [&$newPublication, null, $this->buildSubmission(STATUS_QUEUED)]
        );

        foreach (HookCallback::SUBMISSION_LIST_CACHES as $cacheName) {
            $this->assertNull($this->getCache(self::CONTEXT_ID, $cacheName)->getContents(), $cacheName);
            $otherJournalCache = $this->getCache(self::OTHER_CONTEXT_ID, $cacheName);
            $this->assertSame([['title' => 'Other journal']], $otherJournalCache->getContents(), $cacheName);
            $otherJournalCache->flush();
        }
        $mostCitedCache = $this->getCache(self::CONTEXT_ID, 'most_cited_dois');
        $this->assertSame([self::DOI], $mostCitedCache->getContents());
        $mostCitedCache->flush();
    }
}
