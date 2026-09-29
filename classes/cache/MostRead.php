<?php

namespace APP\plugins\generic\rankingPlugin\classes\cache;

use APP\plugins\generic\rankingPlugin\classes\factory\RankingSubmission;

class MostRead
{
    private RankingCache $cache;

    public function __construct(
        private int $contextId,
        private RankingSubmission $rankingSubmission
    ) {
        $this->cache = new RankingCache('most_read_submissions');
    }

    public function getMostReadSubmissions(int $limit, int $mostReadDays): array
    {
        return $this->cache->get($this->contextId)
            ?? $this->refreshCache($limit, $mostReadDays);
    }

    public function refreshCache(int $limit, int $mostReadDays): array
    {
        return $this->cache->put(
            $this->contextId,
            $this->rankingSubmission->getMostRead($limit, $mostReadDays)
        );
    }
}
