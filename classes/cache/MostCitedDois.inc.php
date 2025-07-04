<?php

import('plugins.generic.rankingPlugin.classes.clients.Crossref');
import('plugins.generic.rankingPlugin.classes.cache.CacheOperator');

class MostCitedDois
{
    private $crossrefClient;
    private $cacheOperator;

    public function __construct()
    {
        $this->crossrefClient = new Crossref(Application::get()->getHttpClient());
        $this->cacheOperator = new CacheOperator();
    }

    public function getMostCitedSubmissionsDois(int $contextId, string $issn, int $limit): array
    {
        return $this->cacheOperator->getCacheContents($contextId, 'most_cited_dois');
    }

    public function refreshCache(int $contextId, string $issn, int $limit): array
    {
        $cacheManager = CacheManager::getManager();
        $cache = $cacheManager->getFileCache(
            $contextId,
            'most_cited_dois',
            [$this, 'cacheDismiss']
        );

        $cache->flush();

        $mostCitedSubmissions = $this->crossrefClient
            ->fetchMostCitedSubmissions($issn, $limit);
        $submissionDois = [];

        foreach ($mostCitedSubmissions['message']['items'] as $item) {
            $submissionDois[] = $item["DOI"];
        }

        $cache->setEntireCache($submissionDois);
        $mostCitedDois = & $cache->getContents();

        return $mostCitedDois;
    }

    public function cacheDismiss()
    {
        return null;
    }
}
