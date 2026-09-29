<?php

namespace APP\plugins\generic\rankingPlugin\classes\cache;

use APP\plugins\generic\rankingPlugin\classes\factory\RankingSubmission;

class MostRecent
{
    private RankingCache $cache;

    public function __construct(
        private int $contextId,
        private RankingSubmission $rankingSubmission
    ) {
        $this->cache = new RankingCache('most_recent_submissions');
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
