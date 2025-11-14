<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Connect API Routes
|--------------------------------------------------------------------------
|
| Connect (webhooks/events) routes for the Signing API.
| These routes handle Connect configuration and event subscriptions.
|
*/

Route::prefix('accounts/{accountId}/connect')->name('connect.')->group(function () {
    // Connect routes will be implemented in Phase 6
    // Placeholder for future implementation
});
