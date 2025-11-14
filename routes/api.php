<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V2_1\Auth\AuthController;
use App\Http\Controllers\Api\V2_1\Auth\OAuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// API Version 2.1
Route::prefix('v2.1')->name('api.v2.1.')->group(function () {

    // Authentication Routes (Public)
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('register', [AuthController::class, 'register'])
            ->middleware('throttle:register')
            ->name('register');
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:login')
            ->name('login');
        Route::post('refresh', [AuthController::class, 'refresh'])
            ->middleware('throttle:oauth-token')
            ->name('refresh');

        // OAuth 2.0 Routes
        Route::get('authorize', [OAuthController::class, 'authorize'])->name('oauth.authorize');
        Route::post('authorize', [OAuthController::class, 'authorizePost'])->name('oauth.authorize.post');
        Route::post('token', [OAuthController::class, 'token'])
            ->middleware('throttle:oauth-token')
            ->name('oauth.token');
        Route::post('token/refresh', [OAuthController::class, 'refreshToken'])
            ->middleware('throttle:oauth-token')
            ->name('oauth.token.refresh');
    });

    // Protected Routes (Require Authentication)
    Route::middleware('auth:api')->group(function () {

        // Authentication
        Route::prefix('auth')->name('auth.')->group(function () {
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('user', [AuthController::class, 'user'])->name('user');
            Route::post('revoke', [AuthController::class, 'revoke'])->name('revoke');
        });

        // Permission System Routes
        Route::prefix('permissions')->name('permissions.')->group(function () {
            Route::get('available', [\App\Http\Controllers\Api\V2_1\PermissionProfileController::class, 'availablePermissions'])->name('available');
            Route::get('roles', [\App\Http\Controllers\Api\V2_1\PermissionProfileController::class, 'availableRoles'])->name('roles');
        });

        // Account Routes
        require __DIR__.'/api/v2.1/accounts.php';

        // User Routes
        require __DIR__.'/api/v2.1/users.php';

        // Envelope Routes
        require __DIR__.'/api/v2.1/envelopes.php';

        // Template Routes
        require __DIR__.'/api/v2.1/templates.php';

        // Brand Routes
        require __DIR__.'/api/v2.1/brands.php';

        // Billing Routes
        require __DIR__.'/api/v2.1/billing.php';

        // Connect Routes
        require __DIR__.'/api/v2.1/connect.php';

        // Workspace Routes
        require __DIR__.'/api/v2.1/workspaces.php';

        // PowerForm Routes
        require __DIR__.'/api/v2.1/powerforms.php';

        // Signature Routes
        require __DIR__.'/api/v2.1/signatures.php';

        // Bulk Send Routes
        require __DIR__.'/api/v2.1/bulk.php';

        // Additional route files will be added as features are implemented
    });
});
