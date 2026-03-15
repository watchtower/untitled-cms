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
];
