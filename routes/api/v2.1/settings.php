<?php

use App\Http\Controllers\Api\V2_1\SettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Settings API Routes (v2.1)
|--------------------------------------------------------------------------
|
| Account settings and configuration routes for the Signing API.
| Handles account settings, supported languages, and file types.
|
| All routes require:
| - Authentication (OAuth 2.0 / API Key)
| - Account access verification
| - Appropriate settings permissions
|
*/

Route::prefix('accounts/{accountId}')->middleware(['check.account.access'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Account Settings (2 endpoints)
    |--------------------------------------------------------------------------
    */

    // Get account settings
    Route::get('/settings', [SettingsController::class, 'getSettings'])
        ->middleware(['throttle:api', 'check.permission:settings.view'])
        ->name('settings.get');

    // Update account settings
    Route::put('/settings', [SettingsController::class, 'updateSettings'])
        ->middleware(['throttle:api', 'check.permission:settings.manage'])
        ->name('settings.update');

    /*
    |--------------------------------------------------------------------------
    | Reference Data (3 endpoints)
    |--------------------------------------------------------------------------
    */

    // Get supported languages
    Route::get('/supported_languages', [SettingsController::class, 'getSupportedLanguages'])
        ->middleware(['throttle:api'])
        ->name('settings.languages');

    // Get unsupported file types
    Route::get('/unsupported_file_types', [SettingsController::class, 'getUnsupportedFileTypes'])
        ->middleware(['throttle:api'])
        ->name('settings.unsupported_file_types');

    // Get supported file types
    Route::get('/supported_file_types', [SettingsController::class, 'getSupportedFileTypes'])
        ->middleware(['throttle:api'])
        ->name('settings.supported_file_types');
});
