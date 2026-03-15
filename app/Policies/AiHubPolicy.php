<?php

namespace App\Policies;

use App\Models\AiHub;
use App\Models\User;

class AiHubPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('ai-integrations.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AiHub $aiHub): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AiHub $aiHub): bool
    {
        return $user->hasPermission('ai-integrations.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AiHub $aiHub): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AiHub $aiHub): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AiHub $aiHub): bool
    {
        return false;
    }
}
