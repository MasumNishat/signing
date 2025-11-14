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
});
