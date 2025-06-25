<?php

import('plugins.generic.rankingPlugin.classes.RankingSubmissionService');

class MostRecent
{
    public function getMostRecentSubmissions($context, $request, $limit = null)
    {
        $cacheManager = CacheManager::getManager();
        $cache = $cacheManager->getFileCache(
            $context->getId(),
            'most_recent_submissions',
            [$this, 'cacheDismiss']
        );

        $mostRecentSubmissions = & $cache->getContents();

        if ($mostRecentSubmissions && $mostRecentSubmissions != '[]') {
            return $mostRecentSubmissions;
        }

        return [];
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
