<?php

namespace APP\plugins\generic\rankingPlugin\classes\cache;

use APP\plugins\generic\rankingPlugin\classes\RankingSubmissionService;

class MostRecent
{
    private RankingCache $cache;

    public function __construct()
    {
        $this->cache = new RankingCache('most_recent_submissions');
    }

    public function getMostRecentSubmissions($context, $request, $limit = null)
    {
        return $this->cache->get($context->getId())
            ?? $this->refreshCache($context, $request, $limit);
    }

    public function refreshCache($context, $request, $limit = null)
    {
        $rankingSubmissionService = new RankingSubmissionService(
            $context->getId(),
            $context->getPath(),
            $limit
        );

        return $this->cache->put(
            $context->getId(),
            $rankingSubmissionService->getMostRecent($request)
        );
    }
}
