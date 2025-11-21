<?php

namespace Phoxy\Cache;

use Phoxy\Response;
use Phoxy\Adapter\CacheAdapterInterface;

/**
 * Implements caching policies, TTL strategies, content validation
 */
class ProxyCache
{
    public function __construct(
        private CacheAdapterInterface $adapter,
        private array $config = []
    ) {
        $this->adapter = $adapter;
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    public function cacheRespnose(string $url, array $response): bool
    {
        if ($this->shouldCacheResponse($response) === false) {
            return false;
        }

        $key = $this->generateCacheKey($url);
        $contentType = $response['content_type'] ?? 'text/html';
        $ttl = $this->getTtlForContentType($contentType);

        try {
            $cacheItem = new CacheItem($key);
            $cacheItem->set($response);
            $cacheItem->expiresAfter($ttl);

            return $this->adapter->save($cacheItem);

        } catch(\InvalidArgumentException $e) {
            return false;
        }
    }

    public function getCachedResponse(string $url): ?array
    {
        $key = $this->generateCacheKey($url);

        try {
            $item = $this->adapter->getItem($key);

            if ($item->isHit() === false) {
                return null;
            }

            $response = $item->get();

            // Additional validation for custom CacheItem
            if ($item instanceof CacheItem && $item->isExpired()) {
                $this->adapter->deleteItem($key);

                return null;
            }

            return is_array($response) ? $response : null;

        } catch(\InvalidArgumentException $e) {
            return null;
        }
    }

    public function delete(string $url): bool
    {
        $key = $this->generateCacheKey($url);

        try {
            return $this->adapter->deleteItem($key);
        } catch(\InvalidArgumentException $e) {
            return false;
        }
    }

    public function clear(): bool
    {
        return $this->adapter->clear();
    }

    public function has(string $url): bool
    {
        $key = $this->generateCacheKey($url);

        try {
            return $this->adapter->hasItem($key);

        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    public function getStats(): array
    {
        return $this->adapter->getStats();
    }

    public function clearPattern(string $pattern): bool
    {
        return $this->adapter->clearPattern($pattern);
    }

    private function generateCacheKey(string $url): string
    {
        return 'response:'.md5($url);
    }

    private function getDefaultConfig(): array
    {
        return array_merge(
            config('cache', 'ttl'),
            config('cache', 'max_size')
        );
    }

    private function shouldCacheResponse($response): bool
    {
        if ($response['status_code'] !== Response::HTTP_OK) {
            return false;
        }

        $contentType = $response['content_type'] ?? '';
        if ($this->isCacheableContentType($contentType)) {
            return false;
        }

        $contentLength = strlen($response['body'] ?? '');
        $maxSize = $this->getMaxSizeForContentType($contentType);

        if ($contentLength > $maxSize) {
            return false;
        }

        return true;
    }

    private function isCacheableContentType(string $contentType): bool
    {
        $cacheableTypes = [
            'text/html',
            'text/css',
            'application/javascript',
            'text/javascript',
            'application/json',
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'font/woff',
            'font/woff2'
        ];

        return in_array($contentType, $cacheableTypes)
            ? true
            : false;
    }

    private function getTtlForContentType($contentType): int
    {
        $category = $this->getContentCategory($contentType);

        return $this->config['ttl'][$category] ?? $this->config['ttl']['other'];
    }

    private function getMaxSizeForContentType(string $contentType): int
    {
        $category = $this->getContentCategory($contentType);

        return $this->config['max_sizes'][$category] ?? $this->config['max_sizes']['other'];
    }

    private function getContentCategory(string $contentType)
    {
        $contentType = strtolower($contentType);

        /** @var array<string,string> */
        $contentTypeMap = [
            'text/html' => 'html',
            'text/css' => 'css',
            'javascript' => 'js',
            'image/' => 'images',
            'font/' => 'fonts',
            'application/json' => 'json',
        ];

        foreach ($contentTypeMap as $type => $value) {
            return ($contentType === $type) ? $value : 'other';
        }
    }
}