<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Token extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'default_count',
        'icon',
        'color',
        'is_active',
    ];

    protected $casts = [
        'default_count' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get user tokens for this token type
     */
    public function userTokens(): HasMany
    {
        return $this->hasMany(UserToken::class);
    }

    /**
     * Get token transactions for this token type
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(TokenTransaction::class);
    }

    /**
     * Scope to get only active tokens
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the route key for the model
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Get total tokens in circulation
     */
    public function getTotalInCirculation(): int
    {
        return $this->userTokens()->sum('balance');
    }

    /**
     * Get total number of users with this token
     */
    public function getUserCount(): int
    {
        return $this->userTokens()->where('balance', '>', 0)->count();
    }
}
