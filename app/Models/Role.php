<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Role extends Model
{
    protected $connection = 'mongodb';

    protected $fillable = ['name', 'slug', 'permissions', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // public function permissions()
    // {
    //    return $this->embedsMany(Permission::class);
    // }

    public function syncPermissions(array $permissions)
    {
        $this->permissions = $permissions;
        $this->save();
    }

    // Check if role has specific permission
    public function hasPermission($permission)
    {
        return in_array($permission, $this->permissions ?? []);
    }

    // Get available permissions for the system
    public static function availablePermissions()
    {
        return [
            'pages.view',
            'pages.create',
            'pages.edit',
            'pages.delete',
            'banners.view',
            'banners.create',
            'banners.edit',
            'banners.delete',
            'media.view',
            'media.upload',
            'media.delete',
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
        ];
    }
}
