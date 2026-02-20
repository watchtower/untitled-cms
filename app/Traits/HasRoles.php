<?php

namespace App\Traits;

use App\Models\Role;
use Illuminate\Support\Facades\Cache;

trait HasRoles
{
    /**
     * Check if the user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->getCachedPermissions();

        return in_array($permission, $permissions);
    }

    /**
     * Check if the user has a specific role.
     */
    public function hasRole(string $roleSlug): bool
    {
        return $this->roles()->where('slug', $roleSlug)->exists();
    }

    /**
     * Get all permissions from all assigned roles.
     */
    public function getCachedPermissions(): array
    {
        // Cache key per user
        $cacheKey = 'user_permissions_'.$this->id;

        return Cache::remember($cacheKey, 60, function () {
            return $this->roles->pluck('permissions')->flatten()->unique()->toArray();
        });
    }

    /**
     * Define the relationship to roles.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}
