<?php

namespace App\Policies;

use App\Models\User;

/**
 * No before() here, unlike the other policies: an admin must not be able to
 * delete their own account or change their own role (and lock everyone out).
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function updateRole(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->isNot($model);
    }

    /**
     * Only clients may delete their own account: staff comments are part of
     * the tickets' history and must stay.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->is($model) && $user->isClient();
    }
}
