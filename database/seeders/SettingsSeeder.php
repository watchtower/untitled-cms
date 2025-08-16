<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General Settings
            ['key' => 'site_name', 'value' => 'Untitled CMS', 'type' => 'string', 'group' => 'general', 'description' => 'Website name', 'is_public' => true],
            ['key' => 'site_description', 'value' => 'A modern content management system', 'type' => 'string', 'group' => 'general', 'description' => 'Website description', 'is_public' => true],
            ['key' => 'site_tagline', 'value' => 'Lightweight. Flexible. Untitled.', 'type' => 'string', 'group' => 'general', 'description' => 'Website tagline', 'is_public' => true],
            ['key' => 'admin_email', 'value' => 'admin@example.com', 'type' => 'string', 'group' => 'general', 'description' => 'Administrator email address', 'is_public' => false],

            // SEO Settings
            ['key' => 'meta_title', 'value' => 'Untitled CMS', 'type' => 'string', 'group' => 'seo', 'description' => 'Default meta title', 'is_public' => true],
            ['key' => 'meta_description', 'value' => 'A modern content management system built with Laravel', 'type' => 'string', 'group' => 'seo', 'description' => 'Default meta description', 'is_public' => true],
            ['key' => 'meta_keywords', 'value' => '["cms", "laravel", "content management"]', 'type' => 'json', 'group' => 'seo', 'description' => 'Default meta keywords', 'is_public' => true],

            // Social Media
            ['key' => 'og_title', 'value' => 'Untitled CMS', 'type' => 'string', 'group' => 'social', 'description' => 'OpenGraph title', 'is_public' => true],
            ['key' => 'og_description', 'value' => 'A modern content management system built with Laravel', 'type' => 'string', 'group' => 'social', 'description' => 'OpenGraph description', 'is_public' => true],
            ['key' => 'og_image', 'value' => '', 'type' => 'string', 'group' => 'social', 'description' => 'OpenGraph image URL', 'is_public' => true],

            // System Settings
            ['key' => 'maintenance_mode', 'value' => '0', 'type' => 'boolean', 'group' => 'system', 'description' => 'Enable maintenance mode', 'is_public' => false],
            ['key' => 'allow_registration', 'value' => '1', 'type' => 'boolean', 'group' => 'system', 'description' => 'Allow user registration', 'is_public' => false],
            ['key' => 'require_email_verification', 'value' => '1', 'type' => 'boolean', 'group' => 'system', 'description' => 'Require email verification', 'is_public' => false],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
