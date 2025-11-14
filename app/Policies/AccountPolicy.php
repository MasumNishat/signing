<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;
use App\Enums\Permission;

class AccountPolicy
{
    /**
     * Determine if the user can view the model.
     */
    public function view(User $user, Account $account): bool
    {
        // Must be member of the account
        if ($user->account_id !== $account->id) {
            return false;
        }

        return $user->isAdmin() ||
            $user->permissionProfile?->hasPermission(Permission::VIEW_ACCOUNT->value);
    }

    /**
     * Determine if the user can update the model.
     */
    public function update(User $user, Account $account): bool
    {
        // Must be member of the account
        if ($user->account_id !== $account->id) {
            return false;
        }

        return $user->isAdmin() ||
            $user->permissionProfile?->hasPermission(Permission::MANAGE_ACCOUNT->value);
    }

    /**
     * Determine if the user can delete the model.
     */
    public function delete(User $user, Account $account): bool
    {
        // Only super admins can delete accounts
        return $user->isAdmin();
    }
}
