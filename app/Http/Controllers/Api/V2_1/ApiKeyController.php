<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Models\Account;
use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;

class ApiKeyController extends BaseController
{
    /**
     * List all API keys for an account.
     *
     * @param Request $request
     * @param string $accountId
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, string $accountId)
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $query = ApiKey::where('account_id', $account->id)
            ->with('user:id,user_name,email');

        // If not admin, only show user's own keys
        if (!$request->user()->isAdmin()) {
            $query->where('user_id', $request->user()->id);
        }

        $apiKeys = $query->get();

        return $this->successResponse($apiKeys);
    }

    /**
     * Get a specific API key.
     *
     * @param Request $request
     * @param string $accountId
     * @param int $keyId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, string $accountId, int $keyId)
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $apiKey = ApiKey::where('account_id', $account->id)
            ->where('id', $keyId)
            ->with('user:id,user_name,email')
            ->firstOrFail();

        // Authorization check
        if (Gate::denies('view', $apiKey)) {
            return $this->errorResponse('Unauthorized to view this API key', 403);
        }

        return $this->successResponse($apiKey);
    }

    /**
     * Create a new API key.
     *
     * @param Request $request
     * @param string $accountId
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, string $accountId)
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        // Authorization check
        if (Gate::denies('create', ApiKey::class)) {
            return $this->errorResponse('Unauthorized to create API keys', 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'scopes' => 'nullable|array',
            'scopes.*' => 'string',
            'expires_at' => 'nullable|date|after:now',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            // If user_id is specified, verify the user belongs to the account
            $userId = $request->user_id ?? $request->user()->id;
            if ($request->has('user_id')) {
                $user = User::where('id', $request->user_id)
                    ->where('account_id', $account->id)
                    ->firstOrFail();
                $userId = $user->id;
            }

            // Generate API key
            $apiKeyString = ApiKey::generate();
            $keyHash = ApiKey::hashKey($apiKeyString);

            $apiKey = ApiKey::create([
                'account_id' => $account->id,
                'user_id' => $userId,
                'key_hash' => $keyHash,
                'name' => $request->name,
                'scopes' => $request->scopes,
                'expires_at' => $request->expires_at,
                'revoked' => false,
            ]);

            // Return the unhashed key ONLY on creation
            $apiKey->api_key = $apiKeyString;

            return $this->successResponse([
                'api_key' => $apiKeyString,
                'key_info' => $apiKey,
                'warning' => 'Save this API key securely. It will not be shown again.',
            ], 'API key created successfully', 201);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create API key: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update an API key.
     *
     * @param Request $request
     * @param string $accountId
     * @param int $keyId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $accountId, int $keyId)
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $apiKey = ApiKey::where('account_id', $account->id)
            ->where('id', $keyId)
            ->firstOrFail();

        // Authorization check
        if (Gate::denies('update', $apiKey)) {
            return $this->errorResponse('Unauthorized to update this API key', 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'scopes' => 'sometimes|array',
            'scopes.*' => 'string',
            'expires_at' => 'sometimes|nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $apiKey->update($request->only(['name', 'scopes', 'expires_at']));

            return $this->successResponse($apiKey, 'API key updated successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update API key: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Revoke an API key.
     *
     * @param Request $request
     * @param string $accountId
     * @param int $keyId
     * @return \Illuminate\Http\JsonResponse
     */
    public function revoke(Request $request, string $accountId, int $keyId)
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $apiKey = ApiKey::where('account_id', $account->id)
            ->where('id', $keyId)
            ->firstOrFail();

        // Authorization check
        if (Gate::denies('delete', $apiKey)) {
            return $this->errorResponse('Unauthorized to revoke this API key', 403);
        }

        $apiKey->update(['revoked' => true]);

        return $this->successResponse($apiKey, 'API key revoked successfully');
    }

    /**
     * Delete an API key.
     *
     * @param Request $request
     * @param string $accountId
     * @param int $keyId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $accountId, int $keyId)
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $apiKey = ApiKey::where('account_id', $account->id)
            ->where('id', $keyId)
            ->firstOrFail();

        // Authorization check
        if (Gate::denies('delete', $apiKey)) {
            return $this->errorResponse('Unauthorized to delete this API key', 403);
        }

        $apiKey->delete();

        return $this->successResponse(null, 'API key deleted successfully');
    }

    /**
     * Rotate an API key (revoke old, create new).
     *
     * @param Request $request
     * @param string $accountId
     * @param int $keyId
     * @return \Illuminate\Http\JsonResponse
     */
    public function rotate(Request $request, string $accountId, int $keyId)
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $oldApiKey = ApiKey::where('account_id', $account->id)
            ->where('id', $keyId)
            ->firstOrFail();

        // Authorization check
        if (Gate::denies('update', $oldApiKey)) {
            return $this->errorResponse('Unauthorized to rotate this API key', 403);
        }

        try {
            // Revoke old key
            $oldApiKey->update(['revoked' => true]);

            // Generate new API key
            $apiKeyString = ApiKey::generate();
            $keyHash = ApiKey::hashKey($apiKeyString);

            $newApiKey = ApiKey::create([
                'account_id' => $account->id,
                'user_id' => $oldApiKey->user_id,
                'key_hash' => $keyHash,
                'name' => $oldApiKey->name . ' (Rotated)',
                'scopes' => $oldApiKey->scopes,
                'expires_at' => $oldApiKey->expires_at,
                'revoked' => false,
            ]);

            // Return the unhashed key ONLY on creation
            $newApiKey->api_key = $apiKeyString;

            return $this->successResponse([
                'api_key' => $apiKeyString,
                'old_key_id' => $oldApiKey->id,
                'new_key_info' => $newApiKey,
                'warning' => 'Save this API key securely. It will not be shown again.',
            ], 'API key rotated successfully', 201);

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to rotate API key: ' . $e->getMessage(), 500);
        }
    }
}
