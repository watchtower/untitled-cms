<?php

namespace App\Policies;

use App\Models\User;

class EmailLogPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('email_logs.view');
    }
}
