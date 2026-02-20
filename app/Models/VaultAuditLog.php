<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class VaultAuditLog extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'vault_audit_logs';

    protected $fillable = [
        'user_id',
        'event',
        'resource_type',
        'resource_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
