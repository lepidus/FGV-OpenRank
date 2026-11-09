<?php

namespace APP\plugins\generic\fgvOpenRank\classes\cache;

use APP\plugins\generic\fgvOpenRank\classes\factory\RankingSubmission;

class MostRead
{
    public const CACHE_NAME = 'most_read_submissions';

    private RankingCache $cache;

    public function __construct(
        private int $contextId,
        private RankingSubmission $rankingSubmission
    ) {
        $this->cache = new RankingCache(self::CACHE_NAME);
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
