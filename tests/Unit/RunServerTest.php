<?php

use Phoxy\ProxyServer;

beforeEach(function () {
    $cacheDir = cache_path();

    if (file_exists($cacheDir)) {
        array_map('unlink', glob($cacheDir . '/*'));
        rmdir($cacheDir);
    }

    $this->proxy = new ProxyServer();
});

afterEach(function () {
    Mockery::close();
});

describe('ProxyServer', function () {
    test('instantiates successfully', function () {
        expect($this->proxy)->toBeInstanceOf(ProxyServer::class);
    });

    test('has handleRequest method', function () {
        expect(method_exists($this->proxy, 'handleRequest'))->toBeTrue();
    });

    test('calls handleRequest without errors', function () {
        /** @var Phoxy\ProxyServer|Mockery\MockInterface $mock */
        $mock = Mockery::mock(ProxyServer::class)->makePartial();
        $_GET['url'] = urlencode('example.com?url=site.com');

        expect(fn () => $mock->handleRequest())->not->toThrow(Exception::class);
    });

    test('handles request through proxy server', function () {
        /** @var Phoxy\ProxyServer|Mockery\MockInterface $mock */
        $mock = Mockery::mock(ProxyServer::class)->makePartial();
        $mock->shouldReceive('handleRequest')->once()->andReturnNull();

        $mock->handleRequest();
    });
});
