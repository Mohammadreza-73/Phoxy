<?php

if (! function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . $path;
    }
}

if (! function_exists('cache_path')) {
    function cache_path(string $file = ''): string
    {
        return base_path('cache') . DIRECTORY_SEPARATOR . $file;
    }
}

if (! function_exists('config_path')) {
    function config_path(string $file): string
    {
        return base_path('src/config') . DIRECTORY_SEPARATOR . $file;
    }
}

if (! function_exists('dd')) {
    function dd(...$vars): void
    {
        echo '<pre>'; var_dump(...$vars); echo '</pre>';
        exit;
    }
}

if (! function_exists('config')) {
    function config(string $fileName, string $index)
    {
        $fileName = $fileName . '.php';

        if (! file_exists(config_path($fileName))) {
            throw new Exception("Config file `$fileName` not found.");
        }

        $config = require config_path($fileName);

        return $config[$index] ?? null;
    }
}