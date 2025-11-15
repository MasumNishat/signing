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

    // Bulk add documents to envelope
    Route::put('/', [DocumentController::class, 'bulkAdd'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('documents.bulk_add');

    // Bulk delete documents from envelope
    Route::delete('/', [DocumentController::class, 'bulkDestroy'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.delete'])
        ->name('documents.bulk_destroy');

    // Combined documents (must be before /{documentId} to avoid conflict)
    Route::get('/combined', [DocumentController::class, 'getCombined'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('documents.combined');

    // Certificate of completion (must be before /{documentId} to avoid conflict)
    Route::get('/certificate', [DocumentController::class, 'getCertificate'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('documents.certificate');

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

    // Document fields (tabs) operations
    Route::get('/{documentId}/fields', [DocumentController::class, 'getFields'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('documents.fields.index');

    Route::post('/{documentId}/fields', [DocumentController::class, 'addFields'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('documents.fields.store');

    // Bulk update document fields
    Route::put('/{documentId}/fields', [DocumentController::class, 'bulkUpdateFields'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('documents.fields.bulk_update');

    // Bulk delete document fields
    Route::delete('/{documentId}/fields', [DocumentController::class, 'bulkDestroyFields'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.delete'])
        ->name('documents.fields.bulk_destroy');

    Route::put('/{documentId}/fields/{tabId}', [DocumentController::class, 'updateField'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('documents.fields.update');

    Route::delete('/{documentId}/fields/{tabId}', [DocumentController::class, 'deleteField'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.delete'])
        ->name('documents.fields.destroy');

    // Document pages operations
    Route::get('/{documentId}/pages', [DocumentController::class, 'getPages'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('documents.pages.index');

    Route::delete('/{documentId}/pages', [DocumentController::class, 'deletePages'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('documents.pages.destroy');

    // Delete specific page
    Route::delete('/{documentId}/pages/{pageNumber}', [DocumentController::class, 'deletePage'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('documents.pages.delete_page');

    // Get page image
    Route::get('/{documentId}/pages/{pageNumber}/page_image', [DocumentController::class, 'getPageImage'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('documents.pages.page_image');

    // Rotate page image
    Route::put('/{documentId}/pages/{pageNumber}/page_image', [DocumentController::class, 'rotatePageImage'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('documents.pages.rotate_page_image');

    // Get tabs on specific page
    Route::get('/{documentId}/pages/{pageNumber}/tabs', [DocumentController::class, 'getPageTabs'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('documents.pages.tabs');

    // Document tabs operations
    Route::get('/{documentId}/tabs', [DocumentController::class, 'getTabs'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('documents.tabs.index');

    Route::post('/{documentId}/tabs', [DocumentController::class, 'addTabs'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('documents.tabs.store');

    Route::put('/{documentId}/tabs', [DocumentController::class, 'updateTabs'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('documents.tabs.update');

    Route::delete('/{documentId}/tabs', [DocumentController::class, 'deleteTabs'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.delete'])
        ->name('documents.tabs.destroy');

    // Document templates operations
    Route::get('/{documentId}/templates', [DocumentController::class, 'getTemplates'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('documents.templates.index');

    Route::post('/{documentId}/templates', [DocumentController::class, 'addTemplates'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('documents.templates.store');

    Route::delete('/{documentId}/templates/{templateId}', [DocumentController::class, 'deleteTemplate'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.delete'])
        ->name('documents.templates.destroy');

    // HTML definitions and responsive preview
    Route::get('/{documentId}/html_definitions', [DocumentController::class, 'getHtmlDefinition'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('documents.html_definitions');

    Route::post('/{documentId}/responsive_html_preview', [DocumentController::class, 'generateResponsiveHtmlPreview'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('documents.responsive_html_preview');
});
