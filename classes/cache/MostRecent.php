<?php

namespace APP\plugins\generic\fgvOpenRank\classes\cache;

use APP\plugins\generic\fgvOpenRank\classes\factory\RankingSubmission;

class MostRecent
{
    public const CACHE_NAME = 'most_recent_submissions';

    private RankingCache $cache;

    public function __construct(
        private int $contextId,
        private RankingSubmission $rankingSubmission
    ) {
        $this->cache = new RankingCache(self::CACHE_NAME);
    }

    public function getMostRecentSubmissions(int $limit): array
    {
        return $this->cache->get($this->contextId)
            ?? $this->refreshCache($limit);
    }

    public function refreshCache(int $limit): array
    {
        return $this->cache->put(
            $this->contextId,
            $this->rankingSubmission->getMostRecent($limit)
        );
    }
}
