<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCounter extends Model
{
    protected $table = 'user_counters';

    protected $fillable = [
        'user_id',
        'counter_type_id',
        'current_count',
        'last_reset_at',
    ];

    protected $casts = [
        'current_count' => 'integer',
        'last_reset_at' => 'datetime',
    ];

    /**
     * Get the user that owns this counter
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the counter type
     */
    public function counterType(): BelongsTo
    {
        return $this->belongsTo(CounterType::class);
    }

    /**
     * Compatibility method for old relationship name
     */
    public function counter(): BelongsTo
    {
        return $this->counterType();
    }

    /**
     * Add to counter and log transaction
     */
    public function addCount(int $amount, string $reason, ?User $admin = null, string $type = 'manual'): bool
    {
        $countBefore = $this->current_count;
        $this->current_count += $amount;
        $countAfter = $this->current_count;
        
        if ($this->save()) {
            // Log the transaction
            CounterTransaction::create([
                'user_id' => $this->user_id,
                'admin_id' => $admin?->id,
                'counter_id' => $this->counter_type_id,
                'count_change' => $amount,
                'count_before' => $countBefore,
                'count_after' => $countAfter,
                'reason' => $reason,
                'type' => $type,
                'created_at' => now(),
            ]);
            
            return true;
        }
        
        return false;
    }

    /**
     * Deduct from counter and log transaction
     */
    public function deductCount(int $amount, string $reason, ?User $admin = null, string $type = 'usage'): bool
    {
        if ($this->current_count < $amount) {
            return false; // Insufficient balance
        }
        
        $countBefore = $this->current_count;
        $this->current_count -= $amount;
        $countAfter = $this->current_count;
        
        if ($this->save()) {
            // Log the transaction
            CounterTransaction::create([
                'user_id' => $this->user_id,
                'admin_id' => $admin?->id,
                'counter_id' => $this->counter_type_id,
                'count_change' => -$amount,
                'count_before' => $countBefore,
                'count_after' => $countAfter,
                'reason' => $reason,
                'type' => $type,
                'created_at' => now(),
            ]);
            
            return true;
        }
        
        return false;
    }

    /**
     * Set counter to specific amount and log transaction
     */
    public function setCount(int $newCount, string $reason, ?User $admin = null): bool
    {
        $countBefore = $this->current_count;
        $countChange = $newCount - $countBefore;
        $this->current_count = $newCount;
        $this->last_reset_at = now();
        
        if ($this->save()) {
            // Log the transaction
            CounterTransaction::create([
                'user_id' => $this->user_id,
                'admin_id' => $admin?->id,
                'counter_id' => $this->counter_type_id,
                'count_change' => $countChange,
                'count_before' => $countBefore,
                'count_after' => $newCount,
                'reason' => $reason,
                'type' => 'admin_set',
                'created_at' => now(),
            ]);
            
            return true;
        }
        
        return false;
    }
}