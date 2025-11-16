<?php

use App\Http\Controllers\Api\V2_1\AdvancedFeaturesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Advanced Features API Routes
|--------------------------------------------------------------------------
|
| Advanced and specialized enterprise features.
| Includes batch operations, workflows, compliance, and integrations.
|
| Total Endpoints: 9
|
*/

// Batch send
Route::post('accounts/{accountId}/envelopes/batch_send', [AdvancedFeaturesController::class, 'batchSend'])
    ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.send'])
    ->name('envelopes.batch_send');

// Workflow creation
Route::post('accounts/{accountId}/workflows/create', [AdvancedFeaturesController::class, 'createWorkflow'])
    ->middleware(['throttle:api', 'check.account.access', 'check.permission:workflows.create'])
    ->name('workflows.create');

// Compliance audit trail
Route::get('accounts/{accountId}/compliance/audit_trail', [AdvancedFeaturesController::class, 'getComplianceAuditTrail'])
    ->middleware(['throttle:api', 'check.account.access'])
    ->name('compliance.audit_trail');

// Template cloning
Route::post('accounts/{accountId}/templates/clone', [AdvancedFeaturesController::class, 'cloneTemplate'])
    ->middleware(['throttle:api', 'check.account.access', 'check.permission:can_create_templates'])
    ->name('templates.clone');

// Envelope scheduling
Route::post('accounts/{accountId}/envelopes/schedule', [AdvancedFeaturesController::class, 'scheduleEnvelope'])
    ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.send'])
    ->name('envelopes.schedule');

// Send reminders
Route::post('accounts/{accountId}/envelopes/{envelopeId}/remind', [AdvancedFeaturesController::class, 'sendReminder'])
    ->middleware(['throttle:api', 'check.account.access'])
    ->name('envelopes.remind');

// Dashboard analytics
Route::get('accounts/{accountId}/analytics/dashboard', [AdvancedFeaturesController::class, 'getDashboard'])
    ->middleware(['throttle:api', 'check.account.access'])
    ->name('analytics.dashboard');

// Webhook testing
Route::post('accounts/{accountId}/integrations/webhook_test', [AdvancedFeaturesController::class, 'testWebhook'])
    ->middleware(['throttle:api', 'check.account.access', 'check.permission:integrations.manage'])
    ->name('integrations.webhook_test');

// Data export
Route::post('accounts/{accountId}/data/export_all', [AdvancedFeaturesController::class, 'exportAllData'])
    ->middleware(['throttle:api', 'check.account.access', 'check.permission:data.export'])
    ->name('data.export_all');
