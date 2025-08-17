<?php

namespace App\Policies;

use App\Models\Page;
use App\Models\User;

class PagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin', 'editor']);
    }

    public function view(User $user, Page $page): bool
    {
        return $user->hasRole(['super_admin', 'admin', 'editor']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin', 'editor']);
    }

    public function update(User $user, Page $page): bool
    {
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        if ($user->hasRole('editor')) {
            return $page->status === 'draft' || $page->created_at->gt(now()->subDays(7));
        }

        return false;
    }

    public function delete(User $user, Page $page): bool
    {
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        if ($user->hasRole('editor')) {
            return $page->status === 'draft' && $page->created_at->gt(now()->subDays(1));
        }

        return false;
    }

    public function duplicate(User $user, Page $page): bool
    {
        return $user->hasRole(['super_admin', 'admin', 'editor']);
    }

    public function publish(User $user, Page $page): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function restore(User $user, Page $page): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function forceDelete(User $user, Page $page): bool
    {
        return $user->hasRole('super_admin');
    }
}
