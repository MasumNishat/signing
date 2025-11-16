<?php

use App\Http\Controllers\Api\V2_1\CustomTabController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Custom Tabs API Routes
|--------------------------------------------------------------------------
|
| Routes for managing custom tab templates (reusable form field templates).
| Custom tabs allow organizations to create standardized fields for
| common data like Employee ID, Department, Project Code, etc.
|
| All routes are prefixed with: /api/v2.1/accounts/{accountId}/custom_tabs
|
| Total Endpoints: 8
|
*/

Route::prefix('accounts/{accountId}/custom_tabs')->middleware(['throttle:api', 'check.account.access'])->group(function () {
    // List custom tabs
    Route::get('/', [CustomTabController::class, 'index'])
        ->middleware('check.permission:custom_tabs.list')
        ->name('custom_tabs.index');

    // Create custom tab
    Route::post('/', [CustomTabController::class, 'store'])
        ->middleware('check.permission:custom_tabs.create')
        ->name('custom_tabs.store');

    // Get shared custom tabs
    Route::get('/shared', [CustomTabController::class, 'getShared'])
        ->middleware('check.permission:custom_tabs.list')
        ->name('custom_tabs.shared');

    // Get personal custom tabs
    Route::get('/personal', [CustomTabController::class, 'getPersonal'])
        ->middleware('check.permission:custom_tabs.list')
        ->name('custom_tabs.personal');

    // Get custom tabs by type
    Route::get('/type/{type}', [CustomTabController::class, 'getByType'])
        ->middleware('check.permission:custom_tabs.list')
        ->name('custom_tabs.by_type');

    // Get specific custom tab
    Route::get('/{customTabId}', [CustomTabController::class, 'show'])
        ->middleware('check.permission:custom_tabs.view')
        ->name('custom_tabs.show');

    // Update custom tab
    Route::put('/{customTabId}', [CustomTabController::class, 'update'])
        ->middleware('check.permission:custom_tabs.update')
        ->name('custom_tabs.update');

    // Delete custom tab
    Route::delete('/{customTabId}', [CustomTabController::class, 'destroy'])
        ->middleware('check.permission:custom_tabs.delete')
        ->name('custom_tabs.destroy');
});
