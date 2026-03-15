<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VaultFolder;
use App\Models\VaultFolderPermission;

class VaultFolderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('media.view');
    }

    public function view(User $user, VaultFolder $folder): bool
    {
        $hasPermission = VaultFolderPermission::where('folder_id', $folder->id)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereIn('role_id', $user->roles->pluck('id'));
            })
            ->whereIn('permission', ['read', 'write', 'delete'])
            ->exists();

        if (! $hasPermission) {
            $folderHasPermissions = VaultFolderPermission::where('folder_id', $folder->id)->exists();
            if ($folderHasPermissions) {
                return false;
            }

            return $user->hasPermission('media.view');
        }

        return $hasPermission;
    }

    public function create(User $user, ?VaultFolder $parent = null): bool
    {
        if ($parent) {
            return $this->update($user, $parent);
        }

        return $user->hasPermission('media.create');
    }

    public function update(User $user, VaultFolder $folder): bool
    {
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

    public function delete(User $user, VaultFolder $folder): bool
    {
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
