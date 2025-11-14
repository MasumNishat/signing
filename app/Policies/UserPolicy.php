<?php

namespace App\Policies;

use App\Models\User;
use App\Enums\Permission;

class UserPolicy
{
    /**
     * Determine if the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() ||
            $user->permissionProfile?->hasPermission(Permission::VIEW_USERS->value);
    }

    /**
     * Determine if the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Users can always view their own profile
        if ($user->id === $model->id) {
            return true;
        }

        // Must be in same account
        if ($user->account_id !== $model->account_id) {
            return false;
        }

        return $user->isAdmin() ||
            $user->permissionProfile?->hasPermission(Permission::VIEW_USERS->value);
    }

    /**
     * Determine if the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() ||
            $user->permissionProfile?->hasPermission(Permission::CREATE_USERS->value);
    }

    /**
     * Determine if the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Users can update their own profile (limited fields)
        if ($user->id === $model->id) {
            return true;
        }

        // Must be in same account
        if ($user->account_id !== $model->account_id) {
            return false;
        }

        return $user->isAdmin() ||
            $user->permissionProfile?->hasPermission(Permission::MANAGE_USERS->value);
    }

    /**
     * Determine if the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Cannot delete yourself
        if ($user->id === $model->id) {
            return false;
        }

        // Must be in same account
        if ($user->account_id !== $model->account_id) {
            return false;
        }

        return $user->isAdmin() ||
            $user->permissionProfile?->hasPermission(Permission::DELETE_USERS->value);
    }

    /**
     * Determine if the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return $this->delete($user, $model);
    }

    /**
     * Determine if the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->isAdmin();
    }
}
