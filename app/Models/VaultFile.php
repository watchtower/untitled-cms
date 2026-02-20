<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
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
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'is_public' => 'boolean',
        'width' => 'integer',
        'height' => 'integer',
    ];

    public function folder()
    {
        return $this->belongsTo(VaultFolder::class, 'folder_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute()
    {
        if ($this->is_public && ! $this->trashed()) {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($this->storage_path);
        }

        // Fallback for private files and trashed files
        return route('vault.file.serve', ['uuid' => $this->uuid]);
    }
}
