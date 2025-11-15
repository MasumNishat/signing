<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V2_1\SignatureController;

/*
|--------------------------------------------------------------------------
| Signature & Seal API Routes
|--------------------------------------------------------------------------
|
| Routes for managing signatures, signature images, signature providers, and seals.
| All routes are prefixed with /api/v2.1/accounts/{accountId}
|
| Total endpoints: 20 (1 provider + 9 account + 9 user + 1 seal)
|
*/

Route::prefix('accounts/{accountId}')->middleware(['throttle:api', 'check.account.access'])->group(function () {

    // ===========================
    // Signature Providers (1 endpoint)
    // ===========================

    // Get signature providers
    Route::get('signatureProviders', [SignatureController::class, 'getSignatureProviders'])
        ->name('api.v2.1.accounts.signature-providers.index');

    // ===========================
    // Account-Level Signatures (9 endpoints)
    // ===========================

    // List account signatures
    Route::get('signatures', [SignatureController::class, 'getAccountSignatures'])
        ->name('api.v2.1.accounts.signatures.index');

    // Create account signatures
    Route::post('signatures', [SignatureController::class, 'createOrUpdateAccountSignatures'])
        ->middleware('check.permission:manage_signatures')
        ->name('api.v2.1.accounts.signatures.store');

    // Update account signatures (bulk)
    Route::put('signatures', [SignatureController::class, 'createOrUpdateAccountSignatures'])
        ->middleware('check.permission:manage_signatures')
        ->name('api.v2.1.accounts.signatures.bulk-update');

    // Get specific account signature
    Route::get('signatures/{signatureId}', [SignatureController::class, 'getAccountSignature'])
        ->name('api.v2.1.accounts.signatures.show');

    // Update specific account signature
    Route::put('signatures/{signatureId}', [SignatureController::class, 'updateAccountSignature'])
        ->middleware('check.permission:manage_signatures')
        ->name('api.v2.1.accounts.signatures.update');

    // Delete account signature
    Route::delete('signatures/{signatureId}', [SignatureController::class, 'deleteAccountSignature'])
        ->middleware('check.permission:manage_signatures')
        ->name('api.v2.1.accounts.signatures.destroy');

    // Get account signature image
    Route::get('signatures/{signatureId}/{imageType}', [SignatureController::class, 'getAccountSignatureImage'])
        ->where('imageType', 'signature_image|initials_image|stamp_image')
        ->name('api.v2.1.accounts.signatures.image.show');

    // Upload account signature image
    Route::put('signatures/{signatureId}/{imageType}', [SignatureController::class, 'uploadAccountSignatureImage'])
        ->where('imageType', 'signature_image|initials_image|stamp_image')
        ->middleware('check.permission:manage_signatures')
        ->name('api.v2.1.accounts.signatures.image.upload');

    // Delete account signature image
    Route::delete('signatures/{signatureId}/{imageType}', [SignatureController::class, 'deleteAccountSignatureImage'])
        ->where('imageType', 'signature_image|initials_image|stamp_image')
        ->middleware('check.permission:manage_signatures')
        ->name('api.v2.1.accounts.signatures.image.destroy');

    // ===========================
    // User-Level Signatures (9 endpoints)
    // ===========================

    // List user signatures
    Route::get('users/{userId}/signatures', [SignatureController::class, 'getUserSignatures'])
        ->name('api.v2.1.accounts.users.signatures.index');

    // Create user signatures
    Route::post('users/{userId}/signatures', [SignatureController::class, 'createOrUpdateUserSignatures'])
        ->middleware('check.permission:manage_users')
        ->name('api.v2.1.accounts.users.signatures.store');

    // Update user signatures (bulk)
    Route::put('users/{userId}/signatures', [SignatureController::class, 'createOrUpdateUserSignatures'])
        ->middleware('check.permission:manage_users')
        ->name('api.v2.1.accounts.users.signatures.bulk-update');

    // Get specific user signature
    Route::get('users/{userId}/signatures/{signatureId}', [SignatureController::class, 'getUserSignature'])
        ->name('api.v2.1.accounts.users.signatures.show');

    // Update specific user signature
    Route::put('users/{userId}/signatures/{signatureId}', [SignatureController::class, 'updateUserSignature'])
        ->middleware('check.permission:manage_users')
        ->name('api.v2.1.accounts.users.signatures.update');

    // Delete user signature
    Route::delete('users/{userId}/signatures/{signatureId}', [SignatureController::class, 'deleteUserSignature'])
        ->middleware('check.permission:manage_users')
        ->name('api.v2.1.accounts.users.signatures.destroy');

    // Get user signature image
    Route::get('users/{userId}/signatures/{signatureId}/{imageType}', [SignatureController::class, 'getUserSignatureImage'])
        ->where('imageType', 'signature_image|initials_image|stamp_image')
        ->name('api.v2.1.accounts.users.signatures.image.show');

    // Upload user signature image
    Route::put('users/{userId}/signatures/{signatureId}/{imageType}', [SignatureController::class, 'uploadUserSignatureImage'])
        ->where('imageType', 'signature_image|initials_image|stamp_image')
        ->middleware('check.permission:manage_users')
        ->name('api.v2.1.accounts.users.signatures.image.upload');

    // Delete user signature image
    Route::delete('users/{userId}/signatures/{signatureId}/{imageType}', [SignatureController::class, 'deleteUserSignatureImage'])
        ->where('imageType', 'signature_image|initials_image|stamp_image')
        ->middleware('check.permission:manage_users')
        ->name('api.v2.1.accounts.users.signatures.image.destroy');

    // ===========================
    // Seals (1 endpoint)
    // ===========================

    // Get account seals
    Route::get('seals', [SignatureController::class, 'getSeals'])
        ->name('api.v2.1.accounts.seals.index');
});
