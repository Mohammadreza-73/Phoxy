<?php

require __DIR__.'/vendor/autoload.php';

$proxy = new Phoxy\ProxyServer();
$proxy->handleRequest();
