<?php

import('plugins.generic.rankingPlugin.classes.RankingSubmissionService');
import('plugins.generic.rankingPlugin.classes.cache.BestAltmetricsScoreDois');
import('plugins.generic.rankingPlugin.lib.APIKeyEncryption.APIKeyEncryption');

class TrendingSubmissions
{
    private const DEFAULT_LIMIT = 4;

    private $plugin;
    private $bestAltmetricsScoreDois;
    private $apiKeyEncryption;

    public function __construct($plugin = null, $bestAltmetricsScoreDois = null, $apiKeyEncryption = null)
    {
        $this->plugin = $plugin;
        $this->bestAltmetricsScoreDois = $bestAltmetricsScoreDois;
        $this->apiKeyEncryption = $apiKeyEncryption;
    }

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

        return $this->refreshCache($contextId, $contextPath, $limit);
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

        $effectiveLimit = $limit ?? self::DEFAULT_LIMIT;
        $apiKey = $this->getStoredApiKey($contextId);

        if ($apiKey !== null) {
            $trendingSubmissions = $this->refreshFromApi($contextId, $contextPath, $effectiveLimit, $apiKey);
        } else {
            $trendingSubmissions = $this->refreshFromManualDois($contextId, $contextPath, $effectiveLimit);
        }

        $cache->setEntireCache($trendingSubmissions);
        $trendingSubmissions = & $cache->getContents();
        return $trendingSubmissions;
    }

    private function getStoredApiKey($contextId): ?string
    {
        $plugin = $this->getPlugin();
        if ($plugin === null) {
            return null;
        }
        $encrypted = $plugin->getSetting($contextId, 'altmetricsApiKey_trending');
        if (empty($encrypted)) {
            return null;
        }
        return $this->getApiKeyEncryption()->decryptString($encrypted);
    }

    private function refreshFromApi($contextId, $contextPath, $limit, $apiKey): array
    {
        $issn = $this->getContextIssn($contextId);
        if (empty($issn)) {
            return [];
        }

        $bestScoreDois = $this->getBestAltmetricsScoreDois()
            ->refreshCache($contextId, $issn, $limit, $apiKey);

        if (empty($bestScoreDois)) {
            return [];
        }

        return $this->resolveSubmissionsFromDois($bestScoreDois, $contextId, $contextPath, $limit);
    }

    private function refreshFromManualDois($contextId, $contextPath, $limit): array
    {
        $plugin = $this->getPlugin();
        if ($plugin === null) {
            return [];
        }
        $storedDois = $plugin->getSetting($contextId, 'trendingDois_trending') ?: [];
        $dois = array_slice(array_values($storedDois), 0, $limit);

        if (empty($dois)) {
            return [];
        }

        return $this->resolveSubmissionsFromDois($dois, $contextId, $contextPath, $limit);
    }

    private function resolveSubmissionsFromDois(array $dois, $contextId, $contextPath, $limit): array
    {
        $request = Application::get()->getRequest();
        $service = $this->createRankingSubmissionService($contextId, $contextPath, $limit);
        return $service->getBestAltmetricsScoreSubmissions($dois, $request);
    }

    private function getPlugin()
    {
        if ($this->plugin === null) {
            $this->plugin = PluginRegistry::getPlugin('generic', 'rankingplugin');
        }
        return $this->plugin;
    }

    private function getBestAltmetricsScoreDois(): BestAltmetricsScoreDois
    {
        if ($this->bestAltmetricsScoreDois === null) {
            $this->bestAltmetricsScoreDois = new BestAltmetricsScoreDois();
        }
        return $this->bestAltmetricsScoreDois;
    }

    private function getApiKeyEncryption(): APIKeyEncryption
    {
        if ($this->apiKeyEncryption === null) {
            $this->apiKeyEncryption = new APIKeyEncryption();
        }
        return $this->apiKeyEncryption;
    }

    protected function getContextIssn($contextId): ?string
    {
        $contextDao = Application::getContextDAO();
        $context = $contextDao->getById($contextId);
        if (!$context) {
            return null;
        }
        return $context->getData('printIssn') ?: $context->getData('onlineIssn');
    }

    protected function createRankingSubmissionService($contextId, $contextPath, $limit)
    {
        return new RankingSubmissionService($contextId, $contextPath, $limit);
    }

    public function cacheDismiss()
    {
        return null;
    }
}
