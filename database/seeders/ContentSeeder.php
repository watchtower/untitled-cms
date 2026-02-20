<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $authorId = $admin ? $admin->id : null;

        // Sample Banners
        Banner::create([
            'title' => 'Welcome to Our CMS',
            'image_url' => 'https://images.unsplash.com/photo-1499750310159-5b600aafb1d6?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80',
            'alt_text' => 'Workspace desk with laptop',
            'description' => 'Built with Laravel 12, MongoDB, and Inertia.js',
            'link_url' => '/about-us',
            'order' => 1,
            'is_active' => true,
        ]);

        Banner::create([
            'title' => 'Modern Web Development',
            'image_url' => 'https://images.unsplash.com/photo-1542831371-29b0f74f9713?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80',
            'alt_text' => 'Code on screen',
            'description' => 'Efficient, scalable, and maintainable.',
            'order' => 2,
            'is_active' => true,
        ]);

        // Sample Pages
        Page::create([
            'title' => 'Getting Started with Laravel CMS',
            'slug' => 'getting-started',
            'content' => '<h2>Introduction</h2><p>This CMS is designed to be lightweight yet powerful. It leverages the flexibility of <strong>MongoDB</strong> for schema-less data storage, perfect for dynamic content structures.</p><h3>Key Features</h3><ul><li>Role-Based Access Control (RBAC)</li><li>Rich Text Editing with CKEditor 5</li><li>Media Management Integration</li><li>AI-Powered SEO Tools</li></ul><p>Explore the admin panel to see how easy it is to manage your digital presence.</p>',
            'status' => 'published',
            'author_id' => $authorId,
            'published_at' => now(),
            'seo_title' => 'Getting Started with Laravel MongoDB CMS',
            'seo_description' => 'Learn how to use the new Laravel 12 CMS powered by MongoDB and Inertia.js.',
        ]);

        Page::create([
            'title' => 'The Power of Inertia.js',
            'slug' => 'power-of-inertia',
            'content' => '<h2>Why Inertia?</h2><p>Inertia.js allows you to build single-page apps without building an API. It works like a classic server-side framework but gives you the smooth SPA experience.</p><blockquote>"Build monoliths, not APIs."</blockquote><p>Combined with React and Shadcn UI, we get a modern, accessible, and fast interface for both administrators and visitors.</p>',
            'status' => 'published',
            'author_id' => $authorId,
            'published_at' => now()->subDay(),
            'seo_title' => 'Why We Chose Inertia.js',
            'seo_description' => 'Discover the benefits of using Inertia.js for modern web application development.',
        ]);

        Page::create([
            'title' => 'Draft Page Example',
            'slug' => 'draft-example',
            'content' => '<p>This is a draft page. It should not be visible on the public frontend until published.</p>',
            'status' => 'draft',
            'author_id' => $authorId,
        ]);
    }
}
