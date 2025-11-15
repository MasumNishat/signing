<?php

use App\Http\Controllers\Api\V2_1\ConnectController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Connect API Routes
|--------------------------------------------------------------------------
|
| Connect (webhooks/events) routes for the Signing API.
| These routes handle webhook configuration, event publishing, logs, and failures.
|
| All routes are prefixed with: /api/v2.1/accounts/{accountId}/connect
|
*/

Route::prefix('accounts/{accountId}/connect')->name('connect.')->group(function () {
    // List all Connect configurations
    Route::get('/', [ConnectController::class, 'index'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('index');

    // Create Connect configuration
    Route::post('/', [ConnectController::class, 'store'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_manage_connect'])
        ->name('store');

    // Update Connect configuration (updates first found, or specific via connect_id in body)
    Route::put('/', [ConnectController::class, 'update'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_manage_connect'])
        ->name('update');

    // Retry Queue: Republish for multiple envelopes
    Route::put('/envelopes/retry_queue', [ConnectController::class, 'retryEnvelopes'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_manage_connect'])
        ->name('retry_envelopes');

    // Retry Queue: Republish for specific envelope
    Route::put('/envelopes/{envelopeId}/retry_queue', [ConnectController::class, 'retryEnvelope'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_manage_connect'])
        ->name('retry_envelope');

    // Get Connect failures
    Route::get('/failures', [ConnectController::class, 'failures'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('failures');

    // Delete Connect failure
    Route::delete('/failures/{failureId}', [ConnectController::class, 'deleteFailure'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_manage_connect'])
        ->name('delete_failure');

    // Get Connect logs (list)
    Route::get('/logs', [ConnectController::class, 'logs'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('logs');

    // Get specific Connect log
    Route::get('/logs/{logId}', [ConnectController::class, 'getLog'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('get_log');

    // Delete Connect log
    Route::delete('/logs/{logId}', [ConnectController::class, 'deleteLog'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_manage_connect'])
        ->name('delete_log');

    // OAuth Config: Get
    Route::get('/oauth', [ConnectController::class, 'getOAuthConfig'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('get_oauth');

    // OAuth Config: Create
    Route::post('/oauth', [ConnectController::class, 'createOAuthConfig'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_manage_connect'])
        ->name('create_oauth');

    // OAuth Config: Update
    Route::put('/oauth', [ConnectController::class, 'updateOAuthConfig'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_manage_connect'])
        ->name('update_oauth');

    // OAuth Config: Delete
    Route::delete('/oauth', [ConnectController::class, 'deleteOAuthConfig'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_manage_connect'])
        ->name('delete_oauth');

    // Get specific Connect configuration
    Route::get('/{connectId}', [ConnectController::class, 'show'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('show');

    // Delete Connect configuration
    Route::delete('/{connectId}', [ConnectController::class, 'destroy'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_manage_connect'])
        ->name('destroy');
});
