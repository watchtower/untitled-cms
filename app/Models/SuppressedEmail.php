<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class SuppressedEmail extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'suppressed_emails';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'reason', // bounced_hard, complained, unsubscribed
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Check if an email is suppressed.
     */
    public static function isSuppressed(string $email): bool
    {
        return self::where('email', strtolower($email))->exists();
    }
}
