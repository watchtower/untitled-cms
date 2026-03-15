<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        // Header navigation
        Menu::updateOrCreate(['slug' => 'app_header'], [
            'name' => 'Header Navigation',
            'slug' => 'app_header',
            'is_active' => true,
            'items' => [
                [
                    'id' => Str::uuid(),
                    'title' => 'Home',
                    'url' => '/',
                    'target' => '_self',
                    'order' => 1,
                    'subItems' => [],
                ],
                [
                    'id' => Str::uuid(),
                    'title' => 'CMS',
                    'url' => '#',
                    'target' => '_self',
                    'order' => 2,
                    'subItems' => [
                        [
                            'id' => Str::uuid(),
                            'title' => 'Getting Started',
                            'url' => '/getting-started',
                            'target' => '_self',
                            'order' => 1,
                        ],
                        [
                            'id' => Str::uuid(),
                            'title' => 'The Power of Inertia.js',
                            'url' => '/power-of-inertia',
                            'target' => '_self',
                            'order' => 2,
                        ],
                    ],
                ],
                [
                    'id' => Str::uuid(),
                    'title' => 'Resources',
                    'url' => '#',
                    'target' => '_self',
                    'order' => 3,
                    'subItems' => [
                        [
                            'id' => Str::uuid(),
                            'title' => 'Privacy Policy',
                            'url' => '/privacy',
                            'target' => '_self',
                            'order' => 1,
                        ],
                        [
                            'id' => Str::uuid(),
                            'title' => 'Terms of Service',
                            'url' => '/terms',
                            'target' => '_self',
                            'order' => 2,
                        ],
                    ],
                ],
            ],
        ]);

        // Footer navigation
        Menu::updateOrCreate(['slug' => 'footer-navigation'], [
            'name' => 'Footer Navigation',
            'slug' => 'footer-navigation',
            'is_active' => true,
            'items' => [
                [
                    'id' => Str::uuid(),
                    'title' => 'Home',
                    'url' => '/',
                    'target' => '_self',
                    'order' => 1,
                    'subItems' => [],
                ],
                [
                    'id' => Str::uuid(),
                    'title' => 'Getting Started',
                    'url' => '/getting-started',
                    'target' => '_self',
                    'order' => 2,
                    'subItems' => [],
                ],
                [
                    'id' => Str::uuid(),
                    'title' => 'The Power of Inertia.js',
                    'url' => '/power-of-inertia',
                    'target' => '_self',
                    'order' => 3,
                    'subItems' => [],
                ],
            ],
        ]);
    }
}
