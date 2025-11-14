<?php

use App\Http\Controllers\Api\V2_1\DocumentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Document API Routes
|--------------------------------------------------------------------------
|
| Document management endpoints for envelopes.
| All routes are prefixed with: /api/v2.1/accounts/{accountId}/envelopes/{envelopeId}
|
*/

Route::prefix('accounts/{accountId}/envelopes/{envelopeId}/documents')->group(function () {
    // List all documents in envelope
    Route::get('/', [DocumentController::class, 'index'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('documents.index');

    // Add documents to envelope
    Route::post('/', [DocumentController::class, 'store'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('documents.store');

    // Reorder documents
    Route::put('/reorder', [DocumentController::class, 'reorder'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('documents.reorder');

    // Get specific document (metadata or download)
    Route::get('/{documentId}', [DocumentController::class, 'show'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('documents.show');

    // Update document
    Route::put('/{documentId}', [DocumentController::class, 'update'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('documents.update');

    // Delete document
    Route::delete('/{documentId}', [DocumentController::class, 'destroy'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.delete'])
        ->name('documents.destroy');

    // Get temporary download URL
    Route::post('/{documentId}/download_url', [DocumentController::class, 'getDownloadUrl'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('documents.download_url');
});
