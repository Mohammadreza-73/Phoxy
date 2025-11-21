<?php

return [
    /**
     * --------------------------
     * Default namespace
     * --------------------------
     *
     * This paramter make Isolation Between Applications,
     * for multiple proxy instances.
     */
    'namespace' => 'phoxy',

    /**
     * --------------------------
     * Time to Live
     * --------------------------
     *
     * This parameter is cache time to live in seconds for outdate cache
     * data and cache new data.
     */
    'ttl' => [
        'html' => 1800,
        'css' => 86400,
        'js' => 86400,
        'images' => 604800,
        'fonts' => 2592000,
        'json' => 3600,
        'other' => 3600
    ],

    /**
     * --------------------------
     * Content type max size
     * --------------------------
     *
     * This parameter shows Content type max size.
     */
    'max_sizes' => [
        'html' => 10485760,
        'css' => 5242880,
        'js' => 10485760,
        'images' => 10485760,
        'fonts' => 5242880,
        'other' => 5242880
    ],

    /**
     * --------------------------
     * Request time out
     * --------------------------
     *
     * This parameter indicates request time out in seconds
     * to the origin URL.
     */
    'timeout' => 30,

    /**
     * --------------------------
     * Filter URL
     * --------------------------
     * This parameter enable/disable filter origin URL.
     *
     */
    'filter_url_enable' => true,

    /**
     * --------------------------
     * List of filterd URL
     * --------------------------
     * This parameter idicates filterd URL.
     *
     */
    'filter_url_list' => [
        // 'malicious_url.com',
    ],

    /**
     * --------------------------
     * Default Driver
     * --------------------------
     *
     * Handle caches with difference strategies,
     * like: `file`, `database`, `redis`
     */
    'driver' => 'file',

    /**
     * --------------------------
     * File caching driver
     * --------------------------
     * This driver caches the content of the origin into the file.
     *
     */
    'file' => [
        /**
         * File content limitation
         */
        'max_content_length' => 10485760, // 10MB
        'directory' => cache_path(),
    ],
];
