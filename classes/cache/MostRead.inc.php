<?php

import('plugins.generic.rankingPlugin.classes.RankingSubmissionService');
import('plugins.generic.rankingPlugin.classes.cache.CacheOperator');

class MostRead
{
    private $plugin;
    private $cacheOperator;

    public function __construct($plugin = null)
    {
        $this->plugin = $plugin;
        $this->cacheOperator = new CacheOperator();
    }

    public function getMostReadSubmissions($context, $request, $limit = null)
    {
        return $this->cacheOperator->getCacheContents(
            $context->getId(),
            'most_read_submissions'
        );
    }

    public function refreshCache($context, $request, $limit = null)
    {
        $cacheManager = CacheManager::getManager();
        $cache = $cacheManager->getFileCache(
            $context->getId(),
            'most_read_submissions',
            [$this, 'cacheDismiss']
        );

        $mostReadDays = 120;
        if ($this->plugin) {
            $contextId = $context->getId();
            $mostReadDays = $this->plugin->getSetting(
                $contextId,
                "mostReadDays_mostRead"
            ) ?? 120;
        }

        $rankingSubmissionService = new RankingSubmissionService(
            $context->getId(),
            $context->getPath(),
            $limit
        );
        $mostReadSubmissions = $rankingSubmissionService->getMostRead($request, $mostReadDays);

        $cache->setEntireCache($mostReadSubmissions);

        return $mostReadSubmissions;
    }

    public function cacheDismiss()
    {
        return null;
    }
}
