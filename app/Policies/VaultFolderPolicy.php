<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VaultFolder;
use App\Models\VaultFolderPermission;
use Illuminate\Support\Collection;

class VaultFolderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('media.view');
    }

    public function view(User $user, VaultFolder $folder): bool
    {
        $permissions = $this->loadPermissions($folder);

        if ($permissions->isEmpty()) {
            return $user->hasPermission('media.view');
        }

        return $this->userMatchesPermission($user, $permissions, ['read', 'write', 'delete']);
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
        $permissions = $this->loadPermissions($folder);

        if ($permissions->isEmpty()) {
            return $user->hasPermission('media.edit');
        }

        return $this->userMatchesPermission($user, $permissions, ['write', 'delete']);
    }

    public function delete(User $user, VaultFolder $folder): bool
    {
        $permissions = $this->loadPermissions($folder);

        if ($permissions->isEmpty()) {
            return $user->hasPermission('media.delete');
        }

        return $this->userMatchesPermission($user, $permissions, ['delete']);
    }

    /**
     * Return the folder's permissions collection.
     * Uses the already-loaded relation (eager-loaded by VaultFolderController::list)
     * to avoid an extra query per folder in list views.
     */
    private function loadPermissions(VaultFolder $folder): Collection
    {
        if ($folder->relationLoaded('permissions')) {
            return $folder->permissions;
        }

        return VaultFolderPermission::where('folder_id', $folder->id)->get();
    }

    /**
     * Check whether the user (by ID or any of their role IDs) matches at least
     * one permission entry with the required permission type.
     */
    private function userMatchesPermission(User $user, Collection $permissions, array $allowed): bool
    {
        $roleIds = $user->roles->pluck('id')->map(fn ($id) => (string) $id);

        return $permissions->contains(function (VaultFolderPermission $p) use ($user, $roleIds, $allowed) {
            return in_array($p->permission, $allowed)
                && ((string) $p->user_id === (string) $user->id || $roleIds->contains((string) $p->role_id));
        });
    }
}
