<?php

use App\Http\Controllers\Api\V2_1\DocumentGenerationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Document Generation API Routes
|--------------------------------------------------------------------------
|
| Routes for generating documents from templates and envelopes.
| Supports merge fields, multiple formats, and previews.
|
| Total Endpoints: 3
|
*/

// Template-based generation
Route::post('accounts/{accountId}/templates/{templateId}/generate', [DocumentGenerationController::class, 'generateFromTemplate'])
    ->middleware(['throttle:api', 'check.account.access'])
    ->name('templates.generate');

// Envelope document generation
Route::post('accounts/{accountId}/envelopes/{envelopeId}/documents/generate', [DocumentGenerationController::class, 'generateForEnvelope'])
    ->middleware(['throttle:api', 'check.account.access'])
    ->name('envelopes.documents.generate');

// Document preview
Route::get('accounts/{accountId}/documents/{documentId}/preview', [DocumentGenerationController::class, 'getPreview'])
    ->middleware(['throttle:api', 'check.account.access'])
    ->name('documents.preview');
