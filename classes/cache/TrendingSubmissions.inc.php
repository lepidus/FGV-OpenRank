<?php

import('plugins.generic.rankingPlugin.classes.RankingSubmissionService');
import('plugins.generic.rankingPlugin.classes.cache.BestAltmetricsScoreDois');

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

        $contextDao = Application::getContextDAO();
        $context = $contextDao->getById($contextId);
        $issn = $context->getData('printIssn') ?: $context->getData('onlineIssn');

        if (empty($issn)) {
            return [];
        }

        $bestAltmetricsScoreDois = new BestAltmetricsScoreDois();
        $bestScoreDois = $bestAltmetricsScoreDois->refreshCache($contextId, $issn, $limit ?? 4);

        if (empty($bestScoreDois)) {
            return [];
        }

        $request = Application::get()->getRequest();
        $rankingSubmissionService = new RankingSubmissionService(
            $contextId,
            $contextPath,
            $limit
        );

        $trendingSubmissions = $rankingSubmissionService
            ->getBestAltmetricsScoreSubmissions($bestScoreDois, $request);

        $cache->setEntireCache($trendingSubmissions);
        $trendingSubmissions = & $cache->getContents();
        return $trendingSubmissions;
    }

    public function cacheDismiss()
    {
        return null;
    }
}
