<?php

namespace APP\plugins\generic\fgvOpenRank\tests;

use APP\plugins\generic\fgvOpenRank\classes\cache\MostRead;
use APP\plugins\generic\fgvOpenRank\classes\cache\MostRecent;
use APP\plugins\generic\fgvOpenRank\classes\cache\RankingCache;
use APP\plugins\generic\fgvOpenRank\classes\cache\TrendingSubmissions;
use APP\plugins\generic\fgvOpenRank\classes\factory\RankingSubmission;
use APP\plugins\generic\fgvOpenRank\classes\HookCallback;
use APP\plugins\generic\fgvOpenRank\FgvOpenRankPlugin;
use APP\core\Request;
use APP\publication\Publication;
use APP\submission\Repository as SubmissionRepository;
use APP\submission\Submission;
use PHPUnit\Framework\Attributes\Test;
use PKP\core\Dispatcher;
use PKP\tests\PKPTestCase;

class UnpublishedSubmissionsTest extends PKPTestCase
{
    private const CONTEXT_ID = 1;
    private const OTHER_CONTEXT_ID = 2;
    private const DOI = '10.4322/2179-7560.2024.001';

    protected function getMockedContainerKeys(): array
    {
        return [...parent::getMockedContainerKeys(), SubmissionRepository::class];
    }

    private function bindRepositoryReturning(Submission $submission): void
    {
        $submissionRepository = $this->createMock(SubmissionRepository::class);
        $submissionRepository->method('getByDoi')->willReturn($submission);
        app()->instance(SubmissionRepository::class, $submissionRepository);
    }

    private function buildSubmission(int $status, int $contextId = self::CONTEXT_ID): Submission
    {
        $submission = new Submission();
        $submission->setData('status', $status);
        $submission->setData('contextId', $contextId);
        return $submission;
    }

    #[Test]
    public function itShouldLeaveUnpublishedSubmissionsOutOfMostCited()
    {
        $this->bindRepositoryReturning($this->buildSubmission(Submission::STATUS_QUEUED));
        $rankingSubmission = new RankingSubmission(self::CONTEXT_ID, 'journal', null);

        $this->assertSame([], $rankingSubmission->getMostCited([self::DOI]));
    }

    #[Test]
    public function itShouldLeaveUnpublishedSubmissionsOutOfTrending()
    {
        $this->bindRepositoryReturning($this->buildSubmission(Submission::STATUS_SCHEDULED));
        $rankingSubmission = new RankingSubmission(self::CONTEXT_ID, 'journal', null);

        $this->assertSame([], $rankingSubmission->getTrending([self::DOI]));
    }

    private function buildPublishedSubmission(): Submission
    {
        $publication = $this->createMock(Publication::class);
        $publication->method('getData')->willReturnCallback(fn ($key) => [
            'title' => ['en' => 'Published article'],
            'datePublished' => '2026-01-02',
        ][$key] ?? null);
        $publication->method('getAuthorString')->willReturn('Alice Author');
        $publication->method('getLocalizedData')->willReturn(null);

        $submission = $this->createMock(Submission::class);
        $submission->method('getData')->willReturnCallback(
            fn ($key) => $key === 'status' ? Submission::STATUS_PUBLISHED : null
        );
        $submission->method('getCurrentPublication')->willReturn($publication);
        $submission->method('getBestId')->willReturn('7');
        $submission->method('getId')->willReturn(7);

        return $submission;
    }

    private function buildRequest(): Request
    {
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher->method('url')->willReturn('https://journal.test/article/view/7');
        $request = $this->createMock(Request::class);
        $request->method('getDispatcher')->willReturn($dispatcher);
        return $request;
    }

    #[Test]
    public function itShouldKeepPublishedSubmissionsResolvedByDoi()
    {
        $this->bindRepositoryReturning($this->buildPublishedSubmission());
        $rankingSubmission = new RankingSubmission(self::CONTEXT_ID, 'journal', $this->buildRequest());

        foreach ([$rankingSubmission->getMostCited([self::DOI]), $rankingSubmission->getTrending([self::DOI])] as $list) {
            $this->assertCount(1, $list);
            $this->assertSame(['en' => 'Published article'], $list[0]['title']);
            $this->assertSame('Alice Author', $list[0]['authorString']);
            $this->assertSame('https://journal.test/article/view/7', $list[0]['submissionUrl']);
        }
    }

    #[Test]
    public function itShouldForgetTheSubmissionListsOfTheContextWhenAPublicationIsUnpublished()
    {
        $listCaches = [MostRecent::CACHE_NAME, MostRead::CACHE_NAME, TrendingSubmissions::CACHE_NAME];
        foreach ($listCaches as $cacheName) {
            (new RankingCache($cacheName))->put(self::CONTEXT_ID, [['title' => 'Unpublished']]);
            (new RankingCache($cacheName))->put(self::OTHER_CONTEXT_ID, [['title' => 'Other journal']]);
        }
        (new RankingCache('most_cited_dois'))->put(self::CONTEXT_ID, [self::DOI]);

        $newPublication = null;
        $hookCallback = new HookCallback($this->createMock(FgvOpenRankPlugin::class));
        $hookCallback->forgetSubmissionListCaches(
            'Publication::unpublish',
            [&$newPublication, null, $this->buildSubmission(Submission::STATUS_QUEUED)]
        );

        foreach ($listCaches as $cacheName) {
            $this->assertNull((new RankingCache($cacheName))->get(self::CONTEXT_ID), $cacheName);
            $this->assertSame(
                [['title' => 'Other journal']],
                (new RankingCache($cacheName))->get(self::OTHER_CONTEXT_ID),
                $cacheName
            );
            (new RankingCache($cacheName))->forget(self::OTHER_CONTEXT_ID);
        }
        $this->assertSame([self::DOI], (new RankingCache('most_cited_dois'))->get(self::CONTEXT_ID));
        (new RankingCache('most_cited_dois'))->forget(self::CONTEXT_ID);
    }
}
