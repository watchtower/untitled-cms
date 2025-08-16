<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function update(User $user, User $model): bool
    {
        // Super admins can edit anyone
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admins can edit editors and other admins (but not super admins)
        if ($user->isAdmin()) {
            return !$model->isSuperAdmin();
        }

        return false;
    }

    public function delete(User $user, User $model): bool
    {
        // Users cannot delete themselves
        if ($user->id === $model->id) {
            return false;
        }

        // Super admins can delete anyone
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admins can delete editors and other admins (but not super admins)
        if ($user->isAdmin()) {
            return !$model->isSuperAdmin();
        }

        return false;
    }

    public function restore(User $user, User $model): bool
    {
        return $this->delete($user, $model);
    }

    public function forceDelete(User $user, User $model): bool
    {
        // Only super admins can permanently delete users
        if ($user->id === $model->id) {
            return false;
        }

        return $user->isSuperAdmin();
    }

    public function assignRole(User $user, string $role): bool
    {
        // Super admins can assign any role
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Admins can assign editor and admin roles (but not super admin)
        if ($user->isAdmin()) {
            return in_array($role, ['admin', 'editor']);
        }

        return false;
    }

    public function activate(User $user, User $model): bool
    {
        return $this->update($user, $model);
    }

    public function deactivate(User $user, User $model): bool
    {
        // Users cannot deactivate themselves
        if ($user->id === $model->id) {
            return false;
        }

        return $this->update($user, $model);
    }

    public function verifyEmail(User $user, User $model): bool
    {
        return $this->update($user, $model);
    }
}