<?php

import('plugins.generic.rankingPlugin.classes.RankingSubmissionService');

class TrendingSubmissions
{
    public function getTrendingSubmissions($contextId, $contextPath, $limit = null)
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

        $publishedSubmissions = Services::get('submission')->getMany([
            'contextId' => $contextId,
            'status' => STATUS_PUBLISHED
        ]);
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $rankingSubmissionService = new RankingSubmissionService(
            $context->getId(),
            $context->getPath(),
            $limit
        );
        $rankingSubmissionService->updatePublishedSubmissionsAltmetricsScore(
            $publishedSubmissions,
            $request
        );

        $trendingSubmissions = $rankingSubmissionService
            ->retrieveTrendingSubmissions($request);

        $cache->setEntireCache($trendingSubmissions);
        $trendingSubmissions = & $cache->getContents();
        return $trendingSubmissions;
    }

    public function cacheDismiss()
    {
        return null;
    }
}
