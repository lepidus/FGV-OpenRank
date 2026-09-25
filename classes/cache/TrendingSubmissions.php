<?php

namespace APP\plugins\generic\rankingPlugin\classes\cache;

use APP\plugins\generic\rankingPlugin\classes\factory\RankingSubmission;
use APP\plugins\generic\rankingPlugin\classes\settings\AltmetricsApiKey;
use APP\plugins\generic\rankingPlugin\classes\settings\TrendingDois;

class TrendingSubmissions
{
    private $bestAltmetricsScoreDois;
    private RankingCache $cache;

    public function __construct(
        private int $contextId,
        private ?string $issn,
        private AltmetricsApiKey $altmetricsApiKey,
        private TrendingDois $trendingDois,
        private RankingSubmission $rankingSubmission,
        $bestAltmetricsScoreDois = null
    ) {
        $this->bestAltmetricsScoreDois = $bestAltmetricsScoreDois;
        $this->cache = new RankingCache('trending_submissions');
    }

    public function getTrendingSubmissions(int $limit): array
    {
        return $this->cache->get($this->contextId)
            ?? $this->refreshCache($limit);
    }

    public function refreshCache(int $limit): array
    {
        $this->cache->forget($this->contextId);

        $apiKey = $this->altmetricsApiKey->get();
        $dois = $apiKey !== null
            ? $this->getDoisFromApi($limit, $apiKey)
            : array_slice(array_values($this->trendingDois->getStored()), 0, $limit);

        $trendingSubmissions = empty($dois) ? [] : $this->rankingSubmission->getTrending($dois);

        return $this->cache->put($this->contextId, $trendingSubmissions);
    }

    private function getDoisFromApi(int $limit, string $apiKey): array
    {
        if (empty($this->issn)) {
            return [];
        }

        return $this->getBestAltmetricsScoreDois()->refreshCache($this->contextId, $this->issn, $limit, $apiKey);
    }

    private function getBestAltmetricsScoreDois(): BestAltmetricsScoreDois
    {
        return $this->bestAltmetricsScoreDois ??= new BestAltmetricsScoreDois();
    }
}
