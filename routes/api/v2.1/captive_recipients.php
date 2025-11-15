<?php

use App\Http\Controllers\Api\V2_1\CaptiveRecipientController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Captive Recipients API Routes
|--------------------------------------------------------------------------
|
| Routes for managing captive recipients (embedded signing).
| All routes require authentication and account access.
|
| Total Endpoints: 3
|
*/

Route::middleware(['throttle:api', 'check.account.access'])->group(function () {
    // List captive recipients
    Route::get('/', [CaptiveRecipientController::class, 'index'])
        ->middleware('check.permission:captive_recipients.list');

    // Create captive recipients
    Route::post('/', [CaptiveRecipientController::class, 'store'])
        ->middleware('check.permission:captive_recipients.create');

    // Delete a captive recipient
    Route::delete('/{recipientId}', [CaptiveRecipientController::class, 'destroy'])
        ->middleware('check.permission:captive_recipients.delete');
});
