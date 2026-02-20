<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class Redirect extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';

    protected $collection = 'redirects';

    protected $fillable = [
        'from_path',
        'to_path',
        'type', // 301, 302, etc.
        'active',
    ];

    protected $attributes = [
        'type' => 301,
        'active' => true,
    ];
}
