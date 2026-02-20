<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class VaultFolderPermission extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'vault_folder_permissions';

    protected $fillable = [
        'folder_id',
        'user_id',
        'role_id',
        'permission', // 'read', 'write', 'delete'
    ];

    public function folder()
    {
        return $this->belongsTo(VaultFolder::class, 'folder_id');
    }
}
