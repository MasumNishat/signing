<?php

use App\Http\Controllers\Api\V2_1\WorkspaceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Workspace API Routes (v2.1)
|--------------------------------------------------------------------------
|
| Workspace management routes for the Signing API.
| Handles workspaces, folders, and file operations.
|
| All routes require:
| - Authentication (OAuth 2.0 / API Key)
| - Account access verification
| - Appropriate workspace permissions
|
*/

Route::prefix('accounts/{accountId}')->middleware(['check.account.access'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Workspace CRUD (5 endpoints)
    |--------------------------------------------------------------------------
    */

    Route::prefix('workspaces')->name('workspaces.')->group(function () {
        // List workspaces
        Route::get('/', [WorkspaceController::class, 'index'])
            ->middleware(['throttle:api', 'check.permission:workspaces.view'])
            ->name('index');

        // Create workspace
        Route::post('/', [WorkspaceController::class, 'store'])
            ->middleware(['throttle:api', 'check.permission:workspaces.manage'])
            ->name('store');

        // Get workspace
        Route::get('/{workspaceId}', [WorkspaceController::class, 'show'])
            ->middleware(['throttle:api', 'check.permission:workspaces.view'])
            ->name('show');

        // Update workspace
        Route::put('/{workspaceId}', [WorkspaceController::class, 'update'])
            ->middleware(['throttle:api', 'check.permission:workspaces.manage'])
            ->name('update');

        // Delete workspace
        Route::delete('/{workspaceId}', [WorkspaceController::class, 'destroy'])
            ->middleware(['throttle:api', 'check.permission:workspaces.manage'])
            ->name('destroy');

        /*
        |--------------------------------------------------------------------------
        | Folder Operations (2 endpoints)
        |--------------------------------------------------------------------------
        */

        // Get folder contents
        Route::get('/{workspaceId}/folders/{folderId}', [WorkspaceController::class, 'showFolder'])
            ->middleware(['throttle:api', 'check.permission:workspaces.view'])
            ->name('folders.show');

        // Delete folder
        Route::delete('/{workspaceId}/folders/{folderId}', [WorkspaceController::class, 'destroyFolder'])
            ->middleware(['throttle:api', 'check.permission:workspaces.manage'])
            ->name('folders.destroy');

        /*
        |--------------------------------------------------------------------------
        | File Operations (4 endpoints)
        |--------------------------------------------------------------------------
        */

        // Upload file
        Route::post('/{workspaceId}/folders/{folderId}/files', [WorkspaceController::class, 'storeFile'])
            ->middleware(['throttle:api', 'check.permission:workspaces.manage'])
            ->name('files.store');

        // Get file
        Route::get('/{workspaceId}/folders/{folderId}/files/{fileId}', [WorkspaceController::class, 'showFile'])
            ->middleware(['throttle:api', 'check.permission:workspaces.view'])
            ->name('files.show');

        // Update file metadata
        Route::put('/{workspaceId}/folders/{folderId}/files/{fileId}', [WorkspaceController::class, 'updateFile'])
            ->middleware(['throttle:api', 'check.permission:workspaces.manage'])
            ->name('files.update');

        // Get file pages
        Route::get('/{workspaceId}/folders/{folderId}/files/{fileId}/pages', [WorkspaceController::class, 'showFilePages'])
            ->middleware(['throttle:api', 'check.permission:workspaces.view'])
            ->name('files.pages');
    });
});
