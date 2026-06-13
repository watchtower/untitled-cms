<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class AiHub extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'ai_hubs';

    protected $fillable = [
        'name',
        'default_model',
        'image_model',
        'is_active',
        'monthly_quota',
        'monthly_usage',
        // 'api_key' intentionally excluded — set explicitly to prevent mass assignment
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'api_key' => 'encrypted',
    ];
}
