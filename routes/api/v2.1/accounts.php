<?php

use App\Http\Controllers\Api\V2_1\AccountController;
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

Route::middleware(['throttle:api'])->group(function () {

    // Account Creation (no account access check needed)
    Route::post('accounts', [AccountController::class, 'store'])
        ->middleware('check.permission:create_accounts')
        ->name('accounts.store');

    // Account Provisioning (for authenticated user's account)
    Route::get('accounts/provisioning', [AccountController::class, 'provisioning'])
        ->middleware('auth:api')
        ->name('accounts.provisioning');

    // Account-specific routes
    Route::prefix('accounts/{accountId}')->name('accounts.')->middleware('check.account.access')->group(function () {

        // Account CRUD
        Route::get('/', [AccountController::class, 'show'])
            ->middleware('check.permission:view_account')
            ->name('show');

        Route::delete('/', [AccountController::class, 'destroy'])
            ->middleware('check.permission:delete_account')
            ->name('destroy');

        // Custom Fields
        Route::prefix('custom_fields')->name('custom_fields.')->group(function () {
            Route::get('/', [AccountController::class, 'getCustomFields'])
                ->middleware('check.permission:view_account')
                ->name('index');

            Route::post('/', [AccountController::class, 'createCustomField'])
                ->middleware('check.permission:manage_account')
                ->name('store');

            Route::put('{customFieldId}', [AccountController::class, 'updateCustomField'])
                ->middleware('check.permission:manage_account')
                ->name('update');

            Route::delete('{customFieldId}', [AccountController::class, 'deleteCustomField'])
                ->middleware('check.permission:manage_account')
                ->name('destroy');
        });

        // Consumer Disclosure
        Route::get('consumer_disclosure', [AccountController::class, 'getConsumerDisclosure'])
            ->middleware('check.permission:view_account')
            ->name('consumer_disclosure.index');

        Route::get('consumer_disclosure/{langCode}', [AccountController::class, 'getConsumerDisclosureByLanguage'])
            ->middleware('check.permission:view_account')
            ->name('consumer_disclosure.show');

        Route::put('consumer_disclosure/{langCode}', [AccountController::class, 'updateConsumerDisclosure'])
            ->middleware('check.permission:manage_account')
            ->name('consumer_disclosure.update');

        // Watermark
        Route::get('watermark', [AccountController::class, 'getWatermark'])
            ->middleware('check.permission:view_account')
            ->name('watermark.show');

        Route::put('watermark', [AccountController::class, 'updateWatermark'])
            ->middleware('check.permission:manage_account')
            ->name('watermark.update');

        Route::put('watermark/preview', [AccountController::class, 'watermarkPreview'])
            ->middleware('check.permission:view_account')
            ->name('watermark.preview');

        // Recipient Names
        Route::get('recipient_names', [AccountController::class, 'getRecipientNames'])
            ->middleware('check.permission:view_account')
            ->name('recipient_names');

        // ==================== Configuration & Settings ====================

        // eNote Configuration
        Route::get('enote_configuration', [AccountController::class, 'getEnoteConfiguration'])
            ->middleware('check.permission:view_account')
            ->name('enote_configuration.show');

        Route::put('enote_configuration', [AccountController::class, 'updateEnoteConfiguration'])
            ->middleware('check.permission:manage_account')
            ->name('enote_configuration.update');

        Route::delete('enote_configuration', [AccountController::class, 'deleteEnoteConfiguration'])
            ->middleware('check.permission:manage_account')
            ->name('enote_configuration.destroy');

        // Envelope Purge Configuration
        Route::get('settings/envelope_purge_configuration', [AccountController::class, 'getEnvelopePurgeConfiguration'])
            ->middleware('check.permission:view_account')
            ->name('settings.envelope_purge.show');

        Route::put('settings/envelope_purge_configuration', [AccountController::class, 'updateEnvelopePurgeConfiguration'])
            ->middleware('check.permission:manage_account')
            ->name('settings.envelope_purge.update');

        // Notification Defaults
        Route::get('settings/notification_defaults', [AccountController::class, 'getNotificationDefaults'])
            ->middleware('check.permission:view_account')
            ->name('settings.notification_defaults.show');

        Route::put('settings/notification_defaults', [AccountController::class, 'updateNotificationDefaults'])
            ->middleware('check.permission:manage_account')
            ->name('settings.notification_defaults.update');

        // Password Rules (Account-level)
        Route::get('settings/password_rules', [AccountController::class, 'getPasswordRules'])
            ->middleware('check.permission:view_account')
            ->name('settings.password_rules.show');

        Route::put('settings/password_rules', [AccountController::class, 'updatePasswordRules'])
            ->middleware('check.permission:manage_account')
            ->name('settings.password_rules.update');

        // Tab Settings
        Route::get('settings/tab_settings', [AccountController::class, 'getTabSettings'])
            ->middleware('check.permission:view_account')
            ->name('settings.tab_settings.show');

        Route::put('settings/tab_settings', [AccountController::class, 'updateTabSettings'])
            ->middleware('check.permission:manage_account')
            ->name('settings.tab_settings.update');

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
    });
});

// Password Rules (Current User) - No accountId required
Route::middleware(['auth:api', 'throttle:api'])->group(function () {
    Route::get('current_user/password_rules', [AccountController::class, 'getCurrentUserPasswordRules'])
        ->name('current_user.password_rules');
});
