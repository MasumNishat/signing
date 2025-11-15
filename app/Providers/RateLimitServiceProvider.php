<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RateLimitServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Global API rate limiting - 1000/hour for authenticated, 100/hour for unauthenticated
        RateLimiter::for('api', function (Request $request) {
            $limit = $request->user() ? 1000 : 100;
            $key = $request->user()
                ? 'api:user:' . $request->user()->id
                : 'api:ip:' . $request->ip();

            return Limit::perHour($limit)->by($key)->response(function (Request $request, array $headers) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many API requests. Please try again later.',
                ], 429, $headers);
            });
        });

        // Burst protection - 20 requests per second
        RateLimiter::for('api-burst', function (Request $request) {
            $key = $request->user()
                ? 'burst:user:' . $request->user()->id
                : 'burst:ip:' . $request->ip();

            return Limit::perSecond(20)->by($key)->response(function (Request $request, array $headers) {
                return response()->json([
                    'success' => false,
                    'message' => 'Request rate too high. Please slow down.',
                ], 429, $headers);
            });
        });

        // Login attempts - 5 per minute per IP
        RateLimiter::for('login', function (Request $request) {
            $email = $request->input('email', '');
            $key = 'login:' . $email . ':' . $request->ip();

            return Limit::perMinute(5)->by($key)->response(function (Request $request, array $headers) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many login attempts. Please try again in a minute.',
                ], 429, $headers);
            });
        });

        // Registration - 3 per hour per IP
        RateLimiter::for('register', function (Request $request) {
            $key = 'register:' . $request->ip();

            return Limit::perHour(3)->by($key)->response(function (Request $request, array $headers) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many registration attempts. Please try again later.',
                ], 429, $headers);
            });
        });

        // OAuth token requests - 10 per minute
        RateLimiter::for('oauth-token', function (Request $request) {
            $key = 'oauth:' . $request->ip();

            return Limit::perMinute(10)->by($key)->response(function (Request $request, array $headers) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many token requests. Please try again later.',
                ], 429, $headers);
            });
        });

        // Per-user envelope sending limit (based on account plan)
        RateLimiter::for('envelope-send', function (Request $request) {
            if (!$request->user()) {
                return Limit::none();
            }

            $account = $request->user()->account;
            $key = 'envelope:user:' . $request->user()->id;

            // 100 envelopes per hour max
            return Limit::perHour(100)->by($key)->response(function (Request $request, array $headers) {
                return response()->json([
                    'success' => false,
                    'message' => 'Envelope sending rate limit exceeded. Please try again later.',
                ], 429, $headers);
            });
        });

        // Webhook delivery attempts - 1000 per hour per account
        RateLimiter::for('webhook', function (Request $request) {
            if (!$request->user()) {
                return Limit::none();
            }

            $key = 'webhook:account:' . $request->user()->account_id;

            return Limit::perHour(1000)->by($key);
        });
    }
}
