<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Bulk Send API Routes
|--------------------------------------------------------------------------
|
| Bulk send operation routes for the Signing API.
| These routes handle bulk send list creation and batch operations.
|
*/

Route::prefix('accounts/{accountId}/bulk-send')->name('bulk.')->group(function () {
    // Bulk send routes will be implemented in Phase 10
    // Placeholder for future implementation
});
