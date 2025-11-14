<?php

namespace App\Http\Controllers\Api\V2_1\Auth;

use App\Http\Controllers\Api\V2_1\BaseController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseController
{
    /**
     * Register a new user and account.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_name' => 'required|string|max:255',
            'user_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'plan_id' => 'nullable|exists:plans,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            // Get the free plan or specified plan
            $plan = \App\Models\Plan::where('is_free', true)->first();
            if ($request->has('plan_id')) {
                $plan = \App\Models\Plan::find($request->plan_id);
            }

            // Create account
            $account = \App\Models\Account::create([
                'plan_id' => $plan->id,
                'account_id' => 'acc_' . \Str::random(16),
                'account_name' => $request->account_name,
                'billing_period_envelopes_sent' => 0,
                'billing_period_envelopes_allowed' => $plan->envelope_allowance,
                'created_date' => now(),
            ]);

            // Create user
            $user = User::create([
                'account_id' => $account->id,
                'user_name' => $request->user_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'user_status' => 'active',
                'user_type' => 'admin',
                'is_admin' => true,
                'activation_access_code' => \Str::random(32),
                'created_datetime' => now(),
            ]);

            // Create access token
            $token = $user->createToken('Personal Access Token')->accessToken;

            return $this->successResponse([
                'user' => $user,
                'account' => $account,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ], 'Registration successful', 201);

        } catch (\Exception $e) {
            return $this->errorResponse('Registration failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Login user and return access token.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->errorResponse('Invalid credentials', 401);
        }

        // Check if user is active
        if (!$user->isActive()) {
            return $this->errorResponse('User account is not active', 403);
        }

        // Update last login
        $user->update(['last_login' => now()]);

        // Create access token with scopes
        $scopes = $this->getUserScopes($user);
        $token = $user->createToken('Personal Access Token', $scopes)->accessToken;

        return $this->successResponse([
            'user' => $user->load('account', 'permissionProfile'),
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 3600, // 1 hour
        ], 'Login successful');
    }

    /**
     * Logout user (revoke token).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return $this->successResponse(null, 'Successfully logged out');
    }

    /**
     * Get authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        return $this->successResponse(
            $request->user()->load('account', 'permissionProfile', 'addresses')
        );
    }

    /**
     * Refresh access token.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            // This will be handled by OAuth2 token endpoint
            // For now, return error directing to OAuth endpoint
            return $this->errorResponse(
                'Please use POST /api/v2.1/auth/token with grant_type=refresh_token',
                400
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Token refresh failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Revoke all user tokens.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function revoke(Request $request)
    {
        $user = $request->user();

        // Revoke all tokens
        $user->tokens()->update(['revoked' => true]);

        return $this->successResponse(null, 'All tokens revoked successfully');
    }

    /**
     * Get scopes for a user based on their permissions.
     *
     * @param User $user
     * @return array
     */
    protected function getUserScopes(User $user): array
    {
        $scopes = [
            'account.read',
            'user.read',
            'envelope.read',
            'template.read',
        ];

        // Load permission profile
        if ($user->permissionProfile) {
            $permissions = $user->permissionProfile->permissions;

            // Map permissions to scopes
            if ($permissions['can_manage_account'] ?? false) {
                $scopes[] = 'account.write';
            }

            if ($permissions['can_manage_users'] ?? false) {
                $scopes[] = 'user.write';
                $scopes[] = 'user.delete';
            }

            if ($permissions['can_send_envelopes'] ?? false) {
                $scopes[] = 'envelope.write';
                $scopes[] = 'envelope.send';
            }

            if ($permissions['can_manage_templates'] ?? false) {
                $scopes[] = 'template.write';
                $scopes[] = 'template.delete';
            }

            if ($permissions['can_manage_branding'] ?? false) {
                $scopes[] = 'brand.read';
                $scopes[] = 'brand.write';
                $scopes[] = 'brand.delete';
            }
        }

        // Admin gets all scopes
        if ($user->isAdmin()) {
            $scopes[] = '*';
        }

        return array_unique($scopes);
    }
}
