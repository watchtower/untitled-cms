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

            // Auth
            [
                'key'         => 'auth.registration_enabled',
                'value'       => false,
                'group'       => 'auth',
                'type'        => 'boolean',
                'label'       => 'Enable Self-Registration',
                'description' => 'Allow new users to register via the /register page. When disabled, only admin-invited users can create accounts.',
                'is_public'   => false,
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

            // AI Features
            [
                'key' => 'vault.moderation_enabled',
                'value' => false,
                'group' => 'ai',
                'type' => 'boolean',
                'label' => 'Enable AI Image Moderation',
                'description' => 'Automatically moderate uploaded images using the active AI Hub.',
                'is_public' => false,
            ],
            [
                'key' => 'ai.chat_enabled',
                'value' => true,
                'group' => 'ai',
                'type' => 'boolean',
                'label' => 'Enable AI Chat Assistant',
                'description' => 'Show the AI chat assistant button for all admin users. Requires an active AI Hub.',
                'is_public' => false,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
