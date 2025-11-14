<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountAccess
{
    /**
     * Handle an incoming request.
     *
     * Ensures the authenticated user has access to the requested account.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Get account ID from route parameter
        $accountId = $request->route('accountId');

        if (!$accountId) {
            // No account ID in route, skip check
            return $next($request);
        }

        // Find the account
        $account = \App\Models\Account::where('account_id', $accountId)->first();

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found',
            ], 404);
        }

        // Check if user belongs to this account
        if ($user->account_id !== $account->id) {
            // Check if user has authorization to act on behalf of this account
            $hasAuthorization = \App\Models\UserAuthorization::where('agent_user_id', $user->id)
                ->whereHas('principal', function ($query) use ($account) {
                    $query->where('account_id', $account->id);
                })
                ->where('is_active', true)
                ->exists();

            if (!$hasAuthorization) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this account',
                ], 403);
            }
        }

        // Attach account to request
        $request->attributes->set('account', $account);

        return $next($request);
    }
}
