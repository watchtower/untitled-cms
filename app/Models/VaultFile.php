<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use MongoDB\Laravel\Eloquent\Model;

class VaultFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'mongodb';

    protected $collection = 'vault_files';

    protected $appends = ['url'];

    protected $fillable = [
        'uuid',
        'folder_id',
        'storage_path',
        'original_name',
        'mime_type',
        'extension',
        'size_bytes',
        'hash_sha256',
        'uploaded_by',
        'is_public',
        'validation_status',
        'width',
        'height',
        'alt_text',
        'optimized_path',
        'optimized_size',
        'is_optimized',
        'use_original',
        'moderation_reason',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'is_public' => 'boolean',
        'width' => 'integer',
        'height' => 'integer',
        'optimized_size' => 'integer',
        'is_optimized' => 'boolean',
        'use_original' => 'boolean',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(VaultFolder::class, 'folder_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        $servingPath = $this->resolveServingPath();

        if ($this->is_public && !$this->trashed()) {
            return Storage::disk('public')->url($servingPath);
        }

        // Fallback for private files and trashed files
        return route('vault.file.serve', ['uuid' => $this->uuid]);
    }

    public function resolveServingPath(): string
    {
        if ($this->is_optimized && !$this->use_original && $this->optimized_path) {
            return $this->optimized_path;
        }

        return $this->storage_path;
    }
}
