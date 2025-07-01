<?php

import('plugins.generic.rankingPlugin.classes.clients.Altmetrics');

class BestAltmetricsScoreDois
{
    private $altmetricsClient;

    public function __construct()
    {
        $this->altmetricsClient = new Altmetrics(Application::get()->getHttpClient());
    }

    public function getBestAltmetricsScoreSubmissionsDois(int $contextId, string $issn, int $limit): array
    {
        $cacheManager = CacheManager::getManager();
        $cache = $cacheManager->getFileCache(
            $contextId,
            'best_altmetrics_score_dois',
            [$this, 'cacheDismiss']
        );

        $bestScoreDois = & $cache->getContents();

        if ($bestScoreDois && $bestScoreDois != '[]') {
            return $bestScoreDois;
        }

        return [];
    }

    public function refreshCache(int $contextId, string $issn, int $limit): array
    {
        $cacheManager = CacheManager::getManager();
        $cache = $cacheManager->getFileCache(
            $contextId,
            'best_altmetrics_score_dois',
            [$this, 'cacheDismiss']
        );

        $cache->flush();

        $bestScoreSubmissions = $this->altmetricsClient
            ->fetchBestScoreSubmissions($issn, $limit);
        $submissionDois = [];

        if (isset($bestScoreSubmissions['results'])) {
            foreach ($bestScoreSubmissions['results'] as $item) {
                if (isset($item['doi'])) {
                    $submissionDois[] = $item['doi'];
                }
            }
        }

        $cache->setEntireCache($submissionDois);
        $bestScoreDois = & $cache->getContents();

        return $bestScoreDois;
    }

    public function cacheDismiss()
    {
        return null;
    }
}
