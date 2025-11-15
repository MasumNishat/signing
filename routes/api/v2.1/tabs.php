<?php

use App\Http\Controllers\Api\V2_1\TabController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tab API Routes
|--------------------------------------------------------------------------
|
| Tab (form field) management endpoints for envelope recipients.
| All routes are prefixed with: /api/v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients/{recipientId}
|
*/

Route::prefix('accounts/{accountId}/envelopes/{envelopeId}/recipients/{recipientId}/tabs')->group(function () {
    // List all tabs for a recipient
    Route::get('/', [TabController::class, 'index'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('tabs.index');

    // Add tabs to a recipient
    Route::post('/', [TabController::class, 'store'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('tabs.store');

    // Get specific tab
    Route::get('/{tabId}', [TabController::class, 'show'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('tabs.show');

    // Update tab
    Route::put('/{tabId}', [TabController::class, 'update'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('tabs.update');

    // Delete tab
    Route::delete('/{tabId}', [TabController::class, 'destroy'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.delete'])
        ->name('tabs.destroy');
});
