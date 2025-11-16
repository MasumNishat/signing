<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V2_1\FolderController;

/*
|--------------------------------------------------------------------------
| Folder API Routes
|--------------------------------------------------------------------------
|
| Routes for managing folders and organizing envelopes.
| All routes are prefixed with /api/v2.1/accounts/{accountId}
|
| Total endpoints: 4
|
*/

Route::middleware(['throttle:api', 'check.account.access'])->group(function () {

    // ===========================
    // Folders (4 endpoints)
    // ===========================

    // Get list of folders
    Route::get('/folders', [FolderController::class, 'index'])
        ->name('api.v2.1.accounts.folders.index');

    // Get folder items (envelopes)
    Route::get('/folders/{folderId}', [FolderController::class, 'show'])
        ->name('api.v2.1.accounts.folders.show');

    // Move envelopes to folder
    Route::put('/folders/{folderId}', [FolderController::class, 'update'])
        ->middleware('check.permission:manage_envelopes')
        ->name('api.v2.1.accounts.folders.update');

    // Search folders
    Route::get('/search_folders/{searchFolderId}', [FolderController::class, 'searchFolders'])
        ->name('api.v2.1.accounts.search-folders.show');
});
