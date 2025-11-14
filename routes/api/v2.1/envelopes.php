<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Envelope API Routes
|--------------------------------------------------------------------------
|
| Envelope management routes for the Signing API.
| These routes handle envelope creation, sending, signing, and tracking.
|
*/

Route::prefix('accounts/{accountId}/envelopes')->name('envelopes.')->group(function () {

    // Envelope statistics (must come before {envelopeId} route)
    Route::get('statistics', [\App\Http\Controllers\Api\V2_1\EnvelopeController::class, 'statistics'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('statistics');

    // List envelopes
    Route::get('/', [\App\Http\Controllers\Api\V2_1\EnvelopeController::class, 'index'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('index');

    // Create envelope
    Route::post('/', [\App\Http\Controllers\Api\V2_1\EnvelopeController::class, 'store'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.create'])
        ->name('store');

    // Get specific envelope
    Route::get('{envelopeId}', [\App\Http\Controllers\Api\V2_1\EnvelopeController::class, 'show'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('show');

    // Update envelope
    Route::put('{envelopeId}', [\App\Http\Controllers\Api\V2_1\EnvelopeController::class, 'update'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('update');

    // Delete envelope
    Route::delete('{envelopeId}', [\App\Http\Controllers\Api\V2_1\EnvelopeController::class, 'destroy'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.delete'])
        ->name('destroy');

    // Send envelope
    Route::post('{envelopeId}/send', [\App\Http\Controllers\Api\V2_1\EnvelopeController::class, 'send'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.send'])
        ->name('send');

    // Void envelope
    Route::post('{envelopeId}/void', [\App\Http\Controllers\Api\V2_1\EnvelopeController::class, 'void'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.void'])
        ->name('void');

    // Notification settings
    Route::get('{envelopeId}/notification', [\App\Http\Controllers\Api\V2_1\EnvelopeController::class, 'getNotification'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('notification.get');

    Route::put('{envelopeId}/notification', [\App\Http\Controllers\Api\V2_1\EnvelopeController::class, 'updateNotification'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('notification.update');

    // Email settings
    Route::get('{envelopeId}/email_settings', [\App\Http\Controllers\Api\V2_1\EnvelopeController::class, 'getEmailSettings'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('email_settings.get');

    Route::put('{envelopeId}/email_settings', [\App\Http\Controllers\Api\V2_1\EnvelopeController::class, 'updateEmailSettings'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('email_settings.update');

    // Custom fields
    Route::get('{envelopeId}/custom_fields', [\App\Http\Controllers\Api\V2_1\EnvelopeController::class, 'getCustomFields'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('custom_fields.get');

    Route::post('{envelopeId}/custom_fields', [\App\Http\Controllers\Api\V2_1\EnvelopeController::class, 'createCustomFields'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('custom_fields.create');

    Route::put('{envelopeId}/custom_fields', [\App\Http\Controllers\Api\V2_1\EnvelopeController::class, 'updateCustomFields'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('custom_fields.update');

    Route::delete('{envelopeId}/custom_fields', [\App\Http\Controllers\Api\V2_1\EnvelopeController::class, 'deleteCustomFields'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.delete'])
        ->name('custom_fields.delete');

    // Envelope lock
    Route::get('{envelopeId}/lock', [\App\Http\Controllers\Api\V2_1\EnvelopeController::class, 'getLock'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('lock.get');

    Route::post('{envelopeId}/lock', [\App\Http\Controllers\Api\V2_1\EnvelopeController::class, 'createLock'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('lock.create');

    Route::put('{envelopeId}/lock', [\App\Http\Controllers\Api\V2_1\EnvelopeController::class, 'updateLock'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('lock.update');

    Route::delete('{envelopeId}/lock', [\App\Http\Controllers\Api\V2_1\EnvelopeController::class, 'deleteLock'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('lock.delete');
});
