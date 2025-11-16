<?php

use App\Http\Controllers\Api\V2_1\BulkEnvelopeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Bulk Envelope API Routes
|--------------------------------------------------------------------------
|
| Bulk operations for envelopes, recipients, and documents.
| All routes are prefixed with: /api/v2.1/accounts/{accountId}/envelopes/bulk
|
*/

Route::prefix('accounts/{accountId}/envelopes/bulk')->group(function () {
    // Bulk status operations
    Route::put('/status', [BulkEnvelopeController::class, 'bulkStatusUpdate'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('bulk.envelopes.status.update');

    Route::post('/void', [BulkEnvelopeController::class, 'bulkVoid'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.delete'])
        ->name('bulk.envelopes.void');

    Route::post('/resend', [BulkEnvelopeController::class, 'bulkResend'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('bulk.envelopes.resend');

    // Bulk recipient operations
    Route::put('/recipients', [BulkEnvelopeController::class, 'bulkRecipientUpdate'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('bulk.recipients.update');

    Route::post('/recipients/resend', [BulkEnvelopeController::class, 'bulkRecipientResend'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('bulk.recipients.resend');

    Route::delete('/recipients', [BulkEnvelopeController::class, 'bulkRecipientRemove'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.delete'])
        ->name('bulk.recipients.remove');

    // Bulk document operations
    Route::post('/documents', [BulkEnvelopeController::class, 'bulkDocumentAdd'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('bulk.documents.add');

    Route::put('/documents', [BulkEnvelopeController::class, 'bulkDocumentReplace'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('bulk.documents.replace');

    Route::delete('/documents', [BulkEnvelopeController::class, 'bulkDocumentDelete'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.delete'])
        ->name('bulk.documents.delete');

    Route::post('/download', [BulkEnvelopeController::class, 'bulkDocumentDownload'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('bulk.documents.download');
});
