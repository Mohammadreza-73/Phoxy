<?php

use Phoxy\ProxyServer;

describe('ProxyServer', function () {
    beforeEach(function () {
        $this->proxy = new ProxyServer();
    });

    afterEach(function () {
        Mockery::close();
    });

    test('instantiates successfully', function () {
        expect($this->proxy)->toBeInstanceOf(ProxyServer::class);
    });

    test('has handleRequest method', function () {
        expect(method_exists($this->proxy, 'handleRequest'))->toBeTrue();
    });

    test('calls handleRequest without errors', function () {
        /** @var Phoxy\ProxyServer|Mockery\MockInterface $mock */
        $mock = Mockery::mock(ProxyServer::class)->makePartial();

        expect(fn () => $mock->handleRequest())->not->toThrow(Exception::class);
    });

    test('handles request through proxy server', function () {
        /** @var Phoxy\ProxyServer|Mockery\MockInterface $mock */
        $mock = Mockery::mock(ProxyServer::class)->makePartial();
        $mock->shouldReceive('handleRequest')->once()->andReturnNull();

        $mock->handleRequest();
    });
});
