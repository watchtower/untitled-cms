<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VaultFile;
use App\Models\VaultFolder;
use Illuminate\Support\Facades\Gate;

class VaultFilePolicy
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
    public function view(User $user, VaultFile $file): bool
    {
        if ($file->folder_id) {
            $folder = $file->folder;

            return $folder && Gate::forUser($user)->allows('view', $folder);
        }

        return $this->viewAny($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, ?VaultFolder $folder = null): bool
    {
        if ($folder) {
            return Gate::forUser($user)->allows('create', $folder);
        }

        return $user->hasRole('Super Admin') || $user->hasPermission('media.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, VaultFile $file): bool
    {
        if ($file->folder_id) {
            $folder = $file->folder;

            return $folder && Gate::forUser($user)->allows('update', $folder);
        }

        return $user->hasRole('Super Admin') || $user->hasPermission('media.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, VaultFile $file): bool
    {
        if ($file->folder_id) {
            $folder = $file->folder;

            return $folder && Gate::forUser($user)->allows('delete', $folder);
        }

        return $user->hasRole('Super Admin') || $user->hasPermission('media.delete');
    }
}
