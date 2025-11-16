<?php

use App\Http\Controllers\Api\V2_1\TemplateController;
use App\Http\Controllers\Api\V2_1\TemplateDocumentController;
use App\Http\Controllers\Api\V2_1\TemplateRecipientController;
use App\Http\Controllers\Api\V2_1\TemplateCustomFieldController;
use App\Http\Controllers\Api\V2_1\TemplateLockController;
use App\Http\Controllers\Api\V2_1\TemplateNotificationController;
use App\Http\Controllers\Api\V2_1\TemplateTabController;
use App\Http\Controllers\Api\V2_1\TemplateBulkController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Template API Routes
|--------------------------------------------------------------------------
|
| Template management routes for the Signing API.
| These routes handle template creation, modification, and usage.
|
| All routes are prefixed with: /api/v2.1/accounts/{accountId}/templates
|
*/

Route::prefix('accounts/{accountId}/templates')->name('templates.')->group(function () {
    // List templates
    Route::get('/', [TemplateController::class, 'index'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('index');

    // Create template
    Route::post('/', [TemplateController::class, 'store'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_create_templates'])
        ->name('store');

    // Get specific template
    Route::get('/{templateId}', [TemplateController::class, 'show'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('show');

    // Update template
    Route::put('/{templateId}', [TemplateController::class, 'update'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_update_templates'])
        ->name('update');

    // Delete template
    Route::delete('/{templateId}', [TemplateController::class, 'destroy'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_delete_templates'])
        ->name('destroy');

    // Bulk operations (must come before {templateId} routes)
    Route::post('/bulk_create', [TemplateBulkController::class, 'bulkCreate'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_create_templates'])
        ->name('bulk_create');

    Route::put('/bulk_update', [TemplateBulkController::class, 'bulkUpdate'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_update_templates'])
        ->name('bulk_update');

    Route::delete('/bulk_delete', [TemplateBulkController::class, 'bulkDelete'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_delete_templates'])
        ->name('bulk_delete');

    // Create envelope from template
    Route::post('/{templateId}/envelopes', [TemplateController::class, 'createEnvelope'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_send_envelopes'])
        ->name('create_envelope');

    // Share template with user
    Route::post('/{templateId}/share', [TemplateController::class, 'share'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_share_templates'])
        ->name('share');

    // Unshare template from user
    Route::delete('/{templateId}/share/{userId}', [TemplateController::class, 'unshare'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_share_templates'])
        ->name('unshare');

    // Add template to favorites
    Route::post('/{templateId}/favorites', [TemplateController::class, 'addFavorite'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('add_favorite');

    // Remove template from favorites
    Route::delete('/{templateId}/favorites', [TemplateController::class, 'removeFavorite'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('remove_favorite');

    // =========================================================================
    // TEMPLATE DOCUMENTS
    // =========================================================================

    // Get all template documents
    Route::get('/{templateId}/documents', [TemplateDocumentController::class, 'index'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('documents.index');

    // Add documents to template
    Route::post('/{templateId}/documents', [TemplateDocumentController::class, 'store'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_update_templates'])
        ->name('documents.store');

    // Replace all template documents
    Route::put('/{templateId}/documents', [TemplateDocumentController::class, 'update'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_update_templates'])
        ->name('documents.update');

    // Delete all template documents
    Route::delete('/{templateId}/documents', [TemplateDocumentController::class, 'destroy'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_update_templates'])
        ->name('documents.destroy');

    // Get specific template document
    Route::get('/{templateId}/documents/{documentId}', [TemplateDocumentController::class, 'show'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('documents.show');

    // Update specific template document
    Route::put('/{templateId}/documents/{documentId}', [TemplateDocumentController::class, 'updateSingle'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_update_templates'])
        ->name('documents.update_single');

    // =========================================================================
    // TEMPLATE RECIPIENTS
    // =========================================================================

    // Get all template recipients
    Route::get('/{templateId}/recipients', [TemplateRecipientController::class, 'index'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('recipients.index');

    // Add recipients to template
    Route::post('/{templateId}/recipients', [TemplateRecipientController::class, 'store'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_update_templates'])
        ->name('recipients.store');

    // Replace all template recipients
    Route::put('/{templateId}/recipients', [TemplateRecipientController::class, 'update'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_update_templates'])
        ->name('recipients.update');

    // Delete all template recipients
    Route::delete('/{templateId}/recipients', [TemplateRecipientController::class, 'destroy'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_update_templates'])
        ->name('recipients.destroy');

    // Get specific template recipient
    Route::get('/{templateId}/recipients/{recipientId}', [TemplateRecipientController::class, 'show'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('recipients.show');

    // Update specific template recipient
    Route::put('/{templateId}/recipients/{recipientId}', [TemplateRecipientController::class, 'updateSingle'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_update_templates'])
        ->name('recipients.update_single');

    // =========================================================================
    // TEMPLATE CUSTOM FIELDS
    // =========================================================================

    // Get template custom fields
    Route::get('/{templateId}/custom_fields', [TemplateCustomFieldController::class, 'index'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('custom_fields.index');

    // Create template custom fields
    Route::post('/{templateId}/custom_fields', [TemplateCustomFieldController::class, 'store'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_update_templates'])
        ->name('custom_fields.store');

    // Update template custom fields
    Route::put('/{templateId}/custom_fields', [TemplateCustomFieldController::class, 'update'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_update_templates'])
        ->name('custom_fields.update');

    // Delete template custom fields
    Route::delete('/{templateId}/custom_fields', [TemplateCustomFieldController::class, 'destroy'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_update_templates'])
        ->name('custom_fields.destroy');

    // =========================================================================
    // TEMPLATE LOCK
    // =========================================================================

    // Get template lock status
    Route::get('/{templateId}/lock', [TemplateLockController::class, 'show'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('lock.show');

    // Create template lock
    Route::post('/{templateId}/lock', [TemplateLockController::class, 'store'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_update_templates'])
        ->name('lock.store');

    // Update template lock
    Route::put('/{templateId}/lock', [TemplateLockController::class, 'update'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_update_templates'])
        ->name('lock.update');

    // Delete template lock
    Route::delete('/{templateId}/lock', [TemplateLockController::class, 'destroy'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_update_templates'])
        ->name('lock.destroy');

    // =========================================================================
    // TEMPLATE NOTIFICATION
    // =========================================================================

    // Get template notification settings
    Route::get('/{templateId}/notification', [TemplateNotificationController::class, 'show'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('notification.show');

    // Update template notification settings
    Route::put('/{templateId}/notification', [TemplateNotificationController::class, 'update'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_update_templates'])
        ->name('notification.update');

    // =========================================================================
    // TEMPLATE TABS
    // =========================================================================

    // Get all template tabs
    Route::get('/{templateId}/tabs', [TemplateTabController::class, 'index'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('tabs.index');

    // Add tabs to template
    Route::post('/{templateId}/tabs', [TemplateTabController::class, 'store'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_update_templates'])
        ->name('tabs.store');

    // Replace all template tabs
    Route::put('/{templateId}/tabs', [TemplateTabController::class, 'update'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_update_templates'])
        ->name('tabs.update');

    // Delete all template tabs
    Route::delete('/{templateId}/tabs', [TemplateTabController::class, 'destroy'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_update_templates'])
        ->name('tabs.destroy');

    // Get specific template tab
    Route::get('/{templateId}/tabs/{tabId}', [TemplateTabController::class, 'show'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('tabs.show');

    // Update specific template tab
    Route::put('/{templateId}/tabs/{tabId}', [TemplateTabController::class, 'updateSingle'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_update_templates'])
        ->name('tabs.update_single');

    // =========================================================================
    // TEMPLATE DOCUMENT TABS (Phase 1.1)
    // =========================================================================

    // Get tabs for a template document
    Route::get('/{templateId}/documents/{documentId}/tabs', [TemplateTabController::class, 'getDocumentTabs'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('documents.tabs.index');

    // Add tabs to a template document
    Route::post('/{templateId}/documents/{documentId}/tabs', [TemplateTabController::class, 'addDocumentTabs'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_update_templates'])
        ->name('documents.tabs.store');

    // Update tabs on a template document
    Route::put('/{templateId}/documents/{documentId}/tabs', [TemplateTabController::class, 'updateDocumentTabs'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_update_templates'])
        ->name('documents.tabs.update');

    // Delete tabs from a template document
    Route::delete('/{templateId}/documents/{documentId}/tabs', [TemplateTabController::class, 'deleteDocumentTabs'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_update_templates'])
        ->name('documents.tabs.destroy');

    // =========================================================================
    // TEMPLATE RECIPIENT TABS (Phase 1.1)
    // =========================================================================

    // Get tabs for a template recipient
    Route::get('/{templateId}/recipients/{recipientId}/tabs', [TemplateTabController::class, 'getRecipientTabs'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('recipients.tabs.index');

    // Add tabs to a template recipient
    Route::post('/{templateId}/recipients/{recipientId}/tabs', [TemplateTabController::class, 'addRecipientTabs'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_update_templates'])
        ->name('recipients.tabs.store');

    // Update tabs for a template recipient
    Route::put('/{templateId}/recipients/{recipientId}/tabs', [TemplateTabController::class, 'updateRecipientTabs'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_update_templates'])
        ->name('recipients.tabs.update');

    // Delete tabs from a template recipient
    Route::delete('/{templateId}/recipients/{recipientId}/tabs', [TemplateTabController::class, 'deleteRecipientTabs'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_update_templates'])
        ->name('recipients.tabs.destroy');
});
