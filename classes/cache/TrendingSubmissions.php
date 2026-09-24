<?php

namespace APP\plugins\generic\rankingPlugin\classes\cache;

use APP\core\Application;
use APP\plugins\generic\rankingPlugin\classes\DataEncryption;
use APP\plugins\generic\rankingPlugin\classes\RankingSubmissionService;
use APP\plugins\generic\rankingPlugin\classes\settings\TrendingDois;
use PKP\plugins\PluginRegistry;

class TrendingSubmissions
{
    private const DEFAULT_LIMIT = 4;

    private $plugin;
    private $bestAltmetricsScoreDois;
    private $dataEncryption;
    private RankingCache $cache;

    public function __construct($plugin = null, $bestAltmetricsScoreDois = null, $dataEncryption = null)
    {
        $this->plugin = $plugin;
        $this->bestAltmetricsScoreDois = $bestAltmetricsScoreDois;
        $this->dataEncryption = $dataEncryption;
        $this->cache = new RankingCache('trending_submissions');
    }

    public function getTrendingSubmissions($contextId, $contextPath, $limit = null)
    {
        return $this->cache->get($contextId)
            ?? $this->refreshCache($contextId, $contextPath, $limit);
    }

    public function refreshCache($contextId, $contextPath, $limit = null)
    {
        $this->cache->forget($contextId);

        $effectiveLimit = $limit ?? self::DEFAULT_LIMIT;
        $apiKey = $this->getStoredApiKey($contextId);

        if ($apiKey !== null) {
            $trendingSubmissions = $this->refreshFromApi($contextId, $contextPath, $effectiveLimit, $apiKey);
        } else {
            $trendingSubmissions = $this->refreshFromManualDois($contextId, $contextPath, $effectiveLimit);
        }

        return $this->cache->put($contextId, $trendingSubmissions);
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
        try {
            return $this->getDataEncryption()->decryptString($encrypted);
        } catch (\Exception $e) {
            error_log(sprintf(
                '[rankingPlugin] Failed to decrypt Altmetric API key for context %s: %s',
                $contextId,
                $e->getMessage()
            ));
            return null;
        }
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
        $storedDois = $plugin->getSetting($contextId, TrendingDois::SETTING_NAME) ?: [];
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

    private function getDataEncryption(): DataEncryption
    {
        if ($this->dataEncryption === null) {
            $this->dataEncryption = new DataEncryption();
        }
        return $this->dataEncryption;
    }

    protected function getContextIssn($contextId): ?string
    {
        $context = app()->get('context')->get($contextId);
        if (!$context) {
            return null;
        }
        return $context->getData('printIssn') ?: $context->getData('onlineIssn');
    }

    protected function createRankingSubmissionService($contextId, $contextPath, $limit)
    {
        return new RankingSubmissionService($contextId, $contextPath, $limit);
    }
}
