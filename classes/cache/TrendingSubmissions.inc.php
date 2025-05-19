<?php

import('plugins.generic.rankingPlugin.classes.clients.Altmetrics');
import('plugins.generic.rankingPlugin.classes.RankingSubmissionService');

class TrendingSubmissions
{
    private $altmetricsClient;
    private $application;
    private const LIMIT = 4;

    public function __construct()
    {
        $this->application = Application::get();
        $this->altmetricsClient = new Altmetrics($this->application->getHttpClient());
    }

    public function getTrendingSubmissions($contextId, $contextPath)
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
        $request = $this->application->getRequest();
        $context = $request->getContext();
        $rankingSubmissionService = new RankingSubmissionService($context->getId(), $context->getPath());
        $rankingSubmissionService->updatePublishedSubmissionsAltmetricsScore($publishedSubmissions, $this->altmetricsClient, $request);

        $trendingSubmissions = $rankingSubmissionService->retrieveTrendingSubmissions($request);

        $cache->setEntireCache($trendingSubmissions);
        $trendingSubmissions = & $cache->getContents();
        return $trendingSubmissions;
    }

    public function cacheDismiss()
    {
        return null;
    }
}
