<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    /**
     * Get a specific setting by key.
     */
    public function get(string $key, $default = null)
    {
        $settings = $this->getAllCached();

        return $settings[$key] ?? $default;
    }

    /**
     * Get all settings (cached).
     */
    public function getAllCached()
    {
        return Cache::rememberForever('app_settings', function () {
            return Setting::all()->pluck('value', 'key')->toArray();
        });
    }

    /**
     * Get all public settings for frontend.
     */
    public function getPublicSettings()
    {
        return Cache::rememberForever('app_settings_public', function () {
            return Setting::where('is_public', true)->pluck('value', 'key')->toArray();
        });
    }

    /**
     * Set a setting value.
     */
    public function set(string $key, $value)
    {
        $setting = Setting::where('key', $key)->first();

        if ($setting) {
            $setting->update(['value' => $value]);
            $this->clearCache();

            return $setting;
        }

        return null;
    }

    /**
     * Clear settings cache.
     */
    public function clearCache()
    {
        Cache::forget('app_settings');
        Cache::forget('app_settings_public');
    }
}
