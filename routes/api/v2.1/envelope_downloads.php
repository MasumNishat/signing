<?php

use App\Http\Controllers\Api\V2_1\EnvelopeDownloadController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Envelope Download API Routes
|--------------------------------------------------------------------------
|
| Envelope document download, PDF generation, and certificate endpoints.
| All routes are prefixed with: /api/v2.1/accounts/{accountId}/envelopes/{envelopeId}
|
*/

Route::prefix('accounts/{accountId}/envelopes/{envelopeId}')->group(function () {
    // Download combined PDF
    Route::get('/documents/combined', [EnvelopeDownloadController::class, 'downloadCombinedPdf'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('envelopes.download.combined');

    // Download specific document
    Route::get('/documents/{documentId}/download', [EnvelopeDownloadController::class, 'downloadDocument'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('envelopes.download.document');

    // Get certificate of completion
    Route::get('/certificate', [EnvelopeDownloadController::class, 'certificate'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('envelopes.certificate');

    // Get form data
    Route::get('/form_data', [EnvelopeDownloadController::class, 'formData'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('envelopes.form_data');
});
