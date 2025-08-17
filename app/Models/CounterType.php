<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CounterType extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'default_allocation',
        'reset_frequency',
        'icon',
        'color',
        'is_active',
        'last_reset_at',
    ];

    protected $casts = [
        'default_allocation' => 'integer',
        'is_active' => 'boolean',
        'last_reset_at' => 'datetime',
    ];

    /**
     * Get the user counters for this counter type
     */
    public function userCounters(): HasMany
    {
        return $this->hasMany(UserCounter::class, 'counter_type_id');
    }

    /**
     * Get the counter transactions for this counter type
     */
    public function counterTransactions(): HasMany
    {
        return $this->hasMany(CounterTransaction::class, 'counter_id');
    }

    /**
     * Scope to get only active counter types
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get counter types by reset frequency
     */
    public function scopeByFrequency($query, string $frequency)
    {
        return $query->where('reset_frequency', $frequency);
    }

    /**
     * Get total allocation across all users
     */
    public function getTotalAllocation(): int
    {
        return $this->userCounters()->sum('current_count');
    }

    /**
     * Get count of users with this counter
     */
    public function getUserCount(): int
    {
        return $this->userCounters()->count();
    }

    /**
     * Get recent transaction count
     */
    public function getRecentTransactionCount(int $days = 7): int
    {
        return $this->counterTransactions()
            ->where('created_at', '>=', now()->subDays($days))
            ->count();
    }
}