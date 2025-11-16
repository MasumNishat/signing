<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Http\Controllers\Controller;
use App\Services\GroupService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class UserGroupController extends Controller
{
    protected GroupService $groupService;

    public function __construct(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }

    /**
     * Get a list of user groups for the account.
     *
     * GET /v2.1/accounts/{accountId}/groups
     *
     * @param Request $request
     * @param string $accountId
     * @return JsonResponse
     */
    public function index(Request $request, string $accountId): JsonResponse
    {
        try {
            $groups = $this->groupService->getUserGroups((int) $accountId);

            return $this->success([
                'groups' => $groups->map(function ($group) {
                    return $this->formatUserGroupResponse($group);
                })->toArray(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get a specific user group.
     *
     * GET /v2.1/accounts/{accountId}/groups/{groupId}
     *
     * @param Request $request
     * @param string $accountId
     * @param string $groupId
     * @return JsonResponse
     */
    public function show(Request $request, string $accountId, string $groupId): JsonResponse
    {
        try {
            $group = $this->groupService->getUserGroup((int) $accountId, $groupId);

            if (!$group) {
                return $this->notFound('User group not found');
            }

            return $this->success($this->formatUserGroupResponse($group));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Create user groups (bulk create).
     *
     * POST /v2.1/accounts/{accountId}/groups
     *
     * @param Request $request
     * @param string $accountId
     * @return JsonResponse
     */
    public function store(Request $request, string $accountId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'groups' => 'required|array',
            'groups.*.group_name' => 'required|string|max:255',
            'groups.*.group_type' => 'nullable|string|in:admin_group,custom_group,everyone_group',
            'groups.*.permission_profile_id' => 'nullable|integer|exists:permission_profiles,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $userId = auth()->id();
            $groups = $this->groupService->createUserGroups(
                (int) $accountId,
                $request->input('groups'),
                $userId
            );

            return $this->created([
                'groups' => $groups->map(function ($group) {
                    return $this->formatUserGroupResponse($group);
                })->toArray(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Update user groups (bulk update).
     *
     * PUT /v2.1/accounts/{accountId}/groups
     *
     * @param Request $request
     * @param string $accountId
     * @return JsonResponse
     */
    public function update(Request $request, string $accountId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'groups' => 'required|array',
            'groups.*.group_id' => 'required|string',
            'groups.*.group_name' => 'nullable|string|max:255',
            'groups.*.group_type' => 'nullable|string|in:admin_group,custom_group,everyone_group',
            'groups.*.permission_profile_id' => 'nullable|integer|exists:permission_profiles,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $userId = auth()->id();
            $updatedGroups = $this->groupService->updateUserGroups(
                (int) $accountId,
                $request->input('groups'),
                $userId
            );

            return $this->success([
                'groups' => $updatedGroups->map(function ($group) {
                    return $this->formatUserGroupResponse($group);
                })->toArray(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Delete user groups (bulk delete).
     *
     * DELETE /v2.1/accounts/{accountId}/groups
     *
     * @param Request $request
     * @param string $accountId
     * @return JsonResponse
     */
    public function destroy(Request $request, string $accountId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'group_ids' => 'required|array',
            'group_ids.*' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $deletedCount = $this->groupService->deleteUserGroups(
                (int) $accountId,
                $request->input('group_ids')
            );

            return $this->success([
                'deleted_count' => $deletedCount,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get user group users.
     *
     * GET /v2.1/accounts/{accountId}/groups/{groupId}/users
     *
     * @param Request $request
     * @param string $accountId
     * @param string $groupId
     * @return JsonResponse
     */
    public function getUsers(Request $request, string $accountId, string $groupId): JsonResponse
    {
        try {
            $users = $this->groupService->getUserGroupUsers((int) $accountId, $groupId);

            return $this->success([
                'users' => $users->map(function ($user) {
                    return [
                        'user_id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ];
                })->toArray(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Add users to a user group.
     *
     * PUT /v2.1/accounts/{accountId}/groups/{groupId}/users
     *
     * @param Request $request
     * @param string $accountId
     * @param string $groupId
     * @return JsonResponse
     */
    public function addUsers(Request $request, string $accountId, string $groupId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array',
            'user_ids.*' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $users = $this->groupService->addUserGroupUsers(
                (int) $accountId,
                $groupId,
                $request->input('user_ids')
            );

            return $this->success([
                'users' => $users->map(function ($user) {
                    return [
                        'user_id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ];
                })->toArray(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Remove users from a user group.
     *
     * DELETE /v2.1/accounts/{accountId}/groups/{groupId}/users
     *
     * @param Request $request
     * @param string $accountId
     * @param string $groupId
     * @return JsonResponse
     */
    public function removeUsers(Request $request, string $accountId, string $groupId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array',
            'user_ids.*' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $removedCount = $this->groupService->deleteUserGroupUsers(
                (int) $accountId,
                $groupId,
                $request->input('user_ids')
            );

            return $this->success([
                'removed_count' => $removedCount,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get user group brands.
     *
     * GET /v2.1/accounts/{accountId}/groups/{groupId}/brands
     *
     * @param Request $request
     * @param string $accountId
     * @param string $groupId
     * @return JsonResponse
     */
    public function getBrands(Request $request, string $accountId, string $groupId): JsonResponse
    {
        try {
            $brands = $this->groupService->getUserGroupBrands((int) $accountId, $groupId);

            return $this->success([
                'brands' => $brands->map(function ($brand) {
                    return [
                        'brand_id' => $brand->brand_id,
                        'brand_name' => $brand->brand_name,
                        'is_default' => $brand->is_default,
                    ];
                })->toArray(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Add brands to a user group.
     *
     * PUT /v2.1/accounts/{accountId}/groups/{groupId}/brands
     *
     * @param Request $request
     * @param string $accountId
     * @param string $groupId
     * @return JsonResponse
     */
    public function addBrands(Request $request, string $accountId, string $groupId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'brand_ids' => 'required|array',
            'brand_ids.*' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $brands = $this->groupService->addUserGroupBrands(
                (int) $accountId,
                $groupId,
                $request->input('brand_ids')
            );

            return $this->success([
                'brands' => $brands->map(function ($brand) {
                    return [
                        'brand_id' => $brand->brand_id,
                        'brand_name' => $brand->brand_name,
                        'is_default' => $brand->is_default,
                    ];
                })->toArray(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Remove all brands from a user group.
     *
     * DELETE /v2.1/accounts/{accountId}/groups/{groupId}/brands
     *
     * @param Request $request
     * @param string $accountId
     * @param string $groupId
     * @return JsonResponse
     */
    public function removeBrands(Request $request, string $accountId, string $groupId): JsonResponse
    {
        try {
            $removedCount = $this->groupService->deleteUserGroupBrands(
                (int) $accountId,
                $groupId
            );

            return $this->success([
                'removed_count' => $removedCount,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Format user group response.
     *
     * @param mixed $group
     * @return array
     */
    private function formatUserGroupResponse($group): array
    {
        $response = [
            'group_id' => $group->group_id,
            'group_name' => $group->group_name,
            'group_type' => $group->group_type,
            'permission_profile_id' => $group->permission_profile_id,
            'created_by' => $group->created_by,
            'modified_by' => $group->modified_by,
            'created_at' => $group->created_at?->toIso8601String(),
            'updated_at' => $group->updated_at?->toIso8601String(),
        ];

        if ($group->relationLoaded('users') && $group->users) {
            $response['users'] = $group->users->map(function ($user) {
                return [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ];
            })->toArray();
        }

        if ($group->relationLoaded('permissionProfile') && $group->permissionProfile) {
            $response['permission_profile'] = [
                'profile_id' => $group->permissionProfile->id,
                'profile_name' => $group->permissionProfile->profile_name,
            ];
        }

        if ($group->relationLoaded('brands') && $group->brands) {
            $response['brands'] = $group->brands->map(function ($brand) {
                return [
                    'brand_id' => $brand->brand_id,
                    'brand_name' => $brand->brand_name,
                    'is_default' => $brand->is_default,
                ];
            })->toArray();
        }

        return $response;
    }
}
