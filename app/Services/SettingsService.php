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
     * Get all public settings for frontend, with type coercion applied.
     * Uses the same boolean/number casting as Setting::get() so that
     * boolean settings are always true/false, never "1"/"0" strings.
     */
    public function getPublicSettings(): array
    {
        return Cache::rememberForever('app_settings_public', function () {
            return Setting::where('is_public', true)
                ->get()
                ->mapWithKeys(fn ($setting) => [
                    $setting->key => match ($setting->type) {
                        'boolean' => (bool) $setting->value,
                        'number'  => (float) $setting->value,
                        default   => $setting->value,
                    },
                ])
                ->toArray();
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
            $this->clearCache($key);

            return $setting;
        }

        return null;
    }

    /**
     * Clear settings cache.
     */
    public function clearCache(?string $key = null)
    {
        Cache::forget('app_settings');
        Cache::forget('app_settings_public');

        if ($key !== null) {
            Cache::forget("setting.{$key}");
        }
    }
}
