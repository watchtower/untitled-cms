<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Page extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'title',
        'slug',
        'summary',
        'content',
        'status',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'meta_keywords' => 'array',
        'published_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
        });

        static::updating(function ($page) {
            if ($page->isDirty('title') && empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get all of the categories for the page.
     */
    public function categories()
    {
        return $this->morphToMany(Category::class, 'categorizable');
    }

    /**
     * Get all of the tags for the page.
     */
    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function contentBlocks(): HasMany
    {
        return $this->hasMany(ContentBlock::class)->ordered();
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
