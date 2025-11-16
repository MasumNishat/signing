<?php

use App\Http\Controllers\Api\V2_1\SigningGroupController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Signing Groups API Routes
|--------------------------------------------------------------------------
|
| Routes for managing signing groups - groups of users that can be used
| in envelope routing. Signing groups allow flexible recipient routing.
|
| All routes require authentication and account access.
|
*/

Route::middleware(['throttle:api', 'check.account.access'])->group(function () {

    // Signing Groups Collection Routes
    Route::prefix('accounts/{accountId}/signing_groups')->group(function () {

        // List all signing groups
        Route::get('/', [SigningGroupController::class, 'index'])
            ->middleware('check.permission:view_signing_groups')
            ->name('signing_groups.index');

        // Create a new signing group
        Route::post('/', [SigningGroupController::class, 'store'])
            ->middleware('check.permission:create_signing_groups')
            ->name('signing_groups.store');

        // Bulk update signing groups
        Route::put('/', [SigningGroupController::class, 'bulkUpdate'])
            ->middleware('check.permission:update_signing_groups')
            ->name('signing_groups.bulk_update');

        // Bulk delete signing groups
        Route::delete('/', [SigningGroupController::class, 'bulkDestroy'])
            ->middleware('check.permission:delete_signing_groups')
            ->name('signing_groups.bulk_destroy');

        // Signing Group Individual Routes
        Route::prefix('{signingGroupId}')->group(function () {

            // Get a specific signing group
            Route::get('/', [SigningGroupController::class, 'show'])
                ->middleware('check.permission:view_signing_groups')
                ->name('signing_groups.show');

            // Signing Group Users Routes
            Route::prefix('users')->group(function () {

                // Get signing group users
                Route::get('/', [SigningGroupController::class, 'getUsers'])
                    ->middleware('check.permission:view_signing_groups')
                    ->name('signing_groups.users.index');

                // Add users to signing group
                Route::put('/', [SigningGroupController::class, 'addUsers'])
                    ->middleware('check.permission:update_signing_groups')
                    ->name('signing_groups.users.add');

                // Remove users from signing group
                Route::delete('/', [SigningGroupController::class, 'removeUsers'])
                    ->middleware('check.permission:update_signing_groups')
                    ->name('signing_groups.users.remove');
            });
        });
    });
});
