<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    |
    | Inertia v3 defaults to `resources/js/pages` (lowercase). This app keeps
    | page components in `resources/js/Pages`, which only resolves by accident
    | on case-insensitive filesystems (macOS) and fails on Linux, e.g. when
    | `assertInertia()` checks that a page component file exists in CI.
    |
    | Other Inertia options fall back to the package defaults.
    |
    */

    'pages' => [

        'ensure_pages_exist' => false,

        'paths' => [
            resource_path('js/Pages'),
        ],

        'extensions' => [
            'js',
            'jsx',
            'ts',
            'tsx',
        ],

    ],

];
