<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Setting extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'settings';

    protected $fillable = [
        'key',
        'value',
        'group',
        'type', // text, textarea, boolean, image, number
        'label',
        'is_public', // exposed to frontend
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public static function get($key, $default = null)
    {
        $setting = \Illuminate\Support\Facades\Cache::rememberForever("setting.{$key}", function () use ($key) {
            return self::where('key', $key)->first();
        });

        if (!$setting)
            return $default;

        $value = $setting->value;
        if ($setting->type === 'boolean')
            return (bool) $value;
        if ($setting->type === 'number')
            return (float) $value;

        return $value;
    }

    public static function set($key, $value, $type = 'text')
    {
        \Illuminate\Support\Facades\Cache::forget("setting.{$key}");
        return self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type]
        );
    }
}
