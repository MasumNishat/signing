<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Billing API Routes
|--------------------------------------------------------------------------
|
| Billing and invoicing routes for the Signing API.
| These routes handle billing plans, invoices, and payments.
|
*/

Route::prefix('accounts/{accountId}/billing')->name('billing.')->group(function () {
    // Billing routes will be implemented in Phase 5
    // Placeholder for future implementation
});
