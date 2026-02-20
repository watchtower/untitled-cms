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
}
