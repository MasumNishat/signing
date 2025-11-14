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
    // Envelope routes will be implemented in Phase 2
    // Placeholder for future implementation
});
