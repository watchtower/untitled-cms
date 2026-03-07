<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use MongoDB\Laravel\Eloquent\Model;

class Banner extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'mongodb';

    protected $collection = 'banners';

    protected $fillable = [
        'title',
        'slug',
        'slides',
        'image_url', // Stores array of image URLs
        'alt_text',
        'link_url',
        'description',
        'order',
        'is_active',
        'start_at',
        'end_at',
    ];

    protected $casts = [
        'slug' => 'string',
        'slides' => 'array',
        'image_url' => 'array',
        'order' => 'integer',
        'is_active' => 'boolean',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', now());
            });
    }
}
