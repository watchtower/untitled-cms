<?php

return [
    'disks' => [
        'upload' => 'sandbox',
        'storage' => 'vault',
    ],
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'csv', 'txt'],
    'max_size' => 50 * 1024, // 50MB
    'image_washing' => true,
    'clamav_enabled' => env('CLAMAV_ENABLED', false),
    'clamav_host' => env('CLAMAV_HOST', '127.0.0.1'),
    'clamav_port' => env('CLAMAV_PORT', 3310),
    'clamav_timeout' => env('CLAMAV_TIMEOUT', 30),
    'clamav_fail_closed' => env('CLAMAV_FAIL_CLOSED', false),
];
