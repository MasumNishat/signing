<?php

use App\Http\Controllers\Api\V2_1\DocumentFieldController;
use App\Http\Controllers\Api\V2_1\DocumentPageController;
use App\Http\Controllers\Api\V2_1\EnvelopeDocumentTabController;
use App\Http\Controllers\Api\V2_1\HtmlDefinitionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Document Operations API Routes (Phase 2.1)
|--------------------------------------------------------------------------
|
| Advanced document operations for envelopes and templates.
| Includes fields, pages, tabs, and HTML definitions.
|
| Total Endpoints: 30
|
*/

Route::middleware(['throttle:api', 'check.account.access'])->group(function () {

    // =========================================================================
    // DOCUMENT FIELDS (4 endpoints)
    // =========================================================================

    Route::prefix('accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/fields')->group(function () {
        Route::get('/', [DocumentFieldController::class, 'index'])->name('envelope.documents.fields.index');
        Route::post('/', [DocumentFieldController::class, 'store'])->middleware('check.permission:can_update_envelopes')->name('envelope.documents.fields.store');
        Route::put('/', [DocumentFieldController::class, 'update'])->middleware('check.permission:can_update_envelopes')->name('envelope.documents.fields.update');
        Route::delete('/', [DocumentFieldController::class, 'destroy'])->middleware('check.permission:can_update_envelopes')->name('envelope.documents.fields.destroy');
    });

    // =========================================================================
    // DOCUMENT PAGES (8 endpoints)
    // =========================================================================

    Route::prefix('accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/pages')->group(function () {
        Route::get('/', [DocumentPageController::class, 'index'])->name('envelope.documents.pages.index');
        Route::get('/{pageNumber}', [DocumentPageController::class, 'show'])->name('envelope.documents.pages.show');
        Route::delete('/{pageNumber}', [DocumentPageController::class, 'destroy'])->middleware('check.permission:can_update_envelopes')->name('envelope.documents.pages.destroy');
        Route::get('/{pageNumber}/page_image', [DocumentPageController::class, 'getPageImage'])->name('envelope.documents.pages.image');
        Route::put('/{pageNumber}/page_image', [DocumentPageController::class, 'rotatePageImage'])->middleware('check.permission:can_update_envelopes')->name('envelope.documents.pages.rotate');
        Route::get('/{pageNumber}/tabs', [DocumentPageController::class, 'getPageTabs'])->name('envelope.documents.pages.tabs');
        Route::post('/{pageNumber}/move', [DocumentPageController::class, 'movePage'])->middleware('check.permission:can_update_envelopes')->name('envelope.documents.pages.move');
        Route::post('/insert', [DocumentPageController::class, 'insertPage'])->middleware('check.permission:can_update_envelopes')->name('envelope.documents.pages.insert');
    });

    // =========================================================================
    // ENVELOPE DOCUMENT TABS (4 endpoints)
    // =========================================================================

    Route::prefix('accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/tabs')->group(function () {
        Route::get('/', [EnvelopeDocumentTabController::class, 'getDocumentTabs'])->name('envelope.documents.tabs.index');
        Route::post('/', [EnvelopeDocumentTabController::class, 'addDocumentTabs'])->middleware('check.permission:can_update_envelopes')->name('envelope.documents.tabs.store');
        Route::put('/', [EnvelopeDocumentTabController::class, 'updateDocumentTabs'])->middleware('check.permission:can_update_envelopes')->name('envelope.documents.tabs.update');
        Route::delete('/', [EnvelopeDocumentTabController::class, 'deleteDocumentTabs'])->middleware('check.permission:can_update_envelopes')->name('envelope.documents.tabs.destroy');
    });

    // =========================================================================
    // ENVELOPE RECIPIENT TABS (4 endpoints)
    // =========================================================================

    Route::prefix('accounts/{accountId}/envelopes/{envelopeId}/recipients/{recipientId}/tabs')->group(function () {
        Route::get('/', [EnvelopeDocumentTabController::class, 'getRecipientTabs'])->name('envelope.recipients.tabs.index');
        Route::post('/', [EnvelopeDocumentTabController::class, 'addRecipientTabs'])->middleware('check.permission:can_update_envelopes')->name('envelope.recipients.tabs.store');
        Route::put('/', [EnvelopeDocumentTabController::class, 'updateRecipientTabs'])->middleware('check.permission:can_update_envelopes')->name('envelope.recipients.tabs.update');
        Route::delete('/', [EnvelopeDocumentTabController::class, 'deleteRecipientTabs'])->middleware('check.permission:can_update_envelopes')->name('envelope.recipients.tabs.destroy');
    });

    // =========================================================================
    // HTML DEFINITIONS - ENVELOPE (5 endpoints)
    // =========================================================================

    Route::prefix('accounts/{accountId}/envelopes/{envelopeId}')->group(function () {
        Route::get('/html_definitions', [HtmlDefinitionController::class, 'getEnvelopeGlobalHtmlDef'])->name('envelope.html_definitions.global');
        Route::get('/documents/{documentId}/html_definitions', [HtmlDefinitionController::class, 'getEnvelopeDocumentHtmlDef'])->name('envelope.documents.html_definitions.show');
        Route::put('/documents/{documentId}/html_definitions', [HtmlDefinitionController::class, 'updateEnvelopeDocumentHtmlDef'])->middleware('check.permission:can_update_envelopes')->name('envelope.documents.html_definitions.update');
        Route::delete('/documents/{documentId}/html_definitions', [HtmlDefinitionController::class, 'deleteEnvelopeDocumentHtmlDef'])->middleware('check.permission:can_update_envelopes')->name('envelope.documents.html_definitions.destroy');
        Route::post('/documents/{documentId}/responsive_html_preview', [HtmlDefinitionController::class, 'previewEnvelopeResponsive'])->name('envelope.documents.html_preview');
    });

    // =========================================================================
    // HTML DEFINITIONS - TEMPLATE (5 endpoints)
    // =========================================================================

    Route::prefix('accounts/{accountId}/templates/{templateId}')->group(function () {
        Route::get('/html_definitions', [HtmlDefinitionController::class, 'getTemplateGlobalHtmlDef'])->name('template.html_definitions.global');
        Route::get('/documents/{documentId}/html_definitions', [HtmlDefinitionController::class, 'getTemplateDocumentHtmlDef'])->name('template.documents.html_definitions.show');
        Route::put('/documents/{documentId}/html_definitions', [HtmlDefinitionController::class, 'updateTemplateDocumentHtmlDef'])->middleware('check.permission:can_update_templates')->name('template.documents.html_definitions.update');
        Route::delete('/documents/{documentId}/html_definitions', [HtmlDefinitionController::class, 'deleteTemplateDocumentHtmlDef'])->middleware('check.permission:can_update_templates')->name('template.documents.html_definitions.destroy');
        Route::post('/documents/{documentId}/responsive_html_preview', [HtmlDefinitionController::class, 'previewTemplateResponsive'])->name('template.documents.html_preview');
    });
});
