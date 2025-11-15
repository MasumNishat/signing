<?php

use App\Http\Controllers\Api\V2_1\PowerFormController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PowerForm API Routes (v2.1)
|--------------------------------------------------------------------------
|
| PowerForm management routes for the Signing API.
| PowerForms are public-facing forms that allow envelope creation without
| requiring user authentication.
|
| All protected routes require:
| - Authentication (OAuth 2.0 / API Key)
| - Account access verification
| - Appropriate permissions
|
*/

// Protected PowerForm Management Routes
Route::prefix('accounts/{accountId}/powerforms')->name('powerforms.')->group(function () {

    // List PowerForms
    Route::get('/', [PowerFormController::class, 'index'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:powerforms.view'])
        ->name('index');

    // Create PowerForm
    Route::post('/', [PowerFormController::class, 'store'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:powerforms.create'])
        ->name('store');

    // Get PowerForm
    Route::get('/{powerformId}', [PowerFormController::class, 'show'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:powerforms.view'])
        ->name('show');

    // Update PowerForm
    Route::put('/{powerformId}', [PowerFormController::class, 'update'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:powerforms.manage'])
        ->name('update');

    // Delete PowerForm
    Route::delete('/{powerformId}', [PowerFormController::class, 'destroy'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:powerforms.delete'])
        ->name('destroy');

    // Get PowerForm submissions
    Route::get('/{powerformId}/submissions', [PowerFormController::class, 'submissions'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:powerforms.view'])
        ->name('submissions');

    // Get PowerForm statistics
    Route::get('/{powerformId}/statistics', [PowerFormController::class, 'statistics'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:powerforms.view'])
        ->name('statistics');
});

/*
|--------------------------------------------------------------------------
| Public PowerForm Routes (No Authentication Required)
|--------------------------------------------------------------------------
*/

// Submit PowerForm (public endpoint)
Route::post('/public/powerforms/{powerformId}/submit', [PowerFormController::class, 'submit'])
    ->middleware(['throttle:api'])
    ->name('powerforms.submit.public');
