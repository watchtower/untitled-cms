<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserToken extends Model
{
    protected $fillable = [
        'user_id',
        'token_id',
        'balance',
    ];

    protected $casts = [
        'balance' => 'integer',
    ];

    /**
     * Get the user that owns this token balance
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the token type
     */
    public function token(): BelongsTo
    {
        return $this->belongsTo(Token::class);
    }

    /**
     * Add tokens to balance and log transaction
     */
    public function addTokens(int $amount, string $reason, ?User $admin = null, string $type = 'manual'): bool
    {
        $balanceBefore = $this->balance;
        $this->balance += $amount;
        $balanceAfter = $this->balance;
        
        if ($this->save()) {
            // Log the transaction
            TokenTransaction::create([
                'user_id' => $this->user_id,
                'admin_id' => $admin?->id,
                'token_id' => $this->token_id,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reason' => $reason,
                'type' => $type,
                'created_at' => now(),
            ]);
            
            return true;
        }
        
        return false;
    }

    /**
     * Deduct tokens from balance and log transaction
     */
    public function deductTokens(int $amount, string $reason, ?User $admin = null, string $type = 'usage'): bool
    {
        if ($this->balance < $amount) {
            return false; // Insufficient balance
        }
        
        $balanceBefore = $this->balance;
        $this->balance -= $amount;
        $balanceAfter = $this->balance;
        
        if ($this->save()) {
            // Log the transaction
            TokenTransaction::create([
                'user_id' => $this->user_id,
                'admin_id' => $admin?->id,
                'token_id' => $this->token_id,
                'amount' => -$amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reason' => $reason,
                'type' => $type,
                'created_at' => now(),
            ]);
            
            return true;
        }
        
        return false;
    }

    /**
     * Set balance to specific amount and log transaction
     */
    public function setBalance(int $newBalance, string $reason, ?User $admin = null): bool
    {
        $balanceBefore = $this->balance;
        $amount = $newBalance - $balanceBefore;
        $this->balance = $newBalance;
        
        if ($this->save()) {
            // Log the transaction
            TokenTransaction::create([
                'user_id' => $this->user_id,
                'admin_id' => $admin?->id,
                'token_id' => $this->token_id,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $newBalance,
                'reason' => $reason,
                'type' => 'admin_set',
                'created_at' => now(),
            ]);
            
            return true;
        }
        
        return false;
    }
}
