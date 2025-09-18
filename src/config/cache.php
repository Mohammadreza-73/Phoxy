<?php

return [
    'type' => 'file', // or Redis
    'ttl' => 3600, // an hour
    'timeout' => 30, // seconds
    'max_content_length' => 10485760, // 10MB

    'filter_url_status' => true,
    'blacklist' => [
        'zoomit.ir',
    ],
];
