<?php

return [
    'disks' => [
        'upload' => 'sandbox',
        'storage' => 'vault',
    ],
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'csv', 'txt'],
    'max_size' => 50 * 1024, // 50MB
    'image_washing' => true,
    'webp_conversion' => env('VAULT_WEBP_CONVERSION', true),
    'webp_quality' => env('VAULT_WEBP_QUALITY', 82), // 0–100; GD clamps out-of-range values
    'clamav_enabled' => env('CLAMAV_ENABLED', false),
];
