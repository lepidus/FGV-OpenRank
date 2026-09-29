<?php

namespace APP\plugins\generic\rankingPlugin\classes\cache;

use Illuminate\Support\Facades\Cache;

class RankingCache
{
    public function __construct(
        private string $name
    ) {
    }

    public function get(int $contextId): ?array
    {
        $contents = Cache::get($this->getKey($contextId));

        return is_array($contents) && !empty($contents) ? $contents : null;
    }

    public function put(int $contextId, array $contents): array
    {
        Cache::forever($this->getKey($contextId), $contents);

        return $contents;
    }

    public function forget(int $contextId): void
    {
        Cache::forget($this->getKey($contextId));
    }

    private function getKey(int $contextId): string
    {
        return "rankingPlugin-{$this->name}-{$contextId}";
    }
}
