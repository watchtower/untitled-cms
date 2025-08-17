<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionLevel extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'level',
        'price',
        'features',
        'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get users with this subscription level
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Scope to get only active subscription levels
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by level
     */
    public function scopeByLevel($query)
    {
        return $query->orderBy('level');
    }

    /**
     * Get the route key for the model
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Check if this is a free tier
     */
    public function isFree(): bool
    {
        return $this->price == 0;
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        return $this->isFree() ? 'Free' : '$'.number_format($this->price, 2).'/month';
    }
}
