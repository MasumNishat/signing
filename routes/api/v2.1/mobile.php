<?php

use App\Http\Controllers\Api\V2_1\MobileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile API Routes
|--------------------------------------------------------------------------
|
| Mobile-optimized endpoints for signing and viewing on mobile devices.
| Provides touch-friendly interfaces and responsive components.
|
| Total Endpoints: 4
|
*/

Route::prefix('accounts/{accountId}/mobile')->middleware(['throttle:api', 'check.account.access'])->group(function () {
    // Mobile envelope list
    Route::get('/envelopes', [MobileController::class, 'getEnvelopes'])
        ->name('mobile.envelopes');

    // Mobile envelope view
    Route::get('/envelopes/{envelopeId}/view', [MobileController::class, 'getEnvelopeView'])
        ->name('mobile.envelope.view');

    // Mobile signing
    Route::post('/envelopes/{envelopeId}/sign', [MobileController::class, 'signEnvelope'])
        ->name('mobile.envelope.sign');

    // Mobile settings
    Route::get('/settings', [MobileController::class, 'getSettings'])
        ->name('mobile.settings');
});
