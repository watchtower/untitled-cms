<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'last_login_at',
        'subscription_level_id',
        'subscription_started_at',
        'subscription_expires_at',
        'subscription_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'subscription_started_at' => 'datetime',
            'subscription_expires_at' => 'datetime',
            'subscription_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function hasRole(string|array $roles): bool
    {
        if (is_string($roles)) {
            return $this->role === $roles;
        }

        return in_array($this->role, $roles);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(['super_admin', 'admin']);
    }

    public function isEditor(): bool
    {
        return $this->role === 'editor';
    }

    public function getRoleDisplayNameAttribute(): string
    {
        return match ($this->role) {
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'editor' => 'Editor',
            default => 'Unknown'
        };
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isInactive(): bool
    {
        return $this->status === 'inactive';
    }

    public function activate(): bool
    {
        return $this->update(['status' => 'active']);
    }

    public function deactivate(): bool
    {
        return $this->update(['status' => 'inactive']);
    }

    public function getStatusDisplayAttribute(): string
    {
        return ucfirst($this->status);
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    // Subscription relationships and methods

    /**
     * Get the subscription level for this user
     */
    public function subscriptionLevel(): BelongsTo
    {
        return $this->belongsTo(SubscriptionLevel::class);
    }

    /**
     * Get user's token balances
     */
    public function userTokens(): HasMany
    {
        return $this->hasMany(UserToken::class);
    }

    /**
     * Get user's counter balances
     */
    public function userCounters(): HasMany
    {
        return $this->hasMany(UserCounter::class);
    }

    /**
     * Get user's token transactions
     */
    public function tokenTransactions(): HasMany
    {
        return $this->hasMany(TokenTransaction::class);
    }

    /**
     * Get user's counter transactions
     */
    public function counterTransactions(): HasMany
    {
        return $this->hasMany(CounterTransaction::class);
    }

    /**
     * Check if user has an active subscription
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscription_active &&
               $this->subscription_level_id &&
               (! $this->subscription_expires_at || $this->subscription_expires_at->isFuture());
    }

    /**
     * Get user's current subscription level name
     */
    public function getSubscriptionLevelName(): string
    {
        return $this->subscriptionLevel?->name ?? 'No Subscription';
    }

    /**
     * Check if user is at a specific subscription level or higher
     */
    public function hasSubscriptionLevel(int $minLevel): bool
    {
        return $this->subscriptionLevel?->level >= $minLevel ?? false;
    }

    /**
     * Check if user is L33t Padawan (Level 1)
     */
    public function isPadawan(): bool
    {
        return $this->subscriptionLevel?->level === 1;
    }

    /**
     * Check if user is L33t Jedi (Level 2)
     */
    public function isJedi(): bool
    {
        return $this->subscriptionLevel?->level === 2;
    }

    /**
     * Check if user is L33t Master (Level 3)
     */
    public function isMaster(): bool
    {
        return $this->subscriptionLevel?->level === 3;
    }

    /**
     * Get token balance for a specific token type
     */
    public function getTokenBalance(string $tokenSlug): int
    {
        return $this->userTokens()
            ->whereHas('token', function ($query) use ($tokenSlug) {
                $query->where('slug', $tokenSlug);
            })
            ->first()?->balance ?? 0;
    }

    /**
     * Get L33t Bytes balance
     */
    public function getL33tBytesBalance(): int
    {
        return $this->getTokenBalance('l33t-bytes');
    }

    /**
     * Get resettable counter balance for a specific counter type
     */
    public function getCounterBalance(string $counterSlug): int
    {
        return $this->userCounters()
            ->whereHas('counter', function ($query) use ($counterSlug) {
                $query->where('slug', $counterSlug);
            })
            ->first()?->current_count ?? 0;
    }

    /**
     * Get Daily Bits balance
     */
    public function getDailyBitsBalance(): int
    {
        return $this->getCounterBalance('daily-bits');
    }

    /**
     * Get Weekly Power Bits balance
     */
    public function getWeeklyPowerBitsBalance(): int
    {
        return $this->getCounterBalance('weekly-power-bits');
    }
}
