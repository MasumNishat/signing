<?php

use App\Http\Controllers\Api\V2_1\ChunkedUploadController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Chunked Upload API Routes
|--------------------------------------------------------------------------
|
| Routes for managing chunked file uploads
| All routes are prefixed with: /api/v2.1/accounts/{accountId}
|
*/

Route::prefix('accounts/{accountId}/chunked_uploads')->group(function () {
    // Initiate new chunked upload
    Route::post('/', [ChunkedUploadController::class, 'store'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('chunked_uploads.store');

    // Get chunked upload metadata
    Route::get('/{chunkedUploadId}', [ChunkedUploadController::class, 'show'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('chunked_uploads.show');

    // Commit chunked upload (finalize and integrity check)
    Route::put('/{chunkedUploadId}', [ChunkedUploadController::class, 'update'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('chunked_uploads.update');

    // Delete chunked upload
    Route::delete('/{chunkedUploadId}', [ChunkedUploadController::class, 'destroy'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('chunked_uploads.destroy');

    // Add chunk part to existing upload
    Route::put('/{chunkedUploadId}/{chunkedUploadPartSeq}', [ChunkedUploadController::class, 'addPart'])
        ->middleware(['throttle:api', 'check.account.access'])
        ->name('chunked_uploads.add_part');
});
