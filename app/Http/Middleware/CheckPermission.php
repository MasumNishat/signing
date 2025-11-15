<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * Checks if the user has a specific permission.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Admin users have all permissions
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Check permission profile
        if (!$user->permissionProfile) {
            return response()->json([
                'success' => false,
                'message' => 'No permission profile assigned',
            ], 403);
        }

        if (!$user->permissionProfile->hasPermission($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions. Required: ' . $permission,
            ], 403);
        }

        return $next($request);
    }
}
