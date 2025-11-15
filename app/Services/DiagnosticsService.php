<?php

namespace App\Services;

use App\Exceptions\Custom\ResourceNotFoundException;
use App\Models\AuditLog;
use App\Models\RequestLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * DiagnosticsService
 *
 * Handles business logic for logging and diagnostics operations including:
 * - Request logging
 * - Audit trail
 * - System diagnostics and health checks
 */
class DiagnosticsService
{
    // =========================================================================
    // REQUEST LOGS
    // =========================================================================

    /**
     * List request logs for an account.
     */
    public function listRequestLogs(int $accountId, array $filters = []): LengthAwarePaginator
    {
        $query = RequestLog::query()
            ->where('account_id', $accountId)
            ->with(['user:id,name,email']);

        // Filter by user
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Filter by method
        if (!empty($filters['method'])) {
            $query->withMethod($filters['method']);
        }

        // Filter by status (successful/failed)
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'success') {
                $query->successful();
            } elseif ($filters['status'] === 'failed') {
                $query->failed();
            }
        }

        // Filter by date range
        if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
            $query->betweenDates($filters['from_date'], $filters['to_date']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_date_time';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $filters['per_page'] ?? 50;
        return $query->paginate($perPage);
    }

    /**
     * Get a specific request log.
     */
    public function getRequestLog(int $accountId, string $requestLogId): RequestLog
    {
        $log = RequestLog::where('request_log_id', $requestLogId)
            ->where('account_id', $accountId)
            ->with(['user:id,name,email'])
            ->first();

        if (!$log) {
            throw new ResourceNotFoundException('Request log not found');
        }

        return $log;
    }

    /**
     * Delete old request logs (cleanup).
     */
    public function deleteOldRequestLogs(int $accountId, int $daysOld = 90): int
    {
        $cutoffDate = now()->subDays($daysOld);

        return RequestLog::where('account_id', $accountId)
            ->where('created_date_time', '<', $cutoffDate)
            ->delete();
    }

    // =========================================================================
    // AUDIT LOGS
    // =========================================================================

    /**
     * List audit logs for an account.
     */
    public function listAuditLogs(int $accountId, array $filters = []): LengthAwarePaginator
    {
        $query = AuditLog::query()
            ->where('account_id', $accountId)
            ->with(['user:id,name,email']);

        // Filter by user
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Filter by action
        if (!empty($filters['action'])) {
            $query->withAction($filters['action']);
        }

        // Filter by resource type
        if (!empty($filters['resource_type'])) {
            $query->forResourceType($filters['resource_type']);
        }

        // Filter by resource ID
        if (!empty($filters['resource_type']) && !empty($filters['resource_id'])) {
            $query->forResource($filters['resource_type'], $filters['resource_id']);
        }

        // Filter by date range
        if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
            $query->betweenDates($filters['from_date'], $filters['to_date']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $filters['per_page'] ?? 50;
        return $query->paginate($perPage);
    }

    /**
     * Get audit log for a specific resource.
     */
    public function getResourceAuditLog(
        int $accountId,
        string $resourceType,
        string $resourceId
    ): Collection {
        return AuditLog::where('account_id', $accountId)
            ->forResource($resourceType, $resourceId)
            ->with(['user:id,name,email'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Create an audit log entry.
     */
    public function logAudit(array $data): AuditLog
    {
        return AuditLog::create($data);
    }

    // =========================================================================
    // SYSTEM DIAGNOSTICS
    // =========================================================================

    /**
     * Get system health status.
     */
    public function getSystemHealth(): array
    {
        return [
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'services' => [
                'database' => $this->checkDatabaseHealth(),
                'cache' => $this->checkCacheHealth(),
                'storage' => $this->checkStorageHealth(),
            ],
            'metrics' => [
                'total_request_logs' => RequestLog::count(),
                'total_audit_logs' => AuditLog::count(),
                'failed_requests_24h' => $this->getFailedRequestsCount(24),
            ],
        ];
    }

    /**
     * Get request statistics.
     */
    public function getRequestStatistics(int $accountId, int $hours = 24): array
    {
        $since = now()->subHours($hours);

        $total = RequestLog::where('account_id', $accountId)
            ->where('created_date_time', '>=', $since)
            ->count();

        $successful = RequestLog::where('account_id', $accountId)
            ->where('created_date_time', '>=', $since)
            ->successful()
            ->count();

        $failed = RequestLog::where('account_id', $accountId)
            ->where('created_date_time', '>=', $since)
            ->failed()
            ->count();

        $avgDuration = RequestLog::where('account_id', $accountId)
            ->where('created_date_time', '>=', $since)
            ->whereNotNull('duration_ms')
            ->avg('duration_ms');

        // Get top endpoints
        $topEndpoints = RequestLog::where('account_id', $accountId)
            ->where('created_date_time', '>=', $since)
            ->select('request_url', DB::raw('count(*) as count'))
            ->groupBy('request_url')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return [
            'period_hours' => $hours,
            'total_requests' => $total,
            'successful_requests' => $successful,
            'failed_requests' => $failed,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
            'average_duration_ms' => round($avgDuration ?? 0, 2),
            'top_endpoints' => $topEndpoints,
        ];
    }

    // =========================================================================
    // PRIVATE HELPER METHODS
    // =========================================================================

    /**
     * Check database health.
     */
    private function checkDatabaseHealth(): array
    {
        try {
            DB::connection()->getPdo();
            return [
                'status' => 'healthy',
                'message' => 'Database connection successful',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache health.
     */
    private function checkCacheHealth(): array
    {
        try {
            \Cache::put('health_check', true, 10);
            $value = \Cache::get('health_check');
            \Cache::forget('health_check');

            return [
                'status' => $value ? 'healthy' : 'unhealthy',
                'message' => $value ? 'Cache working' : 'Cache test failed',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Cache error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage health.
     */
    private function checkStorageHealth(): array
    {
        try {
            $path = storage_path();
            $writable = is_writable($path);

            return [
                'status' => $writable ? 'healthy' : 'unhealthy',
                'message' => $writable ? 'Storage writable' : 'Storage not writable',
                'path' => $path,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Storage error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get count of failed requests in the last N hours.
     */
    private function getFailedRequestsCount(int $hours): int
    {
        $since = now()->subHours($hours);

        return RequestLog::where('created_date_time', '>=', $since)
            ->failed()
            ->count();
    }
}
