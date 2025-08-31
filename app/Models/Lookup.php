<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lookup extends Model
{
    protected $fillable = [
        'user_id',
        'query',
        'type',
        'results',
        'status',
        'error_message',
        'response_time_ms',
        'source_ip',
        'user_agent',
    ];

    protected $casts = [
        'results' => 'array',
    ];

    /**
     * Get the user that owns this lookup
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if lookup was successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Get formatted results for display
     */
    public function getFormattedResults(): array
    {
        if (!$this->results) {
            return [];
        }

        return $this->results;
    }

    /**
     * Scope for successful lookups
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope for failed lookups
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope by lookup type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for recent lookups
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
