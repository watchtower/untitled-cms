<?php

namespace App\Traits;

use App\Models\Role;

trait HasRoles
{
    /**
     * Check if the user has a specific role by slug.
     */
    public function hasRole(string $roleSlug): bool
    {
        return $this->roles()->where('slug', $roleSlug)->exists();
    }

    /**
     * Define the relationship to roles.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
}
