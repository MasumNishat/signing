<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register middleware aliases
        $middleware->alias([
            'api.key' => \App\Http\Middleware\ApiKeyAuthentication::class,
            'scope' => \App\Http\Middleware\CheckApiScope::class,
            'account.access' => \App\Http\Middleware\CheckAccountAccess::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'rate.limit' => \App\Http\Middleware\RateLimitMiddleware::class,
        ]);

        // Apply rate limiting to API routes globally
        $middleware->api(append: [
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            'throttle:api',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle custom API exceptions
        $exceptions->renderable(function (\App\Exceptions\Custom\ApiException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return $e->render();
            }
        });

        // Handle Laravel validation exceptions for API routes
        $exceptions->renderable(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => $e->getMessage(),
                        'details' => $e->errors(),
                    ],
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                        'request_id' => $request->header('X-Request-ID') ?? \Str::uuid()->toString(),
                        'version' => 'v2.1',
                    ],
                ], 422);
            }
        });

        // Handle model not found exceptions
        $exceptions->renderable(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $model = class_basename($e->getModel());
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'RESOURCE_NOT_FOUND',
                        'message' => "The requested {$model} was not found",
                        'details' => ['model' => $model],
                    ],
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                        'request_id' => $request->header('X-Request-ID') ?? \Str::uuid()->toString(),
                        'version' => 'v2.1',
                    ],
                ], 404);
            }
        });

        // Handle authentication exceptions
        $exceptions->renderable(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'UNAUTHENTICATED',
                        'message' => $e->getMessage() ?: 'Unauthenticated',
                    ],
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                        'request_id' => $request->header('X-Request-ID') ?? \Str::uuid()->toString(),
                        'version' => 'v2.1',
                    ],
                ], 401);
            }
        });

        // Handle authorization exceptions
        $exceptions->renderable(function (\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'FORBIDDEN',
                        'message' => $e->getMessage() ?: 'This action is unauthorized',
                    ],
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                        'request_id' => $request->header('X-Request-ID') ?? \Str::uuid()->toString(),
                        'version' => 'v2.1',
                    ],
                ], 403);
            }
        });

        // Handle method not allowed exceptions
        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'METHOD_NOT_ALLOWED',
                        'message' => 'The HTTP method used is not allowed for this endpoint',
                    ],
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                        'request_id' => $request->header('X-Request-ID') ?? \Str::uuid()->toString(),
                        'version' => 'v2.1',
                    ],
                ], 405);
            }
        });

        // Handle not found exceptions
        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'ENDPOINT_NOT_FOUND',
                        'message' => 'The requested endpoint does not exist',
                    ],
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                        'request_id' => $request->header('X-Request-ID') ?? \Str::uuid()->toString(),
                        'version' => 'v2.1',
                    ],
                ], 404);
            }
        });

        // Handle generic HTTP exceptions
        $exceptions->renderable(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'HTTP_ERROR',
                        'message' => $e->getMessage() ?: 'An error occurred',
                    ],
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                        'request_id' => $request->header('X-Request-ID') ?? \Str::uuid()->toString(),
                        'version' => 'v2.1',
                    ],
                ], $e->getStatusCode());
            }
        });

        // Handle all other exceptions
        $exceptions->renderable(function (\Throwable $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $isDebug = config('app.debug', false);

                $response = [
                    'success' => false,
                    'error' => [
                        'code' => 'INTERNAL_SERVER_ERROR',
                        'message' => $isDebug ? $e->getMessage() : 'An unexpected error occurred',
                    ],
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                        'request_id' => $request->header('X-Request-ID') ?? \Str::uuid()->toString(),
                        'version' => 'v2.1',
                    ],
                ];

                if ($isDebug) {
                    $response['error']['details'] = [
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => collect($e->getTrace())->take(5)->toArray(),
                    ];
                }

                \Log::error('API Exception', [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'request_id' => $request->header('X-Request-ID'),
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                ]);

                return response()->json($response, 500);
            }
        });
    })->create();
