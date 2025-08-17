<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CounterTransaction extends Model
{
    public $timestamps = false; // We only use created_at

    protected $fillable = [
        'user_id',
        'admin_id',
        'counter_id',
        'count_change',
        'count_before',
        'count_after',
        'reason',
        'type',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'count_change' => 'integer',
        'count_before' => 'integer',
        'count_after' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the user this transaction belongs to
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who performed this transaction
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Get the counter type for this transaction
     */
    public function counterType(): BelongsTo
    {
        return $this->belongsTo(CounterType::class, 'counter_id');
    }

    /**
     * Scope to get recent transactions
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to get transactions by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get formatted count change with sign
     */
    public function getFormattedCountChangeAttribute(): string
    {
        return ($this->count_change >= 0 ? '+' : '') . number_format($this->count_change);
    }

    /**
     * Check if this was a deduction
     */
    public function isDeduction(): bool
    {
        return $this->count_change < 0;
    }

    /**
     * Check if this was an addition
     */
    public function isAddition(): bool
    {
        return $this->count_change > 0;
    }
}
