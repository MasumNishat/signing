<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User API Routes
|--------------------------------------------------------------------------
|
| User management routes for the Signing API.
| These routes handle user CRUD operations, permissions, and settings.
|
*/

Route::prefix('accounts/{accountId}/users')->name('users.')->middleware('account.access')->group(function () {
    // User Permissions
    Route::prefix('{userId}/permissions')->name('permissions.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V2_1\UserPermissionController::class, 'show'])->name('show');
        Route::post('/check', [\App\Http\Controllers\Api\V2_1\UserPermissionController::class, 'checkPermissions'])->name('check');
        Route::post('/assign-role', [\App\Http\Controllers\Api\V2_1\UserPermissionController::class, 'assignRole'])->name('assign-role');
        Route::post('/assign-profile', [\App\Http\Controllers\Api\V2_1\UserPermissionController::class, 'assignProfile'])->name('assign-profile');
    });

    // Additional user routes will be implemented in Phase 2
});
