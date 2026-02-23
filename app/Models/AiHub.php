<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class AiHub extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'ai_hubs';

    protected $fillable = [
        'name',
        'api_key',
        'default_model',
        'image_model',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'api_key' => 'encrypted',
    ];
}
