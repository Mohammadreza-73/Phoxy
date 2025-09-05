<?php

require __DIR__.'/vendor/autoload.php';

$proxy = new CachingProxy\ProxyServer();
$proxy->handleRequest();