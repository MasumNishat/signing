<?php

use App\Http\Controllers\Api\V2_1\SharedAccessController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Shared Access API Routes
|--------------------------------------------------------------------------
|
| Shared access routes for the Signing API.
| These routes handle sharing envelopes and templates with other users.
|
| Total Endpoints: 2
| - GET /accounts/{accountId}/shared_access - Gets shared item status
| - PUT /accounts/{accountId}/shared_access - Sets shared access information
|
*/

Route::middleware(['throttle:api', 'check.account.access'])->group(function () {

    Route::prefix('accounts/{accountId}/shared_access')->name('shared_access.')->group(function () {

        // Get shared item status for users
        Route::get('/', [SharedAccessController::class, 'index'])
            ->middleware('check.permission:view_envelopes')
            ->name('index');

        // Set shared access information for users
        Route::put('/', [SharedAccessController::class, 'update'])
            ->middleware('check.permission:manage_envelopes')
            ->name('update');
    });
});
