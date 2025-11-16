<?php

use App\Http\Controllers\Api\V2_1\RecipientController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Recipient API Routes
|--------------------------------------------------------------------------
|
| Recipient management endpoints for envelopes.
| All routes are prefixed with: /api/v2.1/accounts/{accountId}/envelopes/{envelopeId}
|
*/

Route::prefix('accounts/{accountId}/envelopes/{envelopeId}/recipients')->group(function () {
    // Bulk operations (must be before individual routes)
    Route::put('/bulk', [RecipientController::class, 'bulkUpdate'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('recipients.bulk.update');

    Route::delete('/bulk', [RecipientController::class, 'bulkDelete'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.delete'])
        ->name('recipients.bulk.delete');

    // List all recipients
    Route::get('/', [RecipientController::class, 'index'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('recipients.index');

    // Add recipients to envelope
    Route::post('/', [RecipientController::class, 'store'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('recipients.store');

    // Get specific recipient
    Route::get('/{recipientId}', [RecipientController::class, 'show'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('recipients.show');

    // Update recipient
    Route::put('/{recipientId}', [RecipientController::class, 'update'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('recipients.update');

    // Delete recipient
    Route::delete('/{recipientId}', [RecipientController::class, 'destroy'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.delete'])
        ->name('recipients.destroy');

    // Resend notification
    Route::post('/{recipientId}/resend', [RecipientController::class, 'resend'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('recipients.resend');

    // Generate signing URL
    Route::post('/{recipientId}/signing_url', [RecipientController::class, 'signingUrl'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('recipients.signing_url');

    // Document visibility
    Route::get('/{recipientId}/document_visibility', [RecipientController::class, 'getDocumentVisibility'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('recipients.document_visibility.get');

    Route::put('/{recipientId}/document_visibility', [RecipientController::class, 'updateDocumentVisibility'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('recipients.document_visibility.update');

    // Consumer disclosure
    Route::get('/{recipientId}/consumer_disclosure/{langCode?}', [RecipientController::class, 'getConsumerDisclosure'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('recipients.consumer_disclosure');

    // Identity proof token
    Route::post('/{recipientId}/identity_proof_token', [RecipientController::class, 'identityProofToken'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('recipients.identity_proof_token');

    // Signature image
    Route::get('/{recipientId}/signature_image', [RecipientController::class, 'getSignatureImage'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('recipients.signature_image.get');

    Route::put('/{recipientId}/signature_image', [RecipientController::class, 'updateSignatureImage'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('recipients.signature_image.update');

    // Initials image
    Route::get('/{recipientId}/initials_image', [RecipientController::class, 'getInitialsImage'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('recipients.initials_image.get');

    Route::put('/{recipientId}/initials_image', [RecipientController::class, 'updateInitialsImage'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('recipients.initials_image.update');

    // Recipient tabs
    Route::put('/{recipientId}/tabs', [RecipientController::class, 'updateTabs'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('recipients.tabs.update');

    Route::delete('/{recipientId}/tabs', [RecipientController::class, 'deleteTabs'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.delete'])
        ->name('recipients.tabs.delete');
});
