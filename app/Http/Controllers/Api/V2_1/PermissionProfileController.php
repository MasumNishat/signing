<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Models\Account;
use App\Models\PermissionProfile;
use App\Enums\Permission;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PermissionProfileController extends BaseController
{
    /**
     * List all permission profiles for an account.
     *
     * @param string $accountId
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(string $accountId)
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $profiles = PermissionProfile::where('account_id', $account->id)
            ->withCount('users')
            ->get();

        return $this->successResponse($profiles);
    }

    /**
     * Get a specific permission profile.
     *
     * @param string $accountId
     * @param string $profileId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $accountId, string $profileId)
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $profile = PermissionProfile::where('account_id', $account->id)
            ->where('permission_profile_id', $profileId)
            ->withCount('users')
            ->with('users:id,user_name,email,user_status')
            ->firstOrFail();

        return $this->successResponse($profile);
    }

    /**
     * Create a new permission profile.
     *
     * @param Request $request
     * @param string $accountId
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, string $accountId)
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'permission_profile_name' => 'required|string|max:255',
            'permissions' => 'required|array',
            'base_role' => 'nullable|string|in:' . implode(',', array_column(UserRole::cases(), 'value')),
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $permissions = $request->permissions;

            // If base_role is specified, start with those permissions
            if ($request->has('base_role')) {
                $role = UserRole::from($request->base_role);
                $permissions = array_merge($role->permissionsArray(), $permissions);
            }

            $profile = PermissionProfile::create([
                'account_id' => $account->id,
                'permission_profile_id' => 'perm_' . \Str::random(16),
                'permission_profile_name' => $request->permission_profile_name,
                'permissions' => $permissions,
            ]);

            return $this->successResponse($profile, 'Permission profile created successfully', 201);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create permission profile: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update a permission profile.
     *
     * @param Request $request
     * @param string $accountId
     * @param string $profileId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $accountId, string $profileId)
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $profile = PermissionProfile::where('account_id', $account->id)
            ->where('permission_profile_id', $profileId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'permission_profile_name' => 'sometimes|string|max:255',
            'permissions' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $profile->update($request->only(['permission_profile_name', 'permissions']));

            return $this->successResponse($profile, 'Permission profile updated successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update permission profile: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete a permission profile.
     *
     * @param string $accountId
     * @param string $profileId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $accountId, string $profileId)
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $profile = PermissionProfile::where('account_id', $account->id)
            ->where('permission_profile_id', $profileId)
            ->firstOrFail();

        // Check if any users are using this profile
        if ($profile->users()->count() > 0) {
            return $this->errorResponse(
                'Cannot delete permission profile that is assigned to users',
                400
            );
        }

        $profile->delete();

        return $this->successResponse(null, 'Permission profile deleted successfully');
    }

    /**
     * List all available permissions.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function availablePermissions()
    {
        $permissions = collect(Permission::cases())->map(function ($permission) {
            return [
                'value' => $permission->value,
                'label' => $permission->label(),
            ];
        });

        return $this->successResponse($permissions);
    }

    /**
     * List all available roles with their permissions.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function availableRoles()
    {
        $roles = collect(UserRole::cases())->map(function ($role) {
            return [
                'value' => $role->value,
                'label' => $role->label(),
                'permissions' => $role->permissions(),
            ];
        });

        return $this->successResponse($roles);
    }
}
