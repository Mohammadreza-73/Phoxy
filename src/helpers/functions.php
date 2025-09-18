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
        echo '<pre>';
        var_dump(...$vars);
        echo '</pre>';
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

if (! function_exists('info_log')) {
    function info_log(mixed $message): void
    {
        $date = date('Y-m-d H:i:s');
        $log = "[$date] INFO: $message \n";

        file_put_contents(base_path('logs/app.log'), $log, FILE_APPEND);
    }
}

if (! function_exists('error_log')) {
    function error_log(mixed $message): void
    {
        $date = date('Y-m-d H:i:s');
        $log = "[$date] ERROR: $message \n";

        file_put_contents(base_path('logs/app.log'), $log, FILE_APPEND);
    }
}
