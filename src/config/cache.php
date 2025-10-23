<?php

return [
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
     * Time to Live
     * --------------------------
     * 
     * This parameter is cache time to live in seconds for outdate cache
     * data and cache new data.
     */
    'ttl' => 3600,

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
    ],
];
