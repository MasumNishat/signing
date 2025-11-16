<?php

use App\Http\Controllers\Api\V2_1\UserGroupController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Groups API Routes
|--------------------------------------------------------------------------
|
| Routes for managing user groups - organizational groups with permission
| profiles and brand associations. User groups control access and capabilities.
|
| All routes require authentication and account access.
|
*/

Route::middleware(['throttle:api', 'check.account.access'])->group(function () {

    // User Groups Collection Routes
    Route::prefix('accounts/{accountId}/groups')->group(function () {

        // List all user groups
        Route::get('/', [UserGroupController::class, 'index'])
            ->middleware('check.permission:view_groups')
            ->name('groups.index');

        // Get a specific user group
        Route::get('{groupId}', [UserGroupController::class, 'show'])
            ->middleware('check.permission:view_groups')
            ->name('groups.show');

        // Create user groups (bulk)
        Route::post('/', [UserGroupController::class, 'store'])
            ->middleware('check.permission:create_groups')
            ->name('groups.store');

        // Update user groups (bulk)
        Route::put('/', [UserGroupController::class, 'update'])
            ->middleware('check.permission:update_groups')
            ->name('groups.update');

        // Delete user groups (bulk)
        Route::delete('/', [UserGroupController::class, 'destroy'])
            ->middleware('check.permission:delete_groups')
            ->name('groups.destroy');

        // User Group Individual Routes
        Route::prefix('{groupId}')->group(function () {

            // User Group Users Routes
            Route::prefix('users')->group(function () {

                // Get user group users
                Route::get('/', [UserGroupController::class, 'getUsers'])
                    ->middleware('check.permission:view_groups')
                    ->name('groups.users.index');

                // Add users to user group
                Route::put('/', [UserGroupController::class, 'addUsers'])
                    ->middleware('check.permission:update_groups')
                    ->name('groups.users.add');

                // Remove users from user group
                Route::delete('/', [UserGroupController::class, 'removeUsers'])
                    ->middleware('check.permission:update_groups')
                    ->name('groups.users.remove');
            });

            // User Group Brands Routes
            Route::prefix('brands')->group(function () {

                // Get user group brands
                Route::get('/', [UserGroupController::class, 'getBrands'])
                    ->middleware('check.permission:view_groups')
                    ->name('groups.brands.index');

                // Add brands to user group
                Route::put('/', [UserGroupController::class, 'addBrands'])
                    ->middleware('check.permission:update_groups')
                    ->name('groups.brands.add');

                // Remove all brands from user group
                Route::delete('/', [UserGroupController::class, 'removeBrands'])
                    ->middleware('check.permission:update_groups')
                    ->name('groups.brands.remove');
            });
        });
    });
});
