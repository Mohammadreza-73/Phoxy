<?php

namespace CachingProxy;

class FileCache
{
    public function __construct()
    {
        if (! file_exists(__DIR__.'/cache/')) {
            mkdir('cache', 0755, true);
        }
    }

    public function get(string $url)
    {
        $fileName = md5($url);
        $filePath = cache_path($fileName);

        if (file_exists($filePath)) {
            return file_get_contents($filePath);
        }

        $content = file_get_contents($url);

        if ($content !== false) {
            file_put_contents($filePath, $content);
        }

        return $content;
    }
}