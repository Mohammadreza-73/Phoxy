<?php

namespace Phoxy;

use RuntimeException;

class FileCache
{
    private string $cacheDir;

    public function __construct()
    {
        $this->cacheDir = dirname(__DIR__).'/cache/';
        $this->ensureCacheDir();
    }

    public function getCacheKey(string $url): string
    {
        return md5($url);
    }

    /**
     * @return array<mixed>|null
     */
    public function get(string $url): ?array
    {
        $fileName = $this->getCacheKey($url);
        $cacheFile = cache_path($fileName);

        if (file_exists($cacheFile) && is_readable($cacheFile)) {
            $cacheFileContent = file_get_contents($cacheFile);

            if ($cacheFileContent === false) {
                throw new RuntimeException("Failed to read Cache file `{$cacheFile}`");
            }

            $data = unserialize($cacheFileContent);

            // Check cache time to live
            if (time() - filemtime($cacheFile) < config('cache', 'ttl')) {
                return $data['content'];
            }

            // Remove expired cache file
            unlink($cacheFile);
        }

        return null;
    }

    /**
     * @param array<mixed> $content
     */
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
        clearstatcache(true, $this->cacheDir);

        if (! mkdir($this->cacheDir, 0755, true) && ! is_dir($this->cacheDir)) {
            throw new RuntimeException("Cache directory {$this->cacheDir} could not be created");
        }
    }
}
