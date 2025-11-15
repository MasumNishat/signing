<?php

use App\Http\Controllers\Api\V2_1\NotaryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Notary API Routes
|--------------------------------------------------------------------------
|
| Routes for notary and eNotary functionality.
| Supports notary configuration, session management, and journal entries.
|
| Total Endpoints: 3
|
*/

Route::prefix('accounts/{accountId}/notary')->middleware(['throttle:api', 'check.account.access'])->group(function () {
    // Notary configuration
    Route::get('/configuration', [NotaryController::class, 'getConfiguration'])
        ->name('notary.configuration');

    // Create notary session
    Route::post('/sessions', [NotaryController::class, 'createSession'])
        ->middleware('check.permission:notary.manage')
        ->name('notary.sessions.create');

    // Get notary journal
    Route::get('/journal', [NotaryController::class, 'getJournal'])
        ->middleware('check.permission:notary.view')
        ->name('notary.journal');
});
