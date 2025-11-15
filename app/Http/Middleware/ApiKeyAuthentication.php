<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check for API key in header
        $apiKey = $request->header('X-Api-Key');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key is required',
            ], 401);
        }

        // Hash the key and find it in database
        $keyHash = \App\Models\ApiKey::hashKey($apiKey);
        $apiKeyModel = \App\Models\ApiKey::where('key_hash', $keyHash)->first();

        if (!$apiKeyModel) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key',
            ], 401);
        }

        // Validate key
        if (!$apiKeyModel->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'API key is expired or revoked',
            ], 401);
        }

        // Record usage
        $apiKeyModel->recordUsage();

        // Attach user to request if key has a user
        if ($apiKeyModel->user_id) {
            $request->setUserResolver(function () use ($apiKeyModel) {
                return $apiKeyModel->user;
            });
        }

        // Store API key model in request for scope checking
        $request->attributes->set('api_key', $apiKeyModel);

        return $next($request);
    }
}
