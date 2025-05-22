<?php

import('plugins.generic.rankingPlugin.classes.RankingSubmissionService');

class MostRecent
{
    public function getMostRecentSubmissions($context, $request)
    {
        $cacheManager = CacheManager::getManager();
        $cache = $cacheManager->getFileCache(
            $context->getId(),
            'most_recent_submissions',
            [$this, 'cacheDismiss']
        );

        $mostRecentSubmissions = & $cache->getContents();
        $currentCacheTime = time() - $cache->getCacheTime();

        if (
            ($mostRecentSubmissions && $mostRecentSubmissions != '[]')
            && $currentCacheTime < ONE_DAY_SECONDS
        ) {
            return $mostRecentSubmissions;
        }

        if ($currentCacheTime > ONE_DAY_SECONDS) {
            $cache->flush();
        }

        $rankingSubmissionService = new RankingSubmissionService($context->getId(), $context->getPath());
        $mostRecentSubmissions = $rankingSubmissionService->getMostRecent($request);

        $cache->setEntireCache($mostRecentSubmissions);
        $mostRecentSubmissions = & $cache->getContents();

        return $mostRecentSubmissions;
    }

    public function cacheDismiss()
    {
        return null;
    }
}
