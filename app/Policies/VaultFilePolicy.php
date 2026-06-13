<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VaultFile;
use App\Models\VaultFolder;
use Illuminate\Support\Facades\Gate;

class VaultFilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('media.view');
    }

    public function view(User $user, VaultFile $file): bool
    {
        if ($file->folder_id) {
            $folder = $file->folder;

            return $folder && Gate::forUser($user)->allows('view', $folder);
        }

        return $this->viewAny($user);
    }

    public function create(User $user, ?VaultFolder $folder = null): bool
    {
        if ($folder) {
            return Gate::forUser($user)->allows('create', $folder);
        }

        return $user->hasPermission('media.create');
    }

    public function update(User $user, VaultFile $file): bool
    {
        if ($file->folder_id) {
            $folder = $file->folder;

            return $folder && Gate::forUser($user)->allows('update', $folder);
        }

        return $user->hasPermission('media.edit');
    }

    public function delete(User $user, VaultFile $file): bool
    {
        if ($file->folder_id) {
            $folder = $file->folder;

            return $folder && Gate::forUser($user)->allows('delete', $folder);
        }

        return $user->hasPermission('media.delete');
    }

    /**
     * Restore a soft-deleted file — requires the same permission as delete.
     */
    public function restore(User $user, VaultFile $file): bool
    {
        return $this->delete($user, $file);
    }

    /**
     * Permanently purge a file from storage.
     * Requires the global media.delete permission regardless of folder context
     * because the action is irreversible — folder-level delete is insufficient.
     */
    public function forceDelete(User $user, VaultFile $file): bool
    {
        return $user->hasPermission('media.delete');
    }
}
