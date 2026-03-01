<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId)->orderBy('last_active_at', 'desc');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
