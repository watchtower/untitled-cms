<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteMonitor extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'url',
        'status',
        'check_interval_minutes',
        'response_data',
        'response_time_ms',
        'status_code',
        'last_checked_at',
        'last_success_at',
        'last_failure_at',
        'failure_reason',
        'consecutive_failures',
        'notifications_enabled',
    ];

    protected $casts = [
        'response_data' => 'array',
        'last_checked_at' => 'datetime',
        'last_success_at' => 'datetime',
        'last_failure_at' => 'datetime',
        'notifications_enabled' => 'boolean',
    ];

    /**
     * Get the user that owns this monitor
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if monitor is healthy (no recent failures)
     */
    public function isHealthy(): bool
    {
        return $this->status === 'active' && $this->consecutive_failures < 3;
    }

    /**
     * Check if monitor needs attention (has recent failures)
     */
    public function needsAttention(): bool
    {
        return $this->status === 'failed' || $this->consecutive_failures >= 3;
    }

    /**
     * Get uptime percentage (simplified calculation)
     */
    public function getUptimePercentage(int $days = 30): float
    {
        // This would typically calculate based on historical check data
        // For now, return a simple calculation based on consecutive failures
        if ($this->consecutive_failures === 0) {
            return 100.0;
        }
        
        return max(0, 100 - ($this->consecutive_failures * 10));
    }

    /**
     * Scope for active monitors
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for failed monitors
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for monitors that need checking
     */
    public function scopeNeedsCheck($query)
    {
        return $query->where('status', 'active')
                    ->where(function ($q) {
                        $q->whereNull('last_checked_at')
                          ->orWhereRaw('last_checked_at < DATE_SUB(NOW(), INTERVAL check_interval_minutes MINUTE)');
                    });
    }
}
