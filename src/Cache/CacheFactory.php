<?php

namespace Phoxy\Cache;

use Phoxy\Adapter\ArrayAdapter;
use Phoxy\Adapter\CacheAdapterInterface;
use Phoxy\Adapter\FilesystemAdapter;

class CacheFactory
{
    public static function createCachePool(string $adapterType): CacheAdapterInterface
    {
        $namespace = config('cache', 'namespace');

        switch($adapterType) {
            case "array":
                return new ArrayAdapter($namespace);

            case "filesystem":
                $directory = config('cache', 'file.directory');

                return new FilesystemAdapter($directory, $namespace);

            default:
                throw new \InvalidArgumentException("Unsupported cache adapter: {$adapterType}");
        }
    }
}