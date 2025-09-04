<?php

use CachingProxy\FileCache;

require __DIR__.'/vendor/autoload.php';

$fc = new FileCache();
echo $fc->get('https://digiato.ir');
