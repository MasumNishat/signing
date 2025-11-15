<?php

use App\Http\Controllers\Api\V2_1\BulkSendController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Bulk Send API Routes (v2.1)
|--------------------------------------------------------------------------
|
| Bulk envelope sending operations including batch management and
| recipient list management.
|
| All routes require:
| - Authentication (OAuth 2.0 / API Key)
| - Account access verification
| - Appropriate permissions
|
*/

Route::prefix('accounts/{accountId}')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Bulk Send Batch Endpoints
    |--------------------------------------------------------------------------
    */

    // List bulk send batches
    Route::get('/bulk_send_batch', [BulkSendController::class, 'listBatches'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:bulk_send.view'])
        ->name('bulk_send.batches.index');

    // Get bulk send batch
    Route::get('/bulk_send_batch/{batchId}', [BulkSendController::class, 'getBatch'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:bulk_send.view'])
        ->name('bulk_send.batches.show');

    // Update bulk send batch
    Route::put('/bulk_send_batch/{batchId}', [BulkSendController::class, 'updateBatch'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:bulk_send.manage'])
        ->name('bulk_send.batches.update');

    // Get batch envelopes
    Route::get('/bulk_send_batch/{batchId}/envelopes', [BulkSendController::class, 'getBatchEnvelopes'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:bulk_send.view'])
        ->name('bulk_send.batches.envelopes');

    // Perform batch action (pause, resume, cancel, resend_failed)
    Route::put('/bulk_send_batch/{batchId}/{action}', [BulkSendController::class, 'performBatchAction'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:bulk_send.manage'])
        ->where('action', 'pause|resume|cancel|resend_failed')
        ->name('bulk_send.batches.action');

    /*
    |--------------------------------------------------------------------------
    | Bulk Send List Endpoints
    |--------------------------------------------------------------------------
    */

    // List bulk send lists
    Route::get('/bulk_send_lists', [BulkSendController::class, 'listLists'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:bulk_send.view'])
        ->name('bulk_send.lists.index');

    // Create bulk send list
    Route::post('/bulk_send_lists', [BulkSendController::class, 'createList'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:bulk_send.create'])
        ->name('bulk_send.lists.store');

    // Get bulk send list
    Route::get('/bulk_send_lists/{listId}', [BulkSendController::class, 'getList'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:bulk_send.view'])
        ->name('bulk_send.lists.show');

    // Update bulk send list
    Route::put('/bulk_send_lists/{listId}', [BulkSendController::class, 'updateList'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:bulk_send.manage'])
        ->name('bulk_send.lists.update');

    // Delete bulk send list
    Route::delete('/bulk_send_lists/{listId}', [BulkSendController::class, 'deleteList'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:bulk_send.delete'])
        ->name('bulk_send.lists.destroy');

    // Send bulk envelopes using list
    Route::post('/bulk_send_lists/{listId}/send', [BulkSendController::class, 'sendBulkEnvelopes'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:bulk_send.send'])
        ->name('bulk_send.lists.send');

    // Test bulk send (validate without sending)
    Route::post('/bulk_send_lists/{listId}/test', [BulkSendController::class, 'testBulkSend'])
        ->middleware(['throttle:api', 'check.account.access', 'check.permission:bulk_send.view'])
        ->name('bulk_send.lists.test');
});
