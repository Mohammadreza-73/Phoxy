<?php

return [
    'type' => 'file', // or Redis
    'ttl' => 3600, // an hour

    'filter_url_status' => true,
    'whitelist' => [
        'zommit.ir',
    ],
];