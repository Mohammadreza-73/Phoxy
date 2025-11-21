<?php

namespace Phoxy\Adapter;

use Phoxy\Cache\CacheItem;
use Psr\Cache\CacheItemInterface;
use Phoxy\Exception\CacheException;
use Phoxy\Adapter\CacheAdapterInterface;
use Phoxy\Concern\Formatable;

class FilesystemAdapter implements CacheAdapterInterface
{
    use Formatable;

    private array $deferred = [];

    public function __construct(
        private string $directory,
        private string $namespace
    ) {
        $this->directory = rtrim($directory, '/\\') . '/';
        $this->namespace = $namespace;

        if (! is_dir($this->directory)) {
            if (! mkdir($this->directory, 0755, true)) {
                throw new CacheException("Cannot create cache directory: '{$this->directory}'");
            }
        }

        if (! is_writeable($this->directory)) {
            throw new CacheException("Cache directory is not writeable: '{$this->directory}'");
        }
    }

    public function getName(): string
    {
        return 'filesystem';
    }

    public function isAvailable(): bool
    {
        return is_writeable($this->directory);
    }

    /**
     * {@inheritDoc}
     */
    public function getItem(string $key): CacheItemInterface
    {
        $this->validateKey($key);
        $fileName = $this->getFileName($key);

        if (! file_exists($fileName)) {
            return CacheItem::miss($key);
        }

        try {
            $data = unserialize(file_get_contents($fileName));

            if (! is_array($data) || ! isset($data['value'])) {
                throw new CacheException("corrupted cache file: {$fileName}");
            }

            $expiration = $data['expiry'] ?? null;
            // Check expiration
            if ($expiration && time() > $expiration) {
                $this->deleteItem($key);

                return CacheItem::miss($key);
            }
            
            return CacheItem::hit($key, $data['value'], $expiration);

        } catch (\Exception $e) {
            // Remove corrupted file
            @unlink($fileName);

            throw new CacheException("Failed to read cache file: {$e->getMessage()}");
        }

    }

    /**
     * {@inheritDoc}
     */
    public function getItems(array $keys = []): iterable
    {
        $items = [];
        foreach ($keys as $key) {
            $item[$key] = $this->getItem($key);
        }

        return $items;
    }

    /**
     * {@inheritDoc}
     */
    public function hasItem(string $key): bool
    {
        return $this->getItem($key)->isHit();
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): bool
    {
        $files = glob($this->directory . $this->namespace . '_*.cache');

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteItem(string $key): bool
    {
        $this->validateKey($key);
        $fileName = $this->getFileName($key);

        if (file_exists($fileName)) {
            unlink($fileName);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteItems(array $keys): bool
    {
        foreach ($keys as $key) {
            if (! $this->deleteItem($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function save(CacheItemInterface $item): bool
    {
        $key = $item->getKey();
        $this->validateKey($key);
        $fileName = $this->getFileName($item->getKey());
        $tempFile = $fileName . '.' . uniqid('', true) . '.tmp';

        $expiration = $item instanceof CacheItem
            ? $item->getExpirationTimestamp()
            : null;

        $data = [
            'value' => $item->get(),
            'expiry' => $expiration,
            'created' => time(),
            'key' => $key,
        ];

        try {
            $result = file_put_contents($tempFile, serialize($data));

            if ($result === false) {
                throw new CacheException("Failed to write cache file");
            }

            if (rename($tempFile, $fileName) === false) {
                unlink($tempFile);

                throw new CacheException("Failed to move cache file to destination location");
            }

            return true;

        } catch (\Exception $e) {
            unlink($tempFile);

            throw new CacheException("Failed to save cache item: {$e->getMessage()}");
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        $this->deferred[$item->getKey()] = $item;

        return true;
    }

    /**
     * {@inheritDoc}
     */
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

    /**
     * {@inheritDoc}
     */
    public function getStats(): array
    {
        $files = glob($this->directory . $this->namespace . '_*.cache');
        $totalSize = 0;
        $expiredCount = 0;

        foreach ($files as $file) {
            $size = filesize($file);
            $totalSize += $size;

            try {
                $data = unserialize(file_get_contents($file));

                if (isset($data['expiry']) && time() > $data['expiry']) {
                    $expiredCount++;
                }

            } catch (\Exception $e) {
                // Skip corrupted files
            }
        }

        return [
            'adapter' => $this->getName(),
            'items_count' => count($files),
            'expired_items' => $expiredCount,
            'total_size' => $totalSize,
            'total_size_human' => $this->formatBytes($totalSize),
            'directory' => $this->directory,
            'namespace' => $this->namespace,
            'deferred_count' => count($this->deferred),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function clearPattern(string $pattern = ''): bool
    {
        $files = glob($this->directory . $this->namespace . '_' . $pattern . '*.cache');
        $deleted = 0;

        foreach ($files as $file) {
            if (unlink($file)) {
                $deleted++;
            }
        }

        return $deleted > 0;
    }

    private function getFileName(string $key): string
    {
        $hash = hash('sha256', $this->namespace . $key);

        return $this->directory . $hash . '.cache';
    }

    private function validateKey(string $key): void
    {
        if (empty($key)) {
            throw new \InvalidArgumentException("Cache key is empty");
        }
    }
}