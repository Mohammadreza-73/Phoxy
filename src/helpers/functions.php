<?php

use Illuminate\Config\Repository;

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
    function dd(mixed ...$vars): void
    {
        echo '<pre>';
        var_dump(...$vars);
        echo '</pre>';
        exit;
    }
}

if (! function_exists('config_file')) {
    function config_file(string $fileName): mixed
    {
        $fileName = $fileName . '.php';

        if (! file_exists(config_path($fileName))) {
            throw new Exception("Config file `$fileName` not found.");
        }

        return require config_path($fileName);
    }
}

if (! function_exists('config')) {
    function config(string $fileName, $key = null, $default = null)
    {
        $config = new Repository(config_file($fileName));

        if (is_null($key)) {
            return $config->all();
        }

        if (is_array($key)) {
            return $config->set($key);
        }

        return $config->get($key, $default);
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
