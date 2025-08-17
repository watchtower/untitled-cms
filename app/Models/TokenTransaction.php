<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TokenTransaction extends Model
{
    public $timestamps = false; // We only use created_at

    protected $fillable = [
        'user_id',
        'admin_id',
        'token_id',
        'amount',
        'balance_before',
        'balance_after',
        'reason',
        'type',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_before' => 'integer',
        'balance_after' => 'integer',
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
     * Get the token type for this transaction
     */
    public function token(): BelongsTo
    {
        return $this->belongsTo(Token::class);
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
     * Get formatted amount with sign
     */
    public function getFormattedAmountAttribute(): string
    {
        return ($this->amount >= 0 ? '+' : '').number_format($this->amount);
    }

    /**
     * Check if this was a deduction
     */
    public function isDeduction(): bool
    {
        return $this->amount < 0;
    }

    /**
     * Check if this was an addition
     */
    public function isAddition(): bool
    {
        return $this->amount > 0;
    }
}
