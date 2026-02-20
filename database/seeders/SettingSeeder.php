<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            [
                'key' => 'site_name',
                'value' => 'Untitled CMS',
                'group' => 'general',
                'type' => 'text',
                'label' => 'Site Name',
                'is_public' => true,
            ],
            [
                'key' => 'site_description',
                'value' => 'A powerful CMS built with Laravel and React.',
                'group' => 'general',
                'type' => 'textarea',
                'label' => 'Site Description',
                'is_public' => true,
            ],

            // Branding
            [
                'key' => 'auth_image_url',
                'value' => '/images/auth-bg.jpg',
                'group' => 'branding',
                'type' => 'image',
                'label' => 'Auth Background Image',
                'is_public' => true,
            ],

            // Integrations
            [
                'key' => 'tinymce_api_key',
                'value' => 'no-api-key', // Default
                'group' => 'integrations',
                'type' => 'text',
                'label' => 'TinyMCE API Key',
                'is_public' => true,
            ],
            [
                'key' => 'social_login_google_enabled',
                'value' => true,
                'group' => 'integrations',
                'type' => 'boolean',
                'label' => 'Enable Google Login',
                'is_public' => true,
            ],
            [
                'key' => 'social_login_apple_enabled',
                'value' => true,
                'group' => 'integrations',
                'type' => 'boolean',
                'label' => 'Enable Apple Login',
                'is_public' => true,
            ],
            [
                'key' => 'social_login_twitter_enabled',
                'value' => false,
                'group' => 'integrations',
                'type' => 'boolean',
                'label' => 'Enable Twitter (X) Login',
                'is_public' => true,
            ],
            [
                'key' => 'social_login_github_enabled',
                'value' => false,
                'group' => 'integrations',
                'type' => 'boolean',
                'label' => 'Enable GitHub Login',
                'is_public' => true,
            ],

            // System
            [
                'key' => 'maintenance_mode',
                'value' => false,
                'group' => 'system',
                'type' => 'boolean',
                'label' => 'Maintenance Mode',
                'is_public' => true, // Might need middleware logic for this
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
