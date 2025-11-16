<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Http\Controllers\Controller;
use App\Services\GroupService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class SigningGroupController extends Controller
{
    protected GroupService $groupService;

    public function __construct(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }

    /**
     * Get a list of signing groups for the account.
     *
     * GET /v2.1/accounts/{accountId}/signing_groups
     *
     * @param Request $request
     * @param string $accountId
     * @return JsonResponse
     */
    public function index(Request $request, string $accountId): JsonResponse
    {
        try {
            $options = $request->only(['group_type', 'include_users']);

            $groups = $this->groupService->getSigningGroups((int) $accountId, $options);

            return $this->success([
                'groups' => $groups->map(function ($group) {
                    return $this->formatSigningGroupResponse($group);
                })->toArray(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get a specific signing group.
     *
     * GET /v2.1/accounts/{accountId}/signing_groups/{signingGroupId}
     *
     * @param Request $request
     * @param string $accountId
     * @param string $signingGroupId
     * @return JsonResponse
     */
    public function show(Request $request, string $accountId, string $signingGroupId): JsonResponse
    {
        try {
            $group = $this->groupService->getSigningGroup((int) $accountId, $signingGroupId);

            if (!$group) {
                return $this->notFound('Signing group not found');
            }

            return $this->success($this->formatSigningGroupResponse($group));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Create a new signing group.
     *
     * POST /v2.1/accounts/{accountId}/signing_groups
     *
     * @param Request $request
     * @param string $accountId
     * @return JsonResponse
     */
    public function store(Request $request, string $accountId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'group_name' => 'required|string|max:255',
            'group_email' => 'nullable|email|max:255',
            'group_type' => 'nullable|string|in:public,private,shared',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $userId = auth()->id();
            $group = $this->groupService->createSigningGroup(
                (int) $accountId,
                $request->all(),
                $userId
            );

            return $this->created($this->formatSigningGroupResponse($group));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Update signing groups (bulk update).
     *
     * PUT /v2.1/accounts/{accountId}/signing_groups
     *
     * @param Request $request
     * @param string $accountId
     * @return JsonResponse
     */
    public function bulkUpdate(Request $request, string $accountId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'groups' => 'required|array',
            'groups.*.signing_group_id' => 'required|string',
            'groups.*.group_name' => 'nullable|string|max:255',
            'groups.*.group_email' => 'nullable|email|max:255',
            'groups.*.group_type' => 'nullable|string|in:public,private,shared',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $userId = auth()->id();
            $updatedGroups = $this->groupService->updateSigningGroups(
                (int) $accountId,
                $request->input('groups'),
                $userId
            );

            return $this->success([
                'groups' => $updatedGroups->map(function ($group) {
                    return $this->formatSigningGroupResponse($group);
                })->toArray(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Delete signing groups (bulk delete).
     *
     * DELETE /v2.1/accounts/{accountId}/signing_groups
     *
     * @param Request $request
     * @param string $accountId
     * @return JsonResponse
     */
    public function bulkDestroy(Request $request, string $accountId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'signing_group_ids' => 'required|array',
            'signing_group_ids.*' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $deletedCount = $this->groupService->deleteSigningGroups(
                (int) $accountId,
                $request->input('signing_group_ids')
            );

            return $this->success([
                'deleted_count' => $deletedCount,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get signing group users.
     *
     * GET /v2.1/accounts/{accountId}/signing_groups/{signingGroupId}/users
     *
     * @param Request $request
     * @param string $accountId
     * @param string $signingGroupId
     * @return JsonResponse
     */
    public function getUsers(Request $request, string $accountId, string $signingGroupId): JsonResponse
    {
        try {
            $users = $this->groupService->getSigningGroupUsers((int) $accountId, $signingGroupId);

            return $this->success([
                'users' => $users->map(function ($user) {
                    return [
                        'user_id' => $user->id,
                        'email' => $user->pivot->email,
                        'user_name' => $user->pivot->user_name,
                    ];
                })->toArray(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Add users to a signing group.
     *
     * PUT /v2.1/accounts/{accountId}/signing_groups/{signingGroupId}/users
     *
     * @param Request $request
     * @param string $accountId
     * @param string $signingGroupId
     * @return JsonResponse
     */
    public function addUsers(Request $request, string $accountId, string $signingGroupId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'users' => 'required|array',
            'users.*.email' => 'required|email',
            'users.*.user_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $users = $this->groupService->addSigningGroupUsers(
                (int) $accountId,
                $signingGroupId,
                $request->input('users')
            );

            return $this->success([
                'users' => $users->map(function ($user) {
                    return [
                        'user_id' => $user->id,
                        'email' => $user->pivot->email,
                        'user_name' => $user->pivot->user_name,
                    ];
                })->toArray(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Remove users from a signing group.
     *
     * DELETE /v2.1/accounts/{accountId}/signing_groups/{signingGroupId}/users
     *
     * @param Request $request
     * @param string $accountId
     * @param string $signingGroupId
     * @return JsonResponse
     */
    public function removeUsers(Request $request, string $accountId, string $signingGroupId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array',
            'user_ids.*' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $removedCount = $this->groupService->deleteSigningGroupUsers(
                (int) $accountId,
                $signingGroupId,
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
     * Format signing group response.
     *
     * @param mixed $group
     * @return array
     */
    private function formatSigningGroupResponse($group): array
    {
        $response = [
            'signing_group_id' => $group->signing_group_id,
            'group_name' => $group->group_name,
            'group_email' => $group->group_email,
            'group_type' => $group->group_type,
            'created_by' => $group->created_by,
            'modified_by' => $group->modified_by,
            'created_at' => $group->created_at?->toIso8601String(),
            'updated_at' => $group->updated_at?->toIso8601String(),
        ];

        if ($group->relationLoaded('users') && $group->users) {
            $response['users'] = $group->users->map(function ($user) {
                return [
                    'user_id' => $user->id,
                    'email' => $user->pivot->email,
                    'user_name' => $user->pivot->user_name,
                ];
            })->toArray();
        }

        return $response;
    }
}
