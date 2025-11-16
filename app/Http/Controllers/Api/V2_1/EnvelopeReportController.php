<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Models\Account;
use App\Models\Envelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * EnvelopeReportController
 *
 * Envelope reporting, export, and analytics endpoints.
 * Supports CSV exports, usage reports, and envelope analytics.
 *
 * Total Endpoints: 4
 */
class EnvelopeReportController extends BaseController
{
    /**
     * POST /accounts/{accountId}/envelopes/export
     *
     * Export envelopes to CSV format
     */
    public function export(Request $request, string $accountId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'sometimes|array',
                'from_date' => 'sometimes|date',
                'to_date' => 'sometimes|date',
                'folder_ids' => 'sometimes|array',
                'include_fields' => 'sometimes|array',
            ]);

            $account = Account::where('account_id', $accountId)->firstOrFail();

            $query = Envelope::where('account_id', $account->id)
                ->with(['recipients', 'documents']);

            // Apply filters
            if (isset($validated['status'])) {
                $query->whereIn('status', $validated['status']);
            }

            if (isset($validated['from_date'])) {
                $query->where('created_at', '>=', $validated['from_date']);
            }

            if (isset($validated['to_date'])) {
                $query->where('created_at', '<=', $validated['to_date']);
            }

            if (isset($validated['folder_ids'])) {
                $query->whereIn('folder_id', $validated['folder_ids']);
            }

            $envelopes = $query->get();

            // Generate CSV content
            $fields = $validated['include_fields'] ?? [
                'envelope_id',
                'status',
                'subject',
                'sender_email',
                'created_at',
                'sent_at',
                'completed_at',
                'recipients_count',
                'documents_count',
            ];

            $csv = [];
            $csv[] = implode(',', array_map(function ($field) {
                return ucwords(str_replace('_', ' ', $field));
            }, $fields));

            foreach ($envelopes as $envelope) {
                $row = [];
                foreach ($fields as $field) {
                    $value = match ($field) {
                        'envelope_id' => $envelope->envelope_id,
                        'status' => $envelope->status,
                        'subject' => $envelope->subject,
                        'sender_email' => $envelope->sender_email,
                        'sender_name' => $envelope->sender_name,
                        'created_at' => $envelope->created_at->toIso8601String(),
                        'sent_at' => $envelope->sent_date_time?->toIso8601String() ?? '',
                        'delivered_at' => $envelope->delivered_date_time?->toIso8601String() ?? '',
                        'completed_at' => $envelope->completed_date_time?->toIso8601String() ?? '',
                        'recipients_count' => $envelope->recipients->count(),
                        'documents_count' => $envelope->documents->count(),
                        default => '',
                    };
                    $row[] = '"' . str_replace('"', '""', $value) . '"';
                }
                $csv[] = implode(',', $row);
            }

            $csvContent = implode("\n", $csv);

            // In production, this would be saved to storage and a download URL provided
            // For now, we'll return the CSV content encoded
            $filename = 'envelopes_export_' . now()->format('Y-m-d_His') . '.csv';

            return $this->successResponse([
                'export_id' => uniqid('exp_'),
                'filename' => $filename,
                'format' => 'csv',
                'record_count' => $envelopes->count(),
                'csv_content' => base64_encode($csvContent),
                'download_url' => '/api/v2.1/accounts/' . $accountId . '/downloads/' . $filename,
                'expires_at' => now()->addHours(24)->toIso8601String(),
            ], 'Envelope export generated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /accounts/{accountId}/envelopes/reports/usage
     *
     * Get envelope usage report for date range
     */
    public function usageReport(Request $request, string $accountId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'from_date' => 'required|date',
                'to_date' => 'required|date',
                'group_by' => 'sometimes|string|in:day,week,month',
            ]);

            $account = Account::where('account_id', $accountId)->firstOrFail();

            $groupBy = $validated['group_by'] ?? 'day';

            // Get envelope counts grouped by date
            $dateFormat = match ($groupBy) {
                'day' => '%Y-%m-%d',
                'week' => '%Y-W%V',
                'month' => '%Y-%m',
                default => '%Y-%m-%d',
            };

            $usage = DB::table('envelopes')
                ->where('account_id', $account->id)
                ->whereBetween('created_at', [$validated['from_date'], $validated['to_date']])
                ->select(
                    DB::raw("DATE_FORMAT(created_at, '{$dateFormat}') as period"),
                    DB::raw('COUNT(*) as total'),
                    DB::raw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed"),
                    DB::raw("SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent"),
                    DB::raw("SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered"),
                    DB::raw("SUM(CASE WHEN status = 'voided' THEN 1 ELSE 0 END) as voided"),
                    DB::raw("SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as declined")
                )
                ->groupBy('period')
                ->orderBy('period')
                ->get();

            $totalEnvelopes = DB::table('envelopes')
                ->where('account_id', $account->id)
                ->whereBetween('created_at', [$validated['from_date'], $validated['to_date']])
                ->count();

            return $this->successResponse([
                'from_date' => $validated['from_date'],
                'to_date' => $validated['to_date'],
                'group_by' => $groupBy,
                'total_envelopes' => $totalEnvelopes,
                'usage_by_period' => $usage,
            ], 'Usage report generated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /accounts/{accountId}/envelopes/reports/recipients
     *
     * Get recipient analytics report
     */
    public function recipientReport(Request $request, string $accountId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'from_date' => 'sometimes|date',
                'to_date' => 'sometimes|date',
                'top_n' => 'sometimes|integer|min:1|max:100',
            ]);

            $account = Account::where('account_id', $accountId)->firstOrFail();

            $query = DB::table('envelope_recipients')
                ->join('envelopes', 'envelope_recipients.envelope_id', '=', 'envelopes.id')
                ->where('envelopes.account_id', $account->id);

            if (isset($validated['from_date'])) {
                $query->where('envelopes.created_at', '>=', $validated['from_date']);
            }

            if (isset($validated['to_date'])) {
                $query->where('envelopes.created_at', '<=', $validated['to_date']);
            }

            $topN = $validated['top_n'] ?? 10;

            // Get top recipients by envelope count
            $topRecipients = (clone $query)
                ->select(
                    'envelope_recipients.email',
                    'envelope_recipients.name',
                    DB::raw('COUNT(DISTINCT envelope_recipients.envelope_id) as envelope_count'),
                    DB::raw("SUM(CASE WHEN envelope_recipients.status = 'completed' THEN 1 ELSE 0 END) as completed_count"),
                    DB::raw('AVG(TIMESTAMPDIFF(HOUR, envelope_recipients.sent_date_time, envelope_recipients.signed_date_time)) as avg_signing_time_hours')
                )
                ->groupBy('envelope_recipients.email', 'envelope_recipients.name')
                ->orderByDesc('envelope_count')
                ->limit($topN)
                ->get();

            // Get recipient type distribution
            $recipientTypeDistribution = (clone $query)
                ->select(
                    'envelope_recipients.recipient_type',
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('envelope_recipients.recipient_type')
                ->get();

            // Get status distribution
            $statusDistribution = (clone $query)
                ->select(
                    'envelope_recipients.status',
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('envelope_recipients.status')
                ->get();

            return $this->successResponse([
                'top_recipients' => $topRecipients,
                'recipient_type_distribution' => $recipientTypeDistribution,
                'status_distribution' => $statusDistribution,
                'total_unique_recipients' => DB::table('envelope_recipients')
                    ->join('envelopes', 'envelope_recipients.envelope_id', '=', 'envelopes.id')
                    ->where('envelopes.account_id', $account->id)
                    ->distinct('envelope_recipients.email')
                    ->count('envelope_recipients.email'),
            ], 'Recipient report generated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /accounts/{accountId}/envelopes/reports/completion_rate
     *
     * Get envelope completion rate analytics
     */
    public function completionRateReport(Request $request, string $accountId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'from_date' => 'sometimes|date',
                'to_date' => 'sometimes|date',
            ]);

            $account = Account::where('account_id', $accountId)->firstOrFail();

            $query = DB::table('envelopes')
                ->where('account_id', $account->id);

            if (isset($validated['from_date'])) {
                $query->where('created_at', '>=', $validated['from_date']);
            }

            if (isset($validated['to_date'])) {
                $query->where('created_at', '<=', $validated['to_date']);
            }

            $stats = $query->select(
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent"),
                DB::raw("SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered"),
                DB::raw("SUM(CASE WHEN status = 'voided' THEN 1 ELSE 0 END) as voided"),
                DB::raw("SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as declined"),
                DB::raw("SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft"),
                DB::raw('AVG(TIMESTAMPDIFF(HOUR, sent_date_time, completed_date_time)) as avg_completion_time_hours')
            )->first();

            $completionRate = $stats->total > 0
                ? round(($stats->completed / $stats->total) * 100, 2)
                : 0;

            $voidedRate = $stats->total > 0
                ? round(($stats->voided / $stats->total) * 100, 2)
                : 0;

            $declinedRate = $stats->total > 0
                ? round(($stats->declined / $stats->total) * 100, 2)
                : 0;

            return $this->successResponse([
                'total_envelopes' => $stats->total,
                'completed' => $stats->completed,
                'sent' => $stats->sent,
                'delivered' => $stats->delivered,
                'voided' => $stats->voided,
                'declined' => $stats->declined,
                'draft' => $stats->draft,
                'completion_rate_percent' => $completionRate,
                'voided_rate_percent' => $voidedRate,
                'declined_rate_percent' => $declinedRate,
                'avg_completion_time_hours' => round($stats->avg_completion_time_hours ?? 0, 2),
            ], 'Completion rate report generated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
