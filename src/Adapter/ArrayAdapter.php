<?php

namespace Phoxy\Adapter;

use Phoxy\Cache\CacheItem;
use Phoxy\Concern\Formatable;
use Psr\Cache\CacheItemInterface;

class ArrayAdapter implements CacheAdapterInterface
{
    use Formatable;

    private array $storage = [];
    private array $deferred = [];
 
    public function __construct(
        private string $namespace
    ) {
        $this->namespace = $namespace;
    }

    public function getName(): string
    {
        return 'array';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function getItem(string $key): CacheItemInterface
    {
        $this->validateKey($key);
        $realKey = $this->getRealKey($key);

        if (isset($this->storage[$realKey])) {
            $data = $this->storage[$realKey];

            // Check if expired
            if ($data['expiry'] !== null && time() > $data['expiry']) {
                unset($this->storage[$realKey]);

                return CacheItem::miss($key);
            }

            return CacheItem::hit($key, $data['value'], $data['expiry']);
        }

        return CacheItem::miss($key);
    }

    public function getItems(array $keys = []): array
    {
        $items = [];
        foreach ($keys as $key) {
            $item[$key] = $this->getItem($key);
        }

        return $items;
    }

    public function hasItem($key): bool
    {
        return $this->getItem($key)->isHit();
    }

    public function clear(): bool
    {
        $this->storage = [];

        return true;
    }

    public function deleteItem(string $key): bool
    {
        $this->validateKey($key);
        $realKey = $this->getRealKey($key);

        unset($this->storage[$realKey]);

        return true;
    }

    public function deleteItems(array $keys): bool
    {
        foreach ($keys as $key) {
            if ($this->deleteItem($key) === false) {
                return false;
            }
        }

        return true;
    }

    public function save(CacheItemInterface $item): bool
    {
        $key = $item->getKey();
        $this->validateKey($key);
        $realKey = $this->getRealKey($key);

        $expiration = $item instanceof CacheItem
            ? $item->getExpirationTimestamp()
            : null;

        $this->storage[$realKey] = [
            'value' => $item->get(),
            'expiry' => $expiration,
            'created' => time(),
        ];

        return true;
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        $this->deferred[$item->getKey()] = $item;

        return true;
    }

    public function commit(): bool
    {
        $status = true;
        foreach ($this->deferred as $item) {
            if ($this->save($item) === false) {
                $status = false;
            }
        }

        $this->deferred = [];

        return $status;
    }

    public function getStats(): array
    {
        $totalItems = count($this->storage);
        $expiredItems = 0;
        $totalSize = 0;

        foreach ($this->storage as $data) {
            if ($data['expiry'] !== null && time() > $data['expiry']) {
                $expiredItems++;
            }
            $totalSize += strlen(serialize($data['value']));
        }

        return [
            'adapter' => $this->getName(),
            'items_count' => $totalItems,
            'expired_items' => $totalItems,
            'total_size' => $totalSize,
            'total_size_human' => $this->formatBytes($totalSize),
            'namespace' => $this->namespace,
            'deferred_count' => count($this->deferred),
        ];
    }

    public function clearPattern(string $pattern = ''): bool
    {
        $cleared = 0;
        $searchPattern = $this->getRealKey($pattern);

        foreach (array_keys($this->storage) as $key) {
            if (strpos($key, $searchPattern) === 0) {
                unset($this->storage[$key]);
                $cleared++;
            }
        }

        return $cleared > 0;
    }

    private function getRealKey(string $key): string
    {
        return $this->namespace . ':' . $key;
    }

    private function validateKey(string $key): void
    {
        if (empty($key)) {
            throw new \InvalidArgumentException("Cache key is empty");
        }
    }
}