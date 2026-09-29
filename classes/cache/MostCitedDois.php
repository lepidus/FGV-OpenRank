<?php

namespace APP\plugins\generic\rankingPlugin\classes\cache;

use APP\core\Application;
use APP\plugins\generic\rankingPlugin\classes\clients\Crossref;

class MostCitedDois
{
    private $crossrefClient;
    private RankingCache $cache;

    public function __construct($crossrefClient = null)
    {
        $this->crossrefClient = $crossrefClient ?? new Crossref(Application::get()->getHttpClient());
        $this->cache = new RankingCache('most_cited_dois');
    }

    public function getMostCitedSubmissionsDois(int $contextId, string $issn, int $limit): array
    {
        return $this->cache->get($contextId)
            ?? $this->refreshCache($contextId, $issn, $limit);
    }

    public function refreshCache(int $contextId, string $issn, int $limit): array
    {
        $this->cache->forget($contextId);

        $mostCitedSubmissions = $this->crossrefClient->fetchMostCitedSubmissions($issn, $limit);
        $submissionDois = [];

        foreach ($mostCitedSubmissions['message']['items'] ?? [] as $item) {
            $submissionDois[] = $item['DOI'];
        }

        return $this->cache->put($contextId, $submissionDois);
    }
}
