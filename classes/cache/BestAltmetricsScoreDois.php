<?php

namespace APP\plugins\generic\rankingPlugin\classes\cache;

use APP\core\Application;
use APP\plugins\generic\rankingPlugin\classes\clients\Altmetrics;

class BestAltmetricsScoreDois
{
    private $altmetricsClient;
    private RankingCache $cache;

    public function __construct($altmetricsClient = null)
    {
        $this->altmetricsClient = $altmetricsClient ?? new Altmetrics(Application::get()->getHttpClient());
        $this->cache = new RankingCache('best_altmetrics_score_dois');
    }

    public function refreshCache(int $contextId, string $issn, int $limit, ?string $apiKey = null): array
    {
        $this->cache->forget($contextId);

        $bestScoreSubmissions = $this->altmetricsClient->fetchBestScoreSubmissions($issn, $limit, $apiKey);
        $submissionDois = [];

        foreach ($bestScoreSubmissions['results'] ?? [] as $item) {
            if (isset($item['doi'])) {
                $submissionDois[] = $item['doi'];
            }
        }

        return $this->cache->put($contextId, $submissionDois);
    }
}
