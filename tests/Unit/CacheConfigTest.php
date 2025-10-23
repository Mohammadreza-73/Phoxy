<?php

test('config file path', function () {
    $configPath = config_path('cache.php');

    expect($configPath)->toBe(base_path('src/config/cache.php'));
});

test('config file without index to be an array', function () {
    $config = config('cache');

    expect($config)->toBeArray();
    expect(count($config))->toBe(6);
});

test('config file expected values', function () {
    expect(config('cache', 'driver'))->toBeString();
    expect(config('cache', 'ttl'))->toBeInt();
    expect(config('cache', 'timeout'))->toBeInt();
    expect(config('cache', 'filter_url_enable'))->toBeBool();
    expect(config('cache', 'filter_url_list'))->toBeArray();
    expect(config('cache', 'file'))->toBeArray();
    expect(config('cache', 'file.max_content_length'))->toBeInt();
});
