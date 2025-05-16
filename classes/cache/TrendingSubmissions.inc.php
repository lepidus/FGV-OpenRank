<?php

import('plugins.generic.rankingPlugin.classes.clients.Altmetrics');

define('ONE_DAY_SECONDS', 60 * 60 * 24);

class TrendingSubmissions
{
    private $altmetricsClient;
    private $application;

    public function __construct()
    {
        $this->application = Application::get();
        $this->altmetricsClient = new Altmetrics($this->application->getHttpClient());
    }

    public function getTrendingSubmissions($contextId)
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
        $range = new \DBResultRange(1);

        $sql = 'SELECT s.* FROM submissions s LEFT JOIN submission_settings ssas ON (s.submission_id = ssas.submission_id AND ssas.setting_name = ?) WHERE s.status = ? AND s.context_id = ? AND ssas.setting_value IS NOT NULL GROUP BY s.submission_id ORDER BY ssas.setting_value DESC';
        $result = $submissionDao->retrieveRange(
            $sql,
            $params,
            $range
        );
        $queryResults = new DAOResultFactory($result, $submissionDao, '_fromRow', [], $sql, $params, $range);
        $submissions = $queryResults->toAssociativeArray();

        $trendingSubmissions = [];

        foreach ($submissions as $submission) {
            $trendingSubmissions[] = $submission->getId();
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
