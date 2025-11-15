<?php

use App\Http\Controllers\Api\V2_1\TemplateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Template API Routes
|--------------------------------------------------------------------------
|
| Template management routes for the Signing API.
| These routes handle template creation, modification, and usage.
|
| All routes are prefixed with: /api/v2.1/accounts/{accountId}/templates
|
*/

Route::prefix('accounts/{accountId}/templates')->name('templates.')->group(function () {
    // List templates
    Route::get('/', [TemplateController::class, 'index'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('index');

    // Create template
    Route::post('/', [TemplateController::class, 'store'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_create_templates'])
        ->name('store');

    // Get specific template
    Route::get('/{templateId}', [TemplateController::class, 'show'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('show');

    // Update template
    Route::put('/{templateId}', [TemplateController::class, 'update'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_update_templates'])
        ->name('update');

    // Delete template
    Route::delete('/{templateId}', [TemplateController::class, 'destroy'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_delete_templates'])
        ->name('destroy');

    // Create envelope from template
    Route::post('/{templateId}/envelopes', [TemplateController::class, 'createEnvelope'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_send_envelopes'])
        ->name('create_envelope');

    // Share template with user
    Route::post('/{templateId}/share', [TemplateController::class, 'share'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_share_templates'])
        ->name('share');

    // Unshare template from user
    Route::delete('/{templateId}/share/{userId}', [TemplateController::class, 'unshare'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_share_templates'])
        ->name('unshare');

    // Add template to favorites
    Route::post('/{templateId}/favorites', [TemplateController::class, 'addFavorite'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('add_favorite');

    // Remove template from favorites
    Route::delete('/{templateId}/favorites', [TemplateController::class, 'removeFavorite'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('remove_favorite');
});
