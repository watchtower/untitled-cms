<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use MongoDB\Laravel\Eloquent\Model;

class ChatSession extends Model
{
    use SoftDeletes;

    protected $connection = 'mongodb';
    protected $collection = 'chat_sessions';

    protected $fillable = [
        'user_id',
        'title',
        'messages',
        'last_active_at',
    ];

    protected $casts = [
        'messages' => 'array',
        'last_active_at' => 'datetime',
    ];

    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId)->orderBy('last_active_at', 'desc');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
