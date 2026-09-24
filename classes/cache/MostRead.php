<?php

namespace APP\plugins\generic\rankingPlugin\classes\cache;

use APP\plugins\generic\rankingPlugin\classes\RankingSubmissionService;
use APP\plugins\generic\rankingPlugin\classes\RankingTabs;

class MostRead
{
    private RankingCache $cache;

    public function __construct(
        private $plugin = null
    ) {
        $this->cache = new RankingCache('most_read_submissions');
    }

    public function getMostReadSubmissions($context, $request, $limit = null)
    {
        return $this->cache->get($context->getId())
            ?? $this->refreshCache($context, $request, $limit);
    }

    public function refreshCache($context, $request, $limit = null)
    {
        $mostReadDays = RankingTabs::DEFAULT_MOST_READ_DAYS;
        if ($this->plugin) {
            $mostReadDays = (new RankingTabs($this->plugin, $context->getId()))->getMostReadDays();
        }

        $rankingSubmissionService = new RankingSubmissionService(
            $context->getId(),
            $context->getPath(),
            $limit
        );

        return $this->cache->put(
            $context->getId(),
            $rankingSubmissionService->getMostRead($request, $mostReadDays)
        );
    }
}
