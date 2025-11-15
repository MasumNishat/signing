<?php

use App\Http\Controllers\Api\V2_1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User API Routes
|--------------------------------------------------------------------------
|
| User management routes for the Signing API.
| These routes handle user CRUD operations, contacts, profiles, settings, and permissions.
|
*/

Route::middleware(['throttle:api', 'check.account.access'])->group(function () {

    // User CRUD Routes
    Route::prefix('accounts/{accountId}/users')->name('users.')->group(function () {

        // List all users
        Route::get('/', [UserController::class, 'index'])
            ->middleware('check.permission:view_users')
            ->name('index');

        // Create a new user
        Route::post('/', [UserController::class, 'store'])
            ->middleware('check.permission:create_users')
            ->name('store');

        // Bulk update users
        Route::put('/', [UserController::class, 'bulkUpdate'])
            ->middleware('check.permission:update_users')
            ->name('bulk_update');

        // Delete users (bulk)
        Route::delete('/', [UserController::class, 'destroy'])
            ->middleware('check.permission:delete_users')
            ->name('destroy');

        // Specific User Routes
        Route::prefix('{userId}')->group(function () {

            // Get specific user
            Route::get('/', [UserController::class, 'show'])
                ->middleware('check.permission:view_users')
                ->name('show');

            // Update specific user
            Route::put('/', [UserController::class, 'update'])
                ->middleware('check.permission:update_users')
                ->name('update');

            // Custom Settings Routes
            Route::prefix('custom_settings')->name('custom_settings.')->group(function () {

                // Get custom settings
                Route::get('/', [UserController::class, 'getCustomSettings'])
                    ->middleware('check.permission:view_users')
                    ->name('index');

                // Update custom settings
                Route::put('/', [UserController::class, 'updateCustomSettings'])
                    ->middleware('check.permission:update_users')
                    ->name('update');

                // Delete custom settings
                Route::delete('/', [UserController::class, 'deleteCustomSettings'])
                    ->middleware('check.permission:update_users')
                    ->name('destroy');
            });

            // Profile Routes
            Route::prefix('profile')->name('profile.')->group(function () {

                // Get profile
                Route::get('/', [UserController::class, 'getProfile'])
                    ->middleware('check.permission:view_users')
                    ->name('show');

                // Update profile
                Route::put('/', [UserController::class, 'updateProfile'])
                    ->middleware('check.permission:update_users')
                    ->name('update');

                // Profile Image Routes
                Route::prefix('image')->name('image.')->group(function () {

                    // Get profile image
                    Route::get('/', [UserController::class, 'getProfileImage'])
                        ->middleware('check.permission:view_users')
                        ->name('show');

                    // Upload profile image
                    Route::put('/', [UserController::class, 'uploadProfileImage'])
                        ->middleware('check.permission:update_users')
                        ->name('upload');

                    // Delete profile image
                    Route::delete('/', [UserController::class, 'deleteProfileImage'])
                        ->middleware('check.permission:update_users')
                        ->name('destroy');
                });
            });

            // Settings Routes
            Route::prefix('settings')->name('settings.')->group(function () {

                // Get settings
                Route::get('/', [UserController::class, 'getSettings'])
                    ->middleware('check.permission:view_users')
                    ->name('show');

                // Update settings
                Route::put('/', [UserController::class, 'updateSettings'])
                    ->middleware('check.permission:update_users')
                    ->name('update');
            });

            // User Permissions Routes (existing)
            Route::prefix('permissions')->name('permissions.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\V2_1\UserPermissionController::class, 'show'])
                    ->middleware('check.permission:view_users')
                    ->name('show');
                Route::post('/check', [\App\Http\Controllers\Api\V2_1\UserPermissionController::class, 'checkPermissions'])
                    ->middleware('check.permission:view_users')
                    ->name('check');
                Route::post('/assign-role', [\App\Http\Controllers\Api\V2_1\UserPermissionController::class, 'assignRole'])
                    ->middleware('check.permission:update_users')
                    ->name('assign-role');
                Route::post('/assign-profile', [\App\Http\Controllers\Api\V2_1\UserPermissionController::class, 'assignProfile'])
                    ->middleware('check.permission:update_users')
                    ->name('assign-profile');
            });
        });
    });

    // Contacts Routes (for authenticated user)
    Route::prefix('accounts/{accountId}/contacts')->name('contacts.')->group(function () {

        // List all contacts
        Route::get('/', [UserController::class, 'getContacts'])
            ->name('index');

        // Import contacts (bulk create)
        Route::post('/', [UserController::class, 'importContacts'])
            ->name('import');

        // Replace all contacts
        Route::put('/', [UserController::class, 'replaceContacts'])
            ->name('replace');

        // Delete all contacts
        Route::delete('/', [UserController::class, 'deleteAllContacts'])
            ->name('destroy_all');

        // Get specific contact
        Route::get('{contactId}', [UserController::class, 'getContact'])
            ->name('show');

        // Delete specific contact
        Route::delete('{contactId}', [UserController::class, 'deleteContact'])
            ->name('destroy');
    });
});
