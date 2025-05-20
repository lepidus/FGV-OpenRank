<?php

import('plugins.generic.rankingPlugin.classes.clients.Crossref');

class MostCitedDois
{
    private $crossrefClient;

    public function __construct()
    {
        $this->crossrefClient = new Crossref(Application::get()->getHttpClient());
    }

    public function getMostCitedSubmissionsDois(int $contextId, string $issn, int $limit): array
    {
        $cacheManager = CacheManager::getManager();
        $cache = $cacheManager->getFileCache(
            $contextId,
            'most_cited_dois',
            [$this, 'cacheDismiss']
        );

        $mostCitedDois = & $cache->getContents();
        $currentCacheTime = time() - $cache->getCacheTime();

        if (
            ($mostCitedDois && $mostCitedDois != '[]')
            && $currentCacheTime < ONE_DAY_SECONDS
        ) {
            return $mostCitedDois;
        }

        if ($currentCacheTime > ONE_DAY_SECONDS) {
            $cache->flush();
        }

        $mostCitedSubmissions = $this->crossrefClient->fetchMostCitedSubmissions($issn, $limit);
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
