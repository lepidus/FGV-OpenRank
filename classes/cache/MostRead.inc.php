<?php

import('plugins.generic.rankingPlugin.classes.RankingSubmissionService');

class MostRead
{
    public function getMostReadSubmissions($context, $request)
    {
        $cacheManager = CacheManager::getManager();
        $cache = $cacheManager->getFileCache(
            $context->getId(),
            'most_read_submissions',
            [$this, 'cacheDismiss']
        );

        $mostReadSubmissions = & $cache->getContents();
        $currentCacheTime = time() - $cache->getCacheTime();

        if (
            ($mostReadSubmissions && $mostReadSubmissions != '[]')
            && $currentCacheTime < ONE_DAY_SECONDS
        ) {
            return $mostReadSubmissions;
        }

        if ($currentCacheTime > ONE_DAY_SECONDS) {
            $cache->flush();
        }

        $rankingSubmissionService = new RankingSubmissionService($context->getId(), $context->getPath());
        $mostReadSubmissions = $rankingSubmissionService->getMostRead($request);

        $cache->setEntireCache($mostReadSubmissions);
        $mostReadSubmissions = & $cache->getContents();

        return $mostReadSubmissions;
    }

    public function cacheDismiss()
    {
        return null;
    }
}
