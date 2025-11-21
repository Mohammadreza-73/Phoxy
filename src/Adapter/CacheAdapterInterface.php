<?php

namespace Phoxy\Adapter;

use Psr\Cache\CacheItemPoolInterface;

interface CacheAdapterInterface extends CacheItemPoolInterface
{
    /**
     * Get adapter name
     */
    public function getName(): string;

    /**
     * Check adapter is available
     */
    public function isAvailable(): bool;

    /**
     * Get adpater statistics
     */
    public function getStats(): array;

    /**
     * Clear all cache items with optional pattern
     */
    public function clearPattern(string $pattern = ''): bool;
}