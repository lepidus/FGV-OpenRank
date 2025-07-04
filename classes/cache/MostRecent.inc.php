<?php

import('plugins.generic.rankingPlugin.classes.RankingSubmissionService');
import('plugins.generic.rankingPlugin.classes.cache.CacheOperator');

class MostRecent
{
    private $cacheOperator;

    public function __construct()
    {
        $this->cacheOperator = new CacheOperator();
    }

    public function getMostRecentSubmissions($context, $request, $limit = null)
    {
        return $this->cacheOperator->getCacheContents($context->getId(), 'most_recent_submissions');
    }

    public function refreshCache($context, $request, $limit = null)
    {
        $cacheManager = CacheManager::getManager();
        $cache = $cacheManager->getFileCache(
            $context->getId(),
            'most_recent_submissions',
            [$this, 'cacheDismiss']
        );

        $rankingSubmissionService = new RankingSubmissionService(
            $context->getId(),
            $context->getPath(),
            $limit
        );
        $mostRecentSubmissions = $rankingSubmissionService->getMostRecent($request);
        $cache->setEntireCache($mostRecentSubmissions);

        return $mostRecentSubmissions;
    }

    public function cacheDismiss()
    {
        return null;
    }
}
