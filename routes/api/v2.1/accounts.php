<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Account API Routes
|--------------------------------------------------------------------------
|
| Account management routes for the Signing API.
| These routes handle account information, settings, and configurations.
|
*/

Route::prefix('accounts/{accountId}')->name('accounts.')->middleware('account.access')->group(function () {
    // Permission Profiles
    Route::prefix('permission-profiles')->name('permission-profiles.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V2_1\PermissionProfileController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Api\V2_1\PermissionProfileController::class, 'store'])->name('store');
        Route::get('/{profileId}', [\App\Http\Controllers\Api\V2_1\PermissionProfileController::class, 'show'])->name('show');
        Route::put('/{profileId}', [\App\Http\Controllers\Api\V2_1\PermissionProfileController::class, 'update'])->name('update');
        Route::delete('/{profileId}', [\App\Http\Controllers\Api\V2_1\PermissionProfileController::class, 'destroy'])->name('destroy');
    });

    // API Keys
    Route::prefix('api-keys')->name('api-keys.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V2_1\ApiKeyController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\Api\V2_1\ApiKeyController::class, 'store'])->name('store');
        Route::get('/{keyId}', [\App\Http\Controllers\Api\V2_1\ApiKeyController::class, 'show'])->name('show');
        Route::put('/{keyId}', [\App\Http\Controllers\Api\V2_1\ApiKeyController::class, 'update'])->name('update');
        Route::post('/{keyId}/revoke', [\App\Http\Controllers\Api\V2_1\ApiKeyController::class, 'revoke'])->name('revoke');
        Route::post('/{keyId}/rotate', [\App\Http\Controllers\Api\V2_1\ApiKeyController::class, 'rotate'])->name('rotate');
        Route::delete('/{keyId}', [\App\Http\Controllers\Api\V2_1\ApiKeyController::class, 'destroy'])->name('destroy');
    });

    // Additional account routes will be implemented in Phase 2
});
