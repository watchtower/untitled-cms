<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use MongoDB\Laravel\Eloquent\Model;

class VaultFolder extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'mongodb';

    protected $collection = 'vault_folders';

    protected $fillable = [
        'uuid',
        'parent_id',
        'name',
        'path_slug',
        'owner_id',
    ];

    public function parent()
    {
        return $this->belongsTo(VaultFolder::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(VaultFolder::class, 'parent_id');
    }

    public function files()
    {
        return $this->hasMany(VaultFile::class, 'folder_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function permissions()
    {
        return $this->hasMany(VaultFolderPermission::class, 'folder_id');
    }
}
