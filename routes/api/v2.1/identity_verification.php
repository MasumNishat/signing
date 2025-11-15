<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V2_1\IdentityVerificationController;

/*
|--------------------------------------------------------------------------
| Identity Verification API Routes
|--------------------------------------------------------------------------
|
| Routes for managing identity verification workflows.
| All routes are prefixed with /api/v2.1/accounts/{accountId}
|
| Total endpoints: 1
|
*/

Route::middleware(['throttle:api', 'check.account.access'])->group(function () {

    // ===========================
    // Identity Verification Workflows (1 endpoint)
    // ===========================

    // Get identity verification workflows for account
    Route::get('/identity_verification', [IdentityVerificationController::class, 'getIdentityVerificationOptions'])
        ->name('api.v2.1.accounts.identity-verification.index');
});
