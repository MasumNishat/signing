<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Models\Account;
use App\Models\User;
use App\Models\PermissionProfile;
use App\Services\PermissionService;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserPermissionController extends BaseController
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Get user's permissions.
     *
     * @param string $accountId
     * @param string $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $accountId, string $userId)
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $user = User::where('account_id', $account->id)
            ->where('id', $userId)
            ->with('permissionProfile')
            ->firstOrFail();

        $permissions = $this->permissionService->getUserPermissions($user);

        return $this->successResponse([
            'user_id' => $user->id,
            'user_name' => $user->user_name,
            'email' => $user->email,
            'is_admin' => $user->is_admin,
            'permission_profile' => $user->permissionProfile,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Assign a role to a user.
     *
     * @param Request $request
     * @param string $accountId
     * @param string $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignRole(Request $request, string $accountId, string $userId)
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $user = User::where('account_id', $account->id)
            ->where('id', $userId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'role' => 'required|string|in:' . implode(',', array_column(UserRole::cases(), 'value')),
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $role = UserRole::from($request->role);
            $this->permissionService->assignRole($user, $role);

            $user->refresh()->load('permissionProfile');

            return $this->successResponse([
                'user' => $user,
                'role' => $role->value,
                'permissions' => $this->permissionService->getUserPermissions($user),
            ], 'Role assigned successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to assign role: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Assign a permission profile to a user.
     *
     * @param Request $request
     * @param string $accountId
     * @param string $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignProfile(Request $request, string $accountId, string $userId)
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $user = User::where('account_id', $account->id)
            ->where('id', $userId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'permission_profile_id' => 'required|string|exists:permission_profiles,permission_profile_id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $profile = PermissionProfile::where('permission_profile_id', $request->permission_profile_id)
                ->where('account_id', $account->id)
                ->firstOrFail();

            $user->update(['permission_profile_id' => $profile->id]);
            $user->refresh()->load('permissionProfile');

            return $this->successResponse([
                'user' => $user,
                'permissions' => $this->permissionService->getUserPermissions($user),
            ], 'Permission profile assigned successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to assign permission profile: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Check if user has specific permissions.
     *
     * @param Request $request
     * @param string $accountId
     * @param string $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkPermissions(Request $request, string $accountId, string $userId)
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $user = User::where('account_id', $account->id)
            ->where('id', $userId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'permissions' => 'required|array',
            'permissions.*' => 'required|string',
            'require_all' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $requireAll = $request->input('require_all', false);
        $permissions = $request->permissions;

        $hasPermission = $requireAll
            ? $this->permissionService->hasAllPermissions($user, $permissions)
            : $this->permissionService->hasAnyPermission($user, $permissions);

        $results = [];
        foreach ($permissions as $permission) {
            $results[$permission] = $this->permissionService->hasPermission($user, $permission);
        }

        return $this->successResponse([
            'user_id' => $user->id,
            'has_permission' => $hasPermission,
            'require_all' => $requireAll,
            'individual_results' => $results,
        ]);
    }
}
