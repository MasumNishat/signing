<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $limiter
     */
    public function handle(Request $request, Closure $next, string $limiter = 'api'): Response
    {
        $key = $this->resolveRequestSignature($request, $limiter);

        $limit = $this->getLimit($request, $limiter);
        $decaySeconds = $this->getDecaySeconds($limiter);

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            return $this->buildRateLimitedResponse($key, $limit);
        }

        RateLimiter::hit($key, $decaySeconds);

        $response = $next($request);

        return $this->addHeaders(
            $response,
            $limit,
            RateLimiter::remaining($key, $limit),
            RateLimiter::availableIn($key)
        );
    }

    /**
     * Resolve the request signature.
     */
    protected function resolveRequestSignature(Request $request, string $limiter): string
    {
        $user = $request->user();

        if ($user) {
            return $limiter . '|user:' . $user->id;
        }

        // For unauthenticated requests, use IP
        return $limiter . '|ip:' . $request->ip();
    }

    /**
     * Get the rate limit for the given limiter.
     */
    protected function getLimit(Request $request, string $limiter): int
    {
        return match($limiter) {
            'api' => $request->user() ? 1000 : 100, // 1000/hour for auth, 100/hour for unauth
            'api-burst' => 20, // 20/second for burst protection
            'login' => 5, // 5 login attempts per minute
            'register' => 3, // 3 registrations per hour
            default => 60,
        };
    }

    /**
     * Get the decay seconds for the given limiter.
     */
    protected function getDecaySeconds(string $limiter): int
    {
        return match($limiter) {
            'api' => 3600, // 1 hour
            'api-burst' => 1, // 1 second
            'login' => 60, // 1 minute
            'register' => 3600, // 1 hour
            default => 60,
        };
    }

    /**
     * Build a rate-limited response.
     */
    protected function buildRateLimitedResponse(string $key, int $limit): Response
    {
        $retryAfter = RateLimiter::availableIn($key);

        $response = response()->json([
            'success' => false,
            'message' => 'Too many requests. Please try again later.',
            'retry_after' => $retryAfter,
        ], 429);

        return $this->addHeaders(
            $response,
            $limit,
            0,
            $retryAfter
        );
    }

    /**
     * Add rate limit headers to the response.
     */
    protected function addHeaders(Response $response, int $limit, int $remaining, int $retryAfter = null): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $limit,
            'X-RateLimit-Remaining' => max(0, $remaining),
        ]);

        if ($retryAfter !== null && $retryAfter > 0) {
            $response->headers->add([
                'Retry-After' => $retryAfter,
                'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp,
            ]);
        }

        return $response;
    }
}
