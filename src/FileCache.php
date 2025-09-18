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

    public function get(string $url): ?array
    {
        $fileName = $this->getCacheKey($url);
        $cacheFile = cache_path($fileName);

        if (file_exists($cacheFile)) {
            $data = unserialize(file_get_contents($cacheFile));

            // Check cache time to live
            if (time() - filemtime($cacheFile) < config('cache', 'ttl')) {
                return $data['content'];
            }

            // Remove expired cache file
            unlink($cacheFile);
        }

        return null;
    }

    public function set(string $url, array $content): bool
    {
        $fileName = $this->getCacheKey($url);
        $cacheFile = cache_path($fileName);

        $data = [
            'timestamp' => time(),
            'content' => $content,
            'url' => $url,
        ];

        file_put_contents($cacheFile, serialize($data));

        return true;
    }

    private function ensureCacheDir(): void
    {
        if (! file_exists(__DIR__.'/cache/')) {
            mkdir('cache', 0755, true);
        }
    }
}
