<?php

namespace App\Traits;

trait HasRoles
{
    /**
     * Check if the user has a specific role by slug.
     * Uses the in-memory roles collection to avoid an extra DB query when roles are already loaded.
     */
    public function hasRole(string $roleSlug): bool
    {
        return $this->roles->contains('slug', $roleSlug);
    }
}
