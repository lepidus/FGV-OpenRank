<?php

class CacheOperator
{
    public function getCacheContents(int $contextId, string $cacheName): ?array
    {
        $cacheManager = CacheManager::getManager();
        $cache = $cacheManager->getFileCache(
            $contextId,
            $cacheName,
            [$this, 'cacheDismiss']
        );

        $contents = & $cache->getContents();

        if ($contents && $contents != '[]') {
            return $contents;
        }

        return null;
    }

    public function cacheDismiss()
    {
        return null;
    }
}
