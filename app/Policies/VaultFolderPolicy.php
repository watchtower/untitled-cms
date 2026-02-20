<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VaultFolder;
use App\Models\VaultFolderPermission;

class VaultFolderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->hasPermission('media.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, VaultFolder $folder): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // Check explicit folder permission
        $hasPermission = VaultFolderPermission::where('folder_id', $folder->id)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereIn('role_id', $user->roles->pluck('id'));
            })
            ->whereIn('permission', ['read', 'write', 'delete'])
            ->exists();

        // If no specific permission is set, fall back to global role permission
        if (! $hasPermission) {
            // Check if any permission exists for this folder at all.
            // If permissions exist but user doesn't have them -> Deny.
            // If no permissions exist for this folder -> Allow based on global role.
            $folderHasPermissions = VaultFolderPermission::where('folder_id', $folder->id)->exists();

            if ($folderHasPermissions) {
                return false;
            }

            return $user->hasPermission('media.view');
        }

        return $hasPermission;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, ?VaultFolder $parent = null): bool
    {
        if ($parent) {
            // Check write permission on parent
            return $this->update($user, $parent);
        }

        return $user->hasRole('Super Admin') || $user->hasPermission('media.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, VaultFolder $folder): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        $hasPermission = VaultFolderPermission::where('folder_id', $folder->id)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereIn('role_id', $user->roles->pluck('id'));
            })
            ->whereIn('permission', ['write', 'delete'])
            ->exists();

        if (! $hasPermission) {
            $folderHasPermissions = VaultFolderPermission::where('folder_id', $folder->id)->exists();
            if ($folderHasPermissions) {
                return false;
            }

            return $user->hasPermission('media.edit');
        }

        return $hasPermission;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, VaultFolder $folder): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        $hasPermission = VaultFolderPermission::where('folder_id', $folder->id)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereIn('role_id', $user->roles->pluck('id'));
            })
            ->where('permission', 'delete')
            ->exists();

        if (! $hasPermission) {
            $folderHasPermissions = VaultFolderPermission::where('folder_id', $folder->id)->exists();
            if ($folderHasPermissions) {
                return false;
            }

            return $user->hasPermission('media.delete');
        }

        return $hasPermission;
    }
}
