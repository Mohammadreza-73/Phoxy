<?php

if (! function_exists('base_path')) {
    function base_path(string $path = '') {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . $path;
    }
}

if (! function_exists('cache_path')) {
    function cache_path(string $file) {
        return base_path('cache') . DIRECTORY_SEPARATOR . $file;
    }
}

if (! function_exists('dd')) {
    function dd(...$vars) {
        echo '<pre>'; var_dump(...$vars); echo '</pre>';
        exit;
    }
}