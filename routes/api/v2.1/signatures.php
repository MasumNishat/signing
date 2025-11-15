<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Signature API Routes
|--------------------------------------------------------------------------
|
| Signature management routes for the Signing API.
| These routes handle signature adoption, images, and providers.
|
*/

Route::prefix('accounts/{accountId}/signatures')->name('signatures.')->group(function () {
    // Signature routes will be implemented in Phase 9
    // Placeholder for future implementation
});
