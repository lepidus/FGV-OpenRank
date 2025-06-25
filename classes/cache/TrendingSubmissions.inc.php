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

        if ($trendingSubmissions && $trendingSubmissions != '[]') {
            return $trendingSubmissions;
        }

        return [];
    }

    public function refreshCache($contextId, $contextPath, $limit = null)
    {
        $cacheManager = CacheManager::getManager();
        $cache = $cacheManager->getFileCache(
            $contextId,
            'trending_submissions',
            [$this, 'cacheDismiss']
        );

        $cache->flush();

        $publishedSubmissions = Services::get('submission')->getMany([
            'contextId' => $contextId,
            'status' => STATUS_PUBLISHED
        ]);
        
        $request = Application::get()->getRequest();
        $contextDao = Application::getContextDAO();
        $context = $contextDao->getById($contextId);
        
        $rankingSubmissionService = new RankingSubmissionService(
            $contextId,
            $contextPath,
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
