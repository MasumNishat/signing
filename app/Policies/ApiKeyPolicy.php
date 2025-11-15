<?php

namespace App\Policies;

use App\Models\ApiKey;
use App\Models\User;
use App\Enums\Permission;

class ApiKeyPolicy
{
    /**
     * Determine if the user can view any API keys.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() ||
            $user->permissionProfile?->hasPermission(Permission::MANAGE_API_KEYS->value);
    }

    /**
     * Determine if the user can view the model.
     */
    public function view(User $user, ApiKey $apiKey): bool
    {
        // Users can view their own API keys
        if ($apiKey->user_id === $user->id) {
            return true;
        }

        // Must be in same account
        if ($user->account_id !== $apiKey->account_id) {
            return false;
        }

        return $user->isAdmin() ||
            $user->permissionProfile?->hasPermission(Permission::MANAGE_API_KEYS->value);
    }

    /**
     * Determine if the user can create API keys.
     */
    public function create(User $user): bool
    {
        return $user->permissionProfile?->hasPermission(Permission::USE_API->value) ?? false;
    }

    /**
     * Determine if the user can update the model.
     */
    public function update(User $user, ApiKey $apiKey): bool
    {
        // Users can update their own API keys
        if ($apiKey->user_id === $user->id) {
            return true;
        }

        // Must be in same account
        if ($user->account_id !== $apiKey->account_id) {
            return false;
        }

        return $user->isAdmin() ||
            $user->permissionProfile?->hasPermission(Permission::MANAGE_API_KEYS->value);
    }

    /**
     * Determine if the user can delete the model.
     */
    public function delete(User $user, ApiKey $apiKey): bool
    {
        return $this->update($user, $apiKey);
    }
}
