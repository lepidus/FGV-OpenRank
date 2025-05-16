<?php

import('plugins.generic.rankingPlugin.classes.clients.Altmetrics');

define('ONE_DAY_SECONDS', 60 * 60 * 24);

class TrendingSubmissions
{
    private $altmetricsClient;
    private $application;
    private const LIMIT = 4;

    public function __construct()
    {
        $this->application = Application::get();
        $this->altmetricsClient = new Altmetrics($this->application->getHttpClient());
    }

    public function getTrendingSubmissions($contextId, $contextPath)
    {
        $cacheManager = CacheManager::getManager();
        $cache = $cacheManager->getFileCache(
            $contextId,
            'trending_submissions',
            [$this, 'cacheDismiss']
        );

        $trendingSubmissions = & $cache->getContents();
        $currentCacheTime = time() - $cache->getCacheTime();

        if (
            ($trendingSubmissions && $trendingSubmissions != '[]')
            && $currentCacheTime < ONE_DAY_SECONDS
        ) {
            return $trendingSubmissions;
        }

        if ($currentCacheTime > ONE_DAY_SECONDS) {
            $cache->flush();
        }

        $submissions = Services::get('submission')->getMany([
            'contextId' => $contextId,
            'status' => STATUS_PUBLISHED
        ]);

        foreach ($submissions as $submission) {
            $publication = $submission->getCurrentPublication();
            if (!empty($publication->getData('pub-id::doi'))) {
                $submissionDoi = $publication->getData('pub-id::doi');
                $submissionMetrics = [];
                try {
                    $submissionMetrics = $this->altmetricsClient->fetchAltmetrics($submissionDoi);
                } catch (Exception $e) {
                    error_log($e->getMessage());
                }
                if (isset($submissionMetrics["score"])) {
                    $score = (float) $submissionMetrics["score"];
                    Services::get('submission')->edit($submission, ['altmetricsScore' => $score], $this->application->getRequest());
                }

            }
        }

        $submissionDao = DAORegistry::getDAO('SubmissionDAO');

        $params = [
            'altmetricsScore',
            STATUS_PUBLISHED,
            $contextId
        ];
        $range = new \DBResultRange(self::LIMIT);

        $sql = 'SELECT s.* FROM submissions s LEFT JOIN submission_settings ssas ON (s.submission_id = ssas.submission_id AND ssas.setting_name = ?) WHERE s.status = ? AND s.context_id = ? AND ssas.setting_value IS NOT NULL GROUP BY s.submission_id ORDER BY ssas.setting_value DESC';
        $result = $submissionDao->retrieveRange(
            $sql,
            $params,
            $range
        );
        $queryResults = new DAOResultFactory($result, $submissionDao, '_fromRow', [], $sql, $params, $range);
        $submissions = $queryResults->toAssociativeArray();

        $trendingSubmissions = [];
        $request = $this->application->getRequest();
        foreach ($submissions as $submission) {
            $submissionUrl = $request->getDispatcher()->url($request, ROUTE_PAGE, $contextPath, 'article', 'view', $submission->getBestId());
            $trendingSubmissionData = [
                'submissionUrl' => $submissionUrl,
                'title' => $submission->getLocalizedTitle(),
                'authorString' => $submission->getAuthorString(),
                'datePublishedLabel' => __("plugins.generic.rankingPlugin.tabs.content.publishedDate", ['datePublished' => strftime('%b %e, %Y', strtotime($submission->getDatePublished()))]),
                'altmetricsScore' => $submission->getData('altmetricsScore'),
                'doi' => $submission->getCurrentPublication()->getData('pub-id::doi'),
            ];
            $publication = $submission->getCurrentPublication();
            $issueDao = DAORegistry::getDAO('IssueDAO');
            $issue = $issueDao->getBySubmissionId($submission->getId());

            if ($publication->getLocalizedData('coverImage') || ($issue && $issue->getLocalizedCoverImage())) {
                $trendingSubmissionData['coverImage'] = $publication->getLocalizedData('coverImage') ?: $issue->getLocalizedCoverImage();
                $trendingSubmissionData['coverImage']['coverImageUrl'] = $publication->getLocalizedCoverImageUrl($contextId);
            }
            $trendingSubmissions[] = $trendingSubmissionData;
        }

        $cache->setEntireCache($trendingSubmissions);
        $trendingSubmissions = & $cache->getContents();
        return $trendingSubmissions;
    }

    public function cacheDismiss()
    {
        return null;
    }
}
