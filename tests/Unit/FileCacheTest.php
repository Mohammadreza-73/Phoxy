<?php

/**
 * Clean up cache directory before each test
 */
beforeEach(function () {
    $cacheDir = base_path('cache/');

    if (file_exists($cacheDir)) {
        array_map('unlink', glob($cacheDir . '/*'));
        rmdir($cacheDir);
    }
});

afterAll(function () {
    $cacheDir = base_path('cache/');

    if (file_exists($cacheDir)) {
        array_map('unlink', glob($cacheDir . '/*'));
        @rmdir($cacheDir);
    }
});

describe('contructor', function () {
    test('creates cache directory if it does not exist', function () {
        $cacheDir = cache_path();
        // Ensure directory does not exist
        if (file_exists($cacheDir)) {
            array_map('unlink', glob($cacheDir . '/*'));
            rmdir($cacheDir);
        }

        new Phoxy\FileCache();

        expect(file_exists($cacheDir))->toBeTrue();
        expect(is_dir($cacheDir))->toBeTrue();
    });

    test('does not throw exception if cache directory already exists', function () {
        $cacheDir = cache_path();

        expect(fn() => new Phoxy\FileCache)->not->toThrow(Exception::class);
        expect(file_exists($cacheDir))->toBeTrue();
    });
});

describe('getCacheKey', function () {
    test('returns md5 hash of the URL', function () {
        $fileCache = new Phoxy\FileCache();
        $url = 'https://example.com/api/v1';
        $expectedKey = md5($url);

        $result = $fileCache->getCacheKey($url);

        expect($result)->toBe($expectedKey);
    });

    test('returns different keys for different URLs', function () {
        $fileCache = new Phoxy\FileCache();
        $url1 = 'https://example.com/api/v1';
        $url2 = 'https://example.com/api/v2';

        $key1 = $fileCache->getCacheKey($url1);
        $key2 = $fileCache->getCacheKey($url2);

        expect($key1)->not->toBe($key2);
    });
});

describe('get', function () {
    test('returns null when cache file does not exist', function () {
        $fileCache = new Phoxy\FileCache();
        $result = $fileCache->get('https://example.com/not-exist');

        expect($result)->toBeNull();
    });

    test('returns null when cache file exists but is not readable', function () {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('File permissions test not applicable on Windows');
        }

        $fileCache = new Phoxy\FileCache();
        $url = 'https://example.com/api/v1/unreachable';
        $cacheKey = $fileCache->getCacheKey($url);
        $cacheFile = cache_path($cacheKey);

        file_put_contents($cacheFile, serialize(['data' => 'test-data']));
        chmod($cacheFile, 0000);

        $result = $fileCache->get($url);

        expect($result)->toBeNull();

        // Restore permissions for cleanup
        chmod($cacheFile, 0644);
    });

    test('returns cached content when file exists and is not expired', function () {
        $fileCache = new Phoxy\FileCache();
        $url = 'https://example.com/api/v1/data';
        $data = ['data' => 'test', 'status' => 200];

        $fileCache->set($url, $data);
        $result = $fileCache->get($url);

        expect($result)->toBe($data);
    });

    test('deletes expired cache file and returns null', function () {
        $fileCache = new Phoxy\FileCache();
        $url = 'https://example.com/api/v1/expired';
        $cacheKey = $fileCache->getCacheKey($url);
        $cacheFile = cache_path($cacheKey);

        // Create cache file with old timestamp (beyond TTL)
        $oldTimestamp = time() - 4000;
        $cacheContent = [
            'timestamp' => $oldTimestamp,
            'content' => '',
            'url' => $url,
        ];

        file_put_contents($cacheFile, serialize($cacheContent));
        // Set file modification time
        touch($cacheFile, $oldTimestamp);

        $result = $fileCache->get($url);

        expect($result)->toBeNull();
        expect(file_exists($cacheFile))->toBeFalse();
    });

    test('handles corrupted cache file gracefully', function () {
        $fileCache = new Phoxy\FileCache();
        $url = 'https://example.com/api.v1/corrupted';
        $cacheKey = $fileCache->getCacheKey($url);
        $cacheFile = cache_path($cacheKey);

        file_put_contents($cacheFile, 'invalid-serialized-data');

        // Write invalid serialized data
        $result = $fileCache->get($url);
        // unserialize returns false for invalid data, which should be treated as cache miss
        expect($result)->toBeNull();
    });
});

describe('set', function () {
    test('creates cache file with correct content', function () {
        $fileCache = new Phoxy\FileCache();
        $url = 'https://example.com/api/v1/data';
        $cacheKey = $fileCache->getCacheKey($url);
        $cacheFile = cache_path($cacheKey);
        $data = ['data' => 'test', 'status' => 200];

        $result = $fileCache->set($url, $data);

        expect($result)->toBeTrue();
        expect(file_exists($cacheFile))->toBeTrue();

        $cachedContent = unserialize(file_get_contents($cacheFile));
        expect($cachedContent['timestamp'])->toBeNumeric();
        expect($cachedContent['content'])->toBe($data);
        expect($cachedContent['url'])->toBe($url);
    });

    test('overwrites existing cache file', function () {
        $fileCache = new Phoxy\FileCache();
        $url = 'https://example.com/api/v1/data';
        $cacheKey = $fileCache->getCacheKey($url);
        $cacheFile = cache_path($cacheKey);

        $fileCache->set($url, ['data' => 'old']);

        $newData = ['data' => 'new'];
        $fileCache->set($url, $newData);

        $cachedContent = unserialize(file_get_contents($cacheFile));
        expect($cachedContent['content'])->toBe($newData);
    });

    test('stores complex array structures correctly', function () {
        $fileCache = new Phoxy\FileCache();
        $url = 'https://example.com/api/v1/complex';
        $complexData = [
            'nested' => ['key' => 'value'],
            'list' => [1, 2, 3],
            'boolean' => true,
            'null' => null,
        ];

        $fileCache->set($url, $complexData);
        $result = $fileCache->get($url);

        expect($result)->toBe($complexData);
    });
});
