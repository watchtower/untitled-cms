<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property SubscriptionLevel|null $subscriptionLevel
 */
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
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // When a user's role is updated, clear their session if they are currently authenticated
        static::updated(function ($user) {
            if ($user->wasChanged('role')) {
                // If the updated user is currently authenticated, we need to refresh their session
                $currentUser = auth()->user();
                if ($currentUser && $currentUser->id === $user->id) {
                    // Re-authenticate the user to refresh their session data
                    auth()->login($user);
                }
            }
        });
    }

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
     * Get user's site monitors
     */
    public function siteMonitors(): HasMany
    {
        return $this->hasMany(SiteMonitor::class);
    }

    /**
     * Get user's lookups
     */
    public function lookups(): HasMany
    {
        return $this->hasMany(Lookup::class);
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
     * Check if user is on the Starter plan
     */
    public function isStarter(): bool
    {
        return $this->subscriptionLevel?->slug === 'starter';
    }

    /**
     * Check if user is on the Pro plan
     */
    public function isPro(): bool
    {
        return $this->subscriptionLevel?->slug === 'pro';
    }

    /**
     * Check if user is on the Elite plan
     */
    public function isElite(): bool
    {
        return $this->subscriptionLevel?->slug === 'elite';
    }

    /**
     * Get token balance for a specific token type
     */
    public function getTokenBalance(string $tokenSlug): int
    {
        $userToken = $this->userTokens()
            ->whereHas('token', function ($query) use ($tokenSlug) {
                $query->where('slug', $tokenSlug);
            })
            ->first();

        return $userToken?->balance ?? 0;
    }

    /**
     * Get Permanent Tokens balance
     */
    public function getPermanentTokenBalance(): int
    {
        return $this->getTokenBalance('permanent-tokens');
    }

    /**
     * Get resettable counter balance for a specific counter type
     */
    public function getCounterBalance(string $counterSlug): int
    {
        $userCounter = $this->userCounters()
            ->whereHas('counterType', function ($query) use ($counterSlug) {
                $query->where('slug', $counterSlug);
            })
            ->first();

        return $userCounter?->current_count ?? 0;
    }

    /**
     * Get Monthly Credits balance
     */
    public function getMonthlyCreditsBalance(): int
    {
        return $this->getCounterBalance('monthly-credits');
    }

    /**
     * Get Bytes balance (alias for permanent tokens)
     */
    public function getBytesBalance(): int
    {
        return $this->getPermanentTokenBalance();
    }

    /**
     * Get daily bits balance (alias for monthly credits)
     */
    public function getDailyBitsBalance(): int
    {
        return $this->getMonthlyCreditsBalance();
    }

    /**
     * Get weekly power bits balance (alias for monthly credits)
     */
    public function getWeeklyPowerBitsBalance(): int
    {
        return $this->getMonthlyCreditsBalance();
    }
}