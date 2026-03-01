<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use MongoDB\Laravel\Eloquent\Model;

class Page extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'mongodb';

    protected $collection = 'pages';

    protected $fillable = [
        'title',
        'slug',
        'content',
        'status', // 'draft', 'published'
        'seo_title',
        'seo_description',
        'featured_image',
        'featured_images',
        'author_id',
        'published_at',
        'tags',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'featured_images' => 'array',
        'tags' => 'array',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
