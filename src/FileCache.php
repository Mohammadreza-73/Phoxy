<?php

namespace CachingProxy;

class FileCache
{
    public function __construct()
    {
        $this->ensureCacheDir();
    }

    public function getCacheKey(string $url): string
    {
        return md5($url);
    }

    public function get(string $url): ?string
    {
        $fileName = $this->getCacheKey($url);
        $cacheFile = cache_path($fileName);

        if (file_exists($cacheFile)) {
            if (time() - filemtime($cacheFile) < config('cache', 'ttl')) {
                return file_get_contents($cacheFile);
            }

            unlink($cacheFile);
        }

        return null;
    }

    public function set(string $url, string $content): bool
    {
        $fileName = $this->getCacheKey($url);
        $cacheFile = cache_path($fileName);

        file_put_contents($cacheFile, $content);

        return true;
    }

    private function ensureCacheDir(): void
    {
        if (! file_exists(__DIR__.'/cache/')) {
            mkdir('cache', 0755, true);
        }
    }
}