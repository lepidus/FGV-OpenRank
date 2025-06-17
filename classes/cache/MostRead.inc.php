<?php

import('plugins.generic.rankingPlugin.classes.RankingSubmissionService');

class MostRead
{
    private $plugin;

    public function __construct($plugin = null)
    {
        $this->plugin = $plugin;
    }

    public function getMostReadSubmissions($context, $request, $limit = null)
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
        $mostReadSubmissions = & $cache->getContents();

        return $mostReadSubmissions;
    }

    public function cacheDismiss()
    {
        return null;
    }
}
