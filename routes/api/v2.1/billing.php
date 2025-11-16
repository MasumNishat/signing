<?php

use App\Http\Controllers\Api\V2_1\BillingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Billing API Routes (v2.1)
|--------------------------------------------------------------------------
|
| Billing and invoicing routes for the Signing API.
| Handles billing plans, charges, invoices, payments, and usage tracking.
|
| All account-specific routes require:
| - Authentication (OAuth 2.0 / API Key)
| - Account access verification
| - Appropriate billing permissions
|
*/

/*
|--------------------------------------------------------------------------
| Billing Plans (2 endpoints)
|--------------------------------------------------------------------------
| Public billing plan information - accessible to all authenticated users
*/

Route::prefix('billing_plans')->name('billing.plans.')->group(function () {
    // List billing plans
    Route::get('/', [BillingController::class, 'index'])
        ->middleware(['throttle:api'])
        ->name('index');

    // Get billing plan
    Route::get('/{planId}', [BillingController::class, 'show'])
        ->middleware(['throttle:api'])
        ->name('show');
});

/*
|--------------------------------------------------------------------------
| Account-Specific Billing Routes
|--------------------------------------------------------------------------
| All routes below require account access verification
*/

Route::prefix('accounts/{accountId}')->middleware(['check.account.access'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Account Billing Plan Management (6 endpoints)
    |--------------------------------------------------------------------------
    */

    Route::prefix('billing_plan')->name('billing.plan.')->group(function () {
        // Get account billing plan
        Route::get('/', [BillingController::class, 'getAccountBillingPlan'])
            ->middleware(['throttle:api', 'check.permission:billing.view'])
            ->name('show');

        // Update account billing plan
        Route::put('/', [BillingController::class, 'updateAccountBillingPlan'])
            ->middleware(['throttle:api', 'check.permission:billing.manage'])
            ->name('update');

        // Get credit card metadata
        Route::get('/credit_card', [BillingController::class, 'getCreditCard'])
            ->middleware(['throttle:api', 'check.permission:billing.view'])
            ->name('credit_card');

        // Get downgrade plan information
        Route::get('/downgrade', [BillingController::class, 'getDowngradeInfo'])
            ->middleware(['throttle:api', 'check.permission:billing.view'])
            ->name('downgrade.info');

        // Queue downgrade request
        Route::put('/downgrade', [BillingController::class, 'requestDowngrade'])
            ->middleware(['throttle:api', 'check.permission:billing.manage'])
            ->name('downgrade.request');

        // Purchase additional envelopes
        Route::put('/purchased_envelopes', [BillingController::class, 'purchaseEnvelopes'])
            ->middleware(['throttle:api', 'check.permission:billing.manage'])
            ->name('purchase_envelopes');
    });

    /*
    |--------------------------------------------------------------------------
    | Billing Charges (5 endpoints)
    |--------------------------------------------------------------------------
    */

    Route::prefix('billing_charges')->name('billing.charges.')->group(function () {
        // List charges
        Route::get('/', [BillingController::class, 'indexCharges'])
            ->middleware(['throttle:api', 'check.permission:billing.view'])
            ->name('index');

        // Create charge
        Route::post('/', [BillingController::class, 'storeCharge'])
            ->middleware(['throttle:api', 'check.permission:billing.manage'])
            ->name('store');

        // Get charge
        Route::get('/{chargeId}', [BillingController::class, 'showCharge'])
            ->middleware(['throttle:api', 'check.permission:billing.view'])
            ->name('show');

        // Update charge
        Route::put('/{chargeId}', [BillingController::class, 'updateCharge'])
            ->middleware(['throttle:api', 'check.permission:billing.manage'])
            ->name('update');

        // Delete charge
        Route::delete('/{chargeId}', [BillingController::class, 'destroyCharge'])
            ->middleware(['throttle:api', 'check.permission:billing.manage'])
            ->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Billing Invoices (6 endpoints)
    |--------------------------------------------------------------------------
    */

    // Get past due invoices (at account level per OpenAPI spec)
    Route::get('/billing_invoices_past_due', [BillingController::class, 'pastDueInvoices'])
        ->middleware(['throttle:api', 'check.permission:billing.view'])
        ->name('billing.invoices.past_due_root');

    Route::prefix('billing_invoices')->name('billing.invoices.')->group(function () {
        // List invoices
        Route::get('/', [BillingController::class, 'indexInvoices'])
            ->middleware(['throttle:api', 'check.permission:billing.view'])
            ->name('index');

        // Create invoice
        Route::post('/', [BillingController::class, 'storeInvoice'])
            ->middleware(['throttle:api', 'check.permission:billing.manage'])
            ->name('store');

        // Get past due invoices (MUST come before {invoiceId}) - kept for backwards compatibility
        Route::get('/past_due', [BillingController::class, 'pastDueInvoices'])
            ->middleware(['throttle:api', 'check.permission:billing.view'])
            ->name('past_due');

        // Get invoice
        Route::get('/{invoiceId}', [BillingController::class, 'showInvoice'])
            ->middleware(['throttle:api', 'check.permission:billing.view'])
            ->name('show');

        // Download invoice PDF
        Route::get('/{invoiceId}/pdf', [BillingController::class, 'downloadInvoicePdf'])
            ->middleware(['throttle:api', 'check.permission:billing.view'])
            ->name('pdf.download');

        // Update invoice
        Route::put('/{invoiceId}', [BillingController::class, 'updateInvoice'])
            ->middleware(['throttle:api', 'check.permission:billing.manage'])
            ->name('update');
    });

    /*
    |--------------------------------------------------------------------------
    | Billing Payments (6 endpoints)
    |--------------------------------------------------------------------------
    */

    Route::prefix('billing_payments')->name('billing.payments.')->group(function () {
        // List payments
        Route::get('/', [BillingController::class, 'indexPayments'])
            ->middleware(['throttle:api', 'check.permission:billing.view'])
            ->name('index');

        // Create payment
        Route::post('/', [BillingController::class, 'storePayment'])
            ->middleware(['throttle:api', 'check.permission:billing.manage'])
            ->name('store');

        // Get payment
        Route::get('/{paymentId}', [BillingController::class, 'showPayment'])
            ->middleware(['throttle:api', 'check.permission:billing.view'])
            ->name('show');

        // Update payment
        Route::put('/{paymentId}', [BillingController::class, 'updatePayment'])
            ->middleware(['throttle:api', 'check.permission:billing.manage'])
            ->name('update');

        // Process payment (mark as completed) - MUST come before DELETE
        Route::post('/{paymentId}/process', [BillingController::class, 'processPayment'])
            ->middleware(['throttle:api', 'check.permission:billing.manage'])
            ->name('process');

        // Delete payment
        Route::delete('/{paymentId}', [BillingController::class, 'destroyPayment'])
            ->middleware(['throttle:api', 'check.permission:billing.manage'])
            ->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Billing Summary & Usage (2 endpoints)
    |--------------------------------------------------------------------------
    */

    // Billing summary
    Route::get('/billing_summary', [BillingController::class, 'getBillingSummary'])
        ->middleware(['throttle:api', 'check.permission:billing.view'])
        ->name('billing.summary');

    // Billing usage
    Route::get('/billing_usage', [BillingController::class, 'getUsage'])
        ->middleware(['throttle:api', 'check.permission:billing.view'])
        ->name('billing.usage');
});
