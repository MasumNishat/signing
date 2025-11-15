<?php

use App\Http\Controllers\Api\V2_1\WorkflowController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Workflow API Routes
|--------------------------------------------------------------------------
|
| Workflow and routing management endpoints for envelopes.
| All routes are prefixed with: /api/v2.1/accounts/{accountId}/envelopes/{envelopeId}/workflow
|
*/

Route::prefix('accounts/{accountId}/envelopes/{envelopeId}/workflow')->group(function () {
    // Start workflow (with optional scheduling)
    Route::post('/start', [WorkflowController::class, 'start'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.send'])
        ->name('workflow.start');

    // Pause workflow
    Route::post('/pause', [WorkflowController::class, 'pause'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('workflow.pause');

    // Resume paused workflow
    Route::post('/resume', [WorkflowController::class, 'resume'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.update'])
        ->name('workflow.resume');

    // Cancel workflow
    Route::post('/cancel', [WorkflowController::class, 'cancel'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:envelope.delete'])
        ->name('workflow.cancel');

    // Get workflow status
    Route::get('/status', [WorkflowController::class, 'status'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('workflow.status');

    // Get current active recipients
    Route::get('/recipients/current', [WorkflowController::class, 'currentRecipients'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('workflow.recipients.current');

    // Get pending recipients
    Route::get('/recipients/pending', [WorkflowController::class, 'pendingRecipients'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('workflow.recipients.pending');
});
