<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Exceptions\Custom\ResourceNotFoundException;
use App\Http\Controllers\Api\BaseController;
use App\Services\DiagnosticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * DiagnosticsController
 *
 * Handles logging and diagnostics operations including request logs,
 * audit trail, and system health monitoring.
 *
 * Endpoints: 8 total
 * - Request Logs: 3 endpoints
 * - Audit Logs: 3 endpoints
 * - System Diagnostics: 2 endpoints
 */
class DiagnosticsController extends BaseController
{
    protected DiagnosticsService $diagnosticsService;

    public function __construct(DiagnosticsService $diagnosticsService)
    {
        $this->diagnosticsService = $diagnosticsService;
    }

    // =========================================================================
    // REQUEST LOGS (3 endpoints)
    // =========================================================================

    /**
     * GET /accounts/{accountId}/request_logs
     * List request logs for account
     */
    public function indexRequestLogs(Request $request, int $accountId): JsonResponse
    {
        try {
            $filters = [
                'user_id' => $request->input('user_id'),
                'method' => $request->input('method'),
                'status' => $request->input('status'), // success, failed
                'from_date' => $request->input('from_date'),
                'to_date' => $request->input('to_date'),
                'sort_by' => $request->input('sort_by', 'created_date_time'),
                'sort_order' => $request->input('sort_order', 'desc'),
                'per_page' => $request->input('per_page', 50),
            ];

            $logs = $this->diagnosticsService->listRequestLogs($accountId, $filters);

            return $this->paginatedResponse($logs, 'Request logs retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to list request logs', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve request logs', 500);
        }
    }

    /**
     * GET /accounts/{accountId}/request_logs/{requestLogId}
     * Get specific request log
     */
    public function showRequestLog(int $accountId, string $requestLogId): JsonResponse
    {
        try {
            $log = $this->diagnosticsService->getRequestLog($accountId, $requestLogId);

            return $this->successResponse($log, 'Request log retrieved successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Failed to get request log', [
                'account_id' => $accountId,
                'request_log_id' => $requestLogId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve request log', 500);
        }
    }

    /**
     * DELETE /accounts/{accountId}/request_logs
     * Delete old request logs (cleanup)
     */
    public function deleteOldRequestLogs(Request $request, int $accountId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'days_old' => 'sometimes|integer|min:30|max:365',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $daysOld = $request->input('days_old', 90);
            $deletedCount = $this->diagnosticsService->deleteOldRequestLogs($accountId, $daysOld);

            return $this->successResponse([
                'deleted_count' => $deletedCount,
                'days_old' => $daysOld,
            ], 'Old request logs deleted successfully');

        } catch (\Exception $e) {
            Log::error('Failed to delete old request logs', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to delete old request logs', 500);
        }
    }

    // =========================================================================
    // AUDIT LOGS (3 endpoints)
    // =========================================================================

    /**
     * GET /accounts/{accountId}/audit_logs
     * List audit logs for account
     */
    public function indexAuditLogs(Request $request, int $accountId): JsonResponse
    {
        try {
            $filters = [
                'user_id' => $request->input('user_id'),
                'action' => $request->input('action'),
                'resource_type' => $request->input('resource_type'),
                'resource_id' => $request->input('resource_id'),
                'from_date' => $request->input('from_date'),
                'to_date' => $request->input('to_date'),
                'sort_by' => $request->input('sort_by', 'created_at'),
                'sort_order' => $request->input('sort_order', 'desc'),
                'per_page' => $request->input('per_page', 50),
            ];

            $logs = $this->diagnosticsService->listAuditLogs($accountId, $filters);

            return $this->paginatedResponse($logs, 'Audit logs retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to list audit logs', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve audit logs', 500);
        }
    }

    /**
     * GET /accounts/{accountId}/audit_logs/resource/{resourceType}/{resourceId}
     * Get audit log for a specific resource
     */
    public function showResourceAuditLog(
        int $accountId,
        string $resourceType,
        string $resourceId
    ): JsonResponse {
        try {
            $logs = $this->diagnosticsService->getResourceAuditLog($accountId, $resourceType, $resourceId);

            return $this->successResponse($logs, 'Resource audit log retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to get resource audit log', [
                'account_id' => $accountId,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve resource audit log', 500);
        }
    }

    /**
     * POST /accounts/{accountId}/audit_logs
     * Create audit log entry (for manual auditing)
     */
    public function storeAuditLog(Request $request, int $accountId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'action' => 'required|string|max:100',
                'resource_type' => 'nullable|string|max:100',
                'resource_id' => 'nullable|string|max:100',
                'user_id' => 'nullable|integer|exists:users,id',
                'old_values' => 'nullable|array',
                'new_values' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $data = array_merge($request->all(), [
                'account_id' => $accountId,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $log = $this->diagnosticsService->logAudit($data);

            return $this->createdResponse($log, 'Audit log created successfully');

        } catch (\Exception $e) {
            Log::error('Failed to create audit log', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to create audit log', 500);
        }
    }

    // =========================================================================
    // GLOBAL DIAGNOSTICS (5 endpoints)
    // =========================================================================

    /**
     * GET /diagnostics/request_logs
     * Gets the API request logging log files (global)
     */
    public function getGlobalRequestLogs(Request $request): JsonResponse
    {
        try {
            $filters = [
                'account_id' => $request->input('account_id'),
                'user_id' => $request->input('user_id'),
                'method' => $request->input('method'),
                'status' => $request->input('status'),
                'from_date' => $request->input('from_date'),
                'to_date' => $request->input('to_date'),
                'sort_by' => $request->input('sort_by', 'created_date_time'),
                'sort_order' => $request->input('sort_order', 'desc'),
                'per_page' => $request->input('per_page', 50),
            ];

            $logs = $this->diagnosticsService->listGlobalRequestLogs($filters);

            return $this->paginatedResponse($logs, 'Request logs retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to list global request logs', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve request logs', 500);
        }
    }

    /**
     * DELETE /diagnostics/request_logs
     * Deletes the request log files (global)
     */
    public function deleteGlobalRequestLogs(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'days_old' => 'sometimes|integer|min:30|max:365',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $daysOld = $request->input('days_old', 90);
            $deletedCount = $this->diagnosticsService->deleteGlobalRequestLogs($daysOld);

            return $this->successResponse([
                'deleted_count' => $deletedCount,
                'days_old' => $daysOld,
            ], 'Request logs deleted successfully');

        } catch (\Exception $e) {
            Log::error('Failed to delete global request logs', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to delete request logs', 500);
        }
    }

    /**
     * GET /diagnostics/request_logs/{requestLogId}
     * Gets a request logging log file (global)
     */
    public function getGlobalRequestLog(string $requestLogId): JsonResponse
    {
        try {
            $log = $this->diagnosticsService->getGlobalRequestLog($requestLogId);

            return $this->successResponse($log, 'Request log retrieved successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Failed to get global request log', [
                'request_log_id' => $requestLogId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve request log', 500);
        }
    }

    /**
     * GET /diagnostics/settings
     * Gets the API request logging settings
     */
    public function getDiagnosticsSettings(): JsonResponse
    {
        try {
            $settings = $this->diagnosticsService->getDiagnosticsSettings();

            return $this->successResponse($settings, 'Diagnostics settings retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to get diagnostics settings', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve diagnostics settings', 500);
        }
    }

    /**
     * PUT /diagnostics/settings
     * Enables or disables API request logging for troubleshooting
     */
    public function updateDiagnosticsSettings(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'api_request_logging' => 'sometimes|boolean',
                'api_request_log_remaining_days' => 'sometimes|integer|min:1|max:365',
                'api_request_log_max_entries' => 'sometimes|integer|min:1000|max:1000000',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $settings = $this->diagnosticsService->updateDiagnosticsSettings($request->all());

            return $this->successResponse($settings, 'Diagnostics settings updated successfully');

        } catch (\Exception $e) {
            Log::error('Failed to update diagnostics settings', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to update diagnostics settings', 500);
        }
    }

    // =========================================================================
    // SYSTEM DIAGNOSTICS (2 endpoints)
    // =========================================================================

    /**
     * GET /diagnostics/health
     * Get system health status
     */
    public function getSystemHealth(): JsonResponse
    {
        try {
            $health = $this->diagnosticsService->getSystemHealth();

            return $this->successResponse($health, 'System health retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to get system health', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve system health', 500);
        }
    }

    /**
     * GET /accounts/{accountId}/diagnostics/statistics
     * Get request statistics for account
     */
    public function getRequestStatistics(Request $request, int $accountId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'hours' => 'sometimes|integer|min:1|max:168', // Max 7 days
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $hours = $request->input('hours', 24);
            $statistics = $this->diagnosticsService->getRequestStatistics($accountId, $hours);

            return $this->successResponse($statistics, 'Request statistics retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to get request statistics', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve request statistics', 500);
        }
    }
}
