<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Models\Account;
use App\Models\User;
use App\Models\UserAuthorization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * UserAuthorizationController
 *
 * Manages user authorizations - allowing users to grant permissions to other users
 * (agent users) to act on their behalf.
 *
 * Endpoints: 7 total
 * - GET /accounts/{accountId}/users/{userId}/authorizations - Get principal authorizations
 * - POST /accounts/{accountId}/users/{userId}/authorizations - Create/update bulk authorizations
 * - GET /accounts/{accountId}/users/{userId}/authorizations/agent - Get agent authorizations
 * - POST /accounts/{accountId}/users/{userId}/authorization - Create authorization
 * - GET /accounts/{accountId}/users/{userId}/authorization/{authorizationId} - Get authorization
 * - PUT /accounts/{accountId}/users/{userId}/authorization/{authorizationId} - Update authorization
 * - DELETE /accounts/{accountId}/users/{userId}/authorization/{authorizationId} - Delete authorization
 */
class UserAuthorizationController extends BaseController
{
    /**
     * GET /accounts/{accountId}/users/{userId}/authorizations
     * Returns the principal user authorizations (where this user grants permissions to others)
     */
    public function indexPrincipal(Request $request, string $accountId, string $userId): JsonResponse
    {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();
            $user = User::where('user_name', $userId)->where('account_id', $account->id)->firstOrFail();

            $authorizations = UserAuthorization::where('account_id', $account->id)
                ->where('principal_user_id', $user->id)
                ->with(['agent:id,user_name,email,name'])
                ->get()
                ->map(function ($auth) {
                    return [
                        'authorization_id' => (string) $auth->id,
                        'agent_user' => [
                            'user_name' => $auth->agent->user_name,
                            'email' => $auth->agent->email,
                            'name' => $auth->agent->name,
                        ],
                        'permissions' => $auth->permissions,
                        'start_date' => $auth->start_date?->toIso8601String(),
                        'end_date' => $auth->end_date?->toIso8601String(),
                        'is_active' => $auth->is_active,
                        'is_valid' => $auth->isValid(),
                    ];
                });

            return $this->successResponse([
                'authorizations' => $authorizations,
            ], 'Principal user authorizations retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve principal user authorizations', [
                'account_id' => $accountId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve authorizations', 500);
        }
    }

    /**
     * GET /accounts/{accountId}/users/{userId}/authorizations/agent
     * Returns the agent user authorizations (where this user receives permissions from others)
     */
    public function indexAgent(Request $request, string $accountId, string $userId): JsonResponse
    {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();
            $user = User::where('user_name', $userId)->where('account_id', $account->id)->firstOrFail();

            $authorizations = UserAuthorization::where('account_id', $account->id)
                ->where('agent_user_id', $user->id)
                ->with(['principal:id,user_name,email,name'])
                ->get()
                ->map(function ($auth) {
                    return [
                        'authorization_id' => (string) $auth->id,
                        'principal_user' => [
                            'user_name' => $auth->principal->user_name,
                            'email' => $auth->principal->email,
                            'name' => $auth->principal->name,
                        ],
                        'permissions' => $auth->permissions,
                        'start_date' => $auth->start_date?->toIso8601String(),
                        'end_date' => $auth->end_date?->toIso8601String(),
                        'is_active' => $auth->is_active,
                        'is_valid' => $auth->isValid(),
                    ];
                });

            return $this->successResponse([
                'authorizations' => $authorizations,
            ], 'Agent user authorizations retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve agent user authorizations', [
                'account_id' => $accountId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve authorizations', 500);
        }
    }

    /**
     * POST /accounts/{accountId}/users/{userId}/authorizations
     * Creates or updates user authorizations (bulk operation)
     */
    public function storeBulk(Request $request, string $accountId, string $userId): JsonResponse
    {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();
            $user = User::where('user_name', $userId)->where('account_id', $account->id)->firstOrFail();

            $validator = Validator::make($request->all(), [
                'authorizations' => 'required|array',
                'authorizations.*.agent_user_name' => 'required|string|exists:users,user_name',
                'authorizations.*.permissions' => 'required|array',
                'authorizations.*.start_date' => 'nullable|date',
                'authorizations.*.end_date' => 'nullable|date',
                'authorizations.*.is_active' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $createdAuthorizations = [];

            foreach ($request->authorizations as $authData) {
                $agentUser = User::where('user_name', $authData['agent_user_name'])
                    ->where('account_id', $account->id)
                    ->firstOrFail();

                $authorization = UserAuthorization::updateOrCreate(
                    [
                        'account_id' => $account->id,
                        'principal_user_id' => $user->id,
                        'agent_user_id' => $agentUser->id,
                    ],
                    [
                        'permissions' => $authData['permissions'],
                        'start_date' => $authData['start_date'] ?? null,
                        'end_date' => $authData['end_date'] ?? null,
                        'is_active' => $authData['is_active'] ?? true,
                    ]
                );

                $createdAuthorizations[] = [
                    'authorization_id' => (string) $authorization->id,
                    'agent_user_name' => $agentUser->user_name,
                    'permissions' => $authorization->permissions,
                ];
            }

            return $this->successResponse([
                'authorizations' => $createdAuthorizations,
            ], 'User authorizations created/updated successfully');

        } catch (\Exception $e) {
            Log::error('Failed to create/update user authorizations', [
                'account_id' => $accountId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to create/update authorizations', 500);
        }
    }

    /**
     * POST /accounts/{accountId}/users/{userId}/authorization
     * Creates a new user authorization
     */
    public function store(Request $request, string $accountId, string $userId): JsonResponse
    {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();
            $user = User::where('user_name', $userId)->where('account_id', $account->id)->firstOrFail();

            $validator = Validator::make($request->all(), [
                'agent_user_name' => 'required|string|exists:users,user_name',
                'permissions' => 'required|array',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'is_active' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $agentUser = User::where('user_name', $request->agent_user_name)
                ->where('account_id', $account->id)
                ->firstOrFail();

            $authorization = UserAuthorization::create([
                'account_id' => $account->id,
                'principal_user_id' => $user->id,
                'agent_user_id' => $agentUser->id,
                'permissions' => $request->permissions,
                'start_date' => $request->start_date ?? null,
                'end_date' => $request->end_date ?? null,
                'is_active' => $request->is_active ?? true,
            ]);

            return $this->successResponse([
                'authorization_id' => (string) $authorization->id,
                'agent_user_name' => $agentUser->user_name,
                'permissions' => $authorization->permissions,
                'is_active' => $authorization->is_active,
            ], 'User authorization created successfully', 201);

        } catch (\Exception $e) {
            Log::error('Failed to create user authorization', [
                'account_id' => $accountId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to create authorization', 500);
        }
    }

    /**
     * GET /accounts/{accountId}/users/{userId}/authorization/{authorizationId}
     * Returns a specific user authorization
     */
    public function show(string $accountId, string $userId, string $authorizationId): JsonResponse
    {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();
            $user = User::where('user_name', $userId)->where('account_id', $account->id)->firstOrFail();

            $authorization = UserAuthorization::where('id', $authorizationId)
                ->where('account_id', $account->id)
                ->where('principal_user_id', $user->id)
                ->with(['agent:id,user_name,email,name'])
                ->firstOrFail();

            return $this->successResponse([
                'authorization_id' => (string) $authorization->id,
                'agent_user' => [
                    'user_name' => $authorization->agent->user_name,
                    'email' => $authorization->agent->email,
                    'name' => $authorization->agent->name,
                ],
                'permissions' => $authorization->permissions,
                'start_date' => $authorization->start_date?->toIso8601String(),
                'end_date' => $authorization->end_date?->toIso8601String(),
                'is_active' => $authorization->is_active,
                'is_valid' => $authorization->isValid(),
            ], 'User authorization retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve user authorization', [
                'account_id' => $accountId,
                'user_id' => $userId,
                'authorization_id' => $authorizationId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve authorization', 500);
        }
    }

    /**
     * PUT /accounts/{accountId}/users/{userId}/authorization/{authorizationId}
     * Updates a user authorization
     */
    public function update(Request $request, string $accountId, string $userId, string $authorizationId): JsonResponse
    {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();
            $user = User::where('user_name', $userId)->where('account_id', $account->id)->firstOrFail();

            $authorization = UserAuthorization::where('id', $authorizationId)
                ->where('account_id', $account->id)
                ->where('principal_user_id', $user->id)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'permissions' => 'sometimes|array',
                'start_date' => 'sometimes|nullable|date',
                'end_date' => 'sometimes|nullable|date',
                'is_active' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $authorization->update($request->only(['permissions', 'start_date', 'end_date', 'is_active']));

            return $this->successResponse([
                'authorization_id' => (string) $authorization->id,
                'permissions' => $authorization->permissions,
                'is_active' => $authorization->is_active,
                'is_valid' => $authorization->isValid(),
            ], 'User authorization updated successfully');

        } catch (\Exception $e) {
            Log::error('Failed to update user authorization', [
                'account_id' => $accountId,
                'user_id' => $userId,
                'authorization_id' => $authorizationId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to update authorization', 500);
        }
    }

    /**
     * DELETE /accounts/{accountId}/users/{userId}/authorization/{authorizationId}
     * Deletes a user authorization
     */
    public function destroy(string $accountId, string $userId, string $authorizationId): JsonResponse
    {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();
            $user = User::where('user_name', $userId)->where('account_id', $account->id)->firstOrFail();

            $authorization = UserAuthorization::where('id', $authorizationId)
                ->where('account_id', $account->id)
                ->where('principal_user_id', $user->id)
                ->firstOrFail();

            $authorization->delete();

            return $this->successResponse([
                'authorization_id' => $authorizationId,
                'message' => 'User authorization deleted successfully',
            ], 'User authorization deleted successfully');

        } catch (\Exception $e) {
            Log::error('Failed to delete user authorization', [
                'account_id' => $accountId,
                'user_id' => $userId,
                'authorization_id' => $authorizationId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to delete authorization', 500);
        }
    }

    /**
     * DELETE /accounts/{accountId}/users/{userId}/authorizations
     * Deletes all user authorizations for a principal user (bulk delete)
     */
    public function destroyBulk(string $accountId, string $userId): JsonResponse
    {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();
            $user = User::where('user_name', $userId)->where('account_id', $account->id)->firstOrFail();

            // Delete all authorizations where this user is the principal
            $deleted = UserAuthorization::where('account_id', $account->id)
                ->where('principal_user_id', $user->id)
                ->delete();

            return $this->successResponse([
                'deleted_count' => $deleted,
                'message' => "Deleted {$deleted} authorization(s)",
            ], 'User authorizations deleted successfully');

        } catch (\Exception $e) {
            Log::error('Failed to bulk delete user authorizations', [
                'account_id' => $accountId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to delete authorizations', 500);
        }
    }
}
