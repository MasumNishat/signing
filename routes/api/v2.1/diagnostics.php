<?php

use App\Http\Controllers\Api\V2_1\DiagnosticsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Diagnostics API Routes (v2.1)
|--------------------------------------------------------------------------
|
| Logging and diagnostics routes for the Signing API.
| Handles request logs, audit trail, and system health monitoring.
|
| Request/Audit log routes require:
| - Authentication (OAuth 2.0 / API Key)
| - Account access verification
| - Admin permissions
|
| System health endpoint is public for monitoring.
|
*/

/*
|--------------------------------------------------------------------------
| Global Diagnostics (5 endpoints) - Requires Admin Access
|--------------------------------------------------------------------------
*/

// Request logs (global)
Route::get('/diagnostics/request_logs', [DiagnosticsController::class, 'getGlobalRequestLogs'])
    ->middleware(['throttle:api', 'check.permission:diagnostics.view'])
    ->name('diagnostics.global.request_logs.index');

Route::get('/diagnostics/request_logs/{requestLogId}', [DiagnosticsController::class, 'getGlobalRequestLog'])
    ->middleware(['throttle:api', 'check.permission:diagnostics.view'])
    ->name('diagnostics.global.request_logs.show');

Route::delete('/diagnostics/request_logs', [DiagnosticsController::class, 'deleteGlobalRequestLogs'])
    ->middleware(['throttle:api', 'check.permission:diagnostics.manage'])
    ->name('diagnostics.global.request_logs.delete');

// Diagnostics settings
Route::get('/diagnostics/settings', [DiagnosticsController::class, 'getDiagnosticsSettings'])
    ->middleware(['throttle:api', 'check.permission:diagnostics.view'])
    ->name('diagnostics.global.settings.get');

Route::put('/diagnostics/settings', [DiagnosticsController::class, 'updateDiagnosticsSettings'])
    ->middleware(['throttle:api', 'check.permission:diagnostics.manage'])
    ->name('diagnostics.global.settings.update');

/*
|--------------------------------------------------------------------------
| System Health (1 endpoint) - Public
|--------------------------------------------------------------------------
*/

// Get system health status (no auth required for monitoring)
Route::get('/diagnostics/health', [DiagnosticsController::class, 'getSystemHealth'])
    ->middleware(['throttle:api'])
    ->name('diagnostics.health');

/*
|--------------------------------------------------------------------------
| Account-Specific Diagnostics
|--------------------------------------------------------------------------
*/

Route::prefix('accounts/{accountId}')->middleware(['check.account.access'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Request Logs (3 endpoints)
    |--------------------------------------------------------------------------
    */

    // List request logs
    Route::get('/request_logs', [DiagnosticsController::class, 'indexRequestLogs'])
        ->middleware(['throttle:api', 'check.permission:diagnostics.view'])
        ->name('diagnostics.request_logs.index');

    // Get specific request log
    Route::get('/request_logs/{requestLogId}', [DiagnosticsController::class, 'showRequestLog'])
        ->middleware(['throttle:api', 'check.permission:diagnostics.view'])
        ->name('diagnostics.request_logs.show');

    // Delete old request logs (cleanup)
    Route::delete('/request_logs', [DiagnosticsController::class, 'deleteOldRequestLogs'])
        ->middleware(['throttle:api', 'check.permission:diagnostics.manage'])
        ->name('diagnostics.request_logs.delete');

    /*
    |--------------------------------------------------------------------------
    | Audit Logs (3 endpoints)
    |--------------------------------------------------------------------------
    */

    // List audit logs
    Route::get('/audit_logs', [DiagnosticsController::class, 'indexAuditLogs'])
        ->middleware(['throttle:api', 'check.permission:diagnostics.view'])
        ->name('diagnostics.audit_logs.index');

    // Get resource audit log
    Route::get('/audit_logs/resource/{resourceType}/{resourceId}', [DiagnosticsController::class, 'showResourceAuditLog'])
        ->middleware(['throttle:api', 'check.permission:diagnostics.view'])
        ->name('diagnostics.audit_logs.resource');

    // Create audit log entry
    Route::post('/audit_logs', [DiagnosticsController::class, 'storeAuditLog'])
        ->middleware(['throttle:api', 'check.permission:diagnostics.manage'])
        ->name('diagnostics.audit_logs.store');

    /*
    |--------------------------------------------------------------------------
    | Statistics (1 endpoint)
    |--------------------------------------------------------------------------
    */

    // Get request statistics
    Route::get('/diagnostics/statistics', [DiagnosticsController::class, 'getRequestStatistics'])
        ->middleware(['throttle:api', 'check.permission:diagnostics.view'])
        ->name('diagnostics.statistics');
});
