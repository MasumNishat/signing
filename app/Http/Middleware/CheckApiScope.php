<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApiScope
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $scope
     */
    public function handle(Request $request, Closure $next, string $scope): Response
    {
        $user = $request->user();

        // Check OAuth token scopes (for Passport authentication)
        if ($user && $user->token()) {
            $token = $user->token();

            if (!$token->can($scope)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions. Required scope: ' . $scope,
                ], 403);
            }
        }

        // Check API key scopes (if using API key authentication)
        if ($request->attributes->has('api_key')) {
            $apiKey = $request->attributes->get('api_key');

            if (!$apiKey->hasScope($scope)) {
                return response()->json([
                    'success' => false,
                    'message' => 'API key does not have required scope: ' . $scope,
                ], 403);
            }
        }

        return $next($request);
    }
}
