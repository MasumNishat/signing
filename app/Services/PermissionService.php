<?php

namespace App\Services;

use App\Models\User;
use App\Models\PermissionProfile;
use App\Enums\Permission;
use App\Enums\UserRole;

class PermissionService
{
    /**
     * Check if a user has a specific permission.
     */
    public function hasPermission(User $user, Permission|string $permission): bool
    {
        // Admin users have all permissions
        if ($user->isAdmin()) {
            return true;
        }

        // Get permission string
        $permissionString = $permission instanceof Permission
            ? $permission->value
            : $permission;

        // Check user's permission profile
        if (!$user->permissionProfile) {
            return false;
        }

        return $user->permissionProfile->hasPermission($permissionString);
    }

    /**
     * Check if a user has any of the given permissions.
     */
    public function hasAnyPermission(User $user, array $permissions): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        foreach ($permissions as $permission) {
            if ($this->hasPermission($user, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a user has all of the given permissions.
     */
    public function hasAllPermissions(User $user, array $permissions): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        foreach ($permissions as $permission) {
            if (!$this->hasPermission($user, $permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create a permission profile for a role.
     */
    public function createProfileForRole(int $accountId, UserRole $role): PermissionProfile
    {
        return PermissionProfile::create([
            'account_id' => $accountId,
            'permission_profile_id' => 'perm_' . \Str::random(16),
            'permission_profile_name' => $role->label(),
            'permissions' => $role->permissionsArray(),
        ]);
    }

    /**
     * Assign a role to a user.
     */
    public function assignRole(User $user, UserRole $role): void
    {
        // Find or create permission profile for this role
        $profile = PermissionProfile::where('account_id', $user->account_id)
            ->where('permission_profile_name', $role->label())
            ->first();

        if (!$profile) {
            $profile = $this->createProfileForRole($user->account_id, $role);
        }

        $user->update(['permission_profile_id' => $profile->id]);
    }

    /**
     * Get all permissions for a user.
     */
    public function getUserPermissions(User $user): array
    {
        if ($user->isAdmin()) {
            return Permission::all();
        }

        if (!$user->permissionProfile) {
            return [];
        }

        $permissions = $user->permissionProfile->permissions ?? [];
        return array_keys(array_filter($permissions));
    }

    /**
     * Grant a permission to a user's profile.
     */
    public function grantPermission(User $user, Permission|string $permission): bool
    {
        if (!$user->permissionProfile) {
            return false;
        }

        $permissionString = $permission instanceof Permission
            ? $permission->value
            : $permission;

        $permissions = $user->permissionProfile->permissions ?? [];
        $permissions[$permissionString] = true;

        $user->permissionProfile->update(['permissions' => $permissions]);

        return true;
    }

    /**
     * Revoke a permission from a user's profile.
     */
    public function revokePermission(User $user, Permission|string $permission): bool
    {
        if (!$user->permissionProfile) {
            return false;
        }

        $permissionString = $permission instanceof Permission
            ? $permission->value
            : $permission;

        $permissions = $user->permissionProfile->permissions ?? [];
        $permissions[$permissionString] = false;

        $user->permissionProfile->update(['permissions' => $permissions]);

        return true;
    }
}
