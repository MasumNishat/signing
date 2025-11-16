<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Models\Account;
use App\Models\Envelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * EnvelopeSearchController
 *
 * Advanced search and filtering for envelopes.
 * Supports complex queries, saved searches, and custom field filtering.
 *
 * Total Endpoints: 3
 */
class EnvelopeSearchController extends BaseController
{
    /**
     * POST /accounts/{accountId}/envelopes/search
     *
     * Advanced envelope search with complex filters
     */
    public function search(Request $request, string $accountId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'envelope_ids' => 'sometimes|array',
                'transaction_ids' => 'sometimes|array',
                'status' => 'sometimes|array',
                'from_date' => 'sometimes|date',
                'to_date' => 'sometimes|date',
                'from_to_status' => 'sometimes|string|in:changed,created,sent,delivered,signed,completed,declined,voided,deleted',
                'sender_email' => 'sometimes|email',
                'sender_name' => 'sometimes|string',
                'recipient_email' => 'sometimes|email',
                'recipient_name' => 'sometimes|string',
                'subject' => 'sometimes|string',
                'folder_ids' => 'sometimes|array',
                'custom_field' => 'sometimes|array',
                'custom_field.*.name' => 'required_with:custom_field|string',
                'custom_field.*.value' => 'required_with:custom_field|string',
                'include' => 'sometimes|string',
                'count' => 'sometimes|integer|min:1|max:100',
                'start_position' => 'sometimes|integer|min:0',
                'order' => 'sometimes|string|in:asc,desc',
                'order_by' => 'sometimes|string|in:created,last_modified,sent,signed,completed,subject,status,sender',
            ]);

            $account = Account::where('account_id', $accountId)->firstOrFail();

            $query = Envelope::where('account_id', $account->id);

            // Filter by envelope IDs
            if (isset($validated['envelope_ids'])) {
                $query->whereIn('envelope_id', $validated['envelope_ids']);
            }

            // Filter by status
            if (isset($validated['status'])) {
                $query->whereIn('status', $validated['status']);
            }

            // Date range filtering
            if (isset($validated['from_date']) || isset($validated['to_date'])) {
                $dateField = match ($validated['from_to_status'] ?? 'created') {
                    'created' => 'created_at',
                    'sent' => 'sent_date_time',
                    'delivered' => 'delivered_date_time',
                    'signed' => 'last_signed_date_time',
                    'completed' => 'completed_date_time',
                    'declined' => 'declined_date_time',
                    'voided' => 'voided_date_time',
                    default => 'created_at',
                };

                if (isset($validated['from_date'])) {
                    $query->where($dateField, '>=', $validated['from_date']);
                }

                if (isset($validated['to_date'])) {
                    $query->where($dateField, '<=', $validated['to_date']);
                }
            }

            // Filter by sender
            if (isset($validated['sender_email'])) {
                $query->where('sender_email', 'like', "%{$validated['sender_email']}%");
            }

            if (isset($validated['sender_name'])) {
                $query->where('sender_name', 'like', "%{$validated['sender_name']}%");
            }

            // Filter by subject
            if (isset($validated['subject'])) {
                $query->where(function ($q) use ($validated) {
                    $q->where('subject', 'like', "%{$validated['subject']}%")
                      ->orWhere('email_subject', 'like', "%{$validated['subject']}%");
                });
            }

            // Filter by recipient
            if (isset($validated['recipient_email']) || isset($validated['recipient_name'])) {
                $query->whereHas('recipients', function ($q) use ($validated) {
                    if (isset($validated['recipient_email'])) {
                        $q->where('email', 'like', "%{$validated['recipient_email']}%");
                    }
                    if (isset($validated['recipient_name'])) {
                        $q->where('name', 'like', "%{$validated['recipient_name']}%");
                    }
                });
            }

            // Filter by folder
            if (isset($validated['folder_ids'])) {
                $query->whereIn('folder_id', $validated['folder_ids']);
            }

            // Filter by custom fields
            if (isset($validated['custom_field'])) {
                foreach ($validated['custom_field'] as $customField) {
                    $query->whereHas('customFields', function ($q) use ($customField) {
                        $q->where('field_name', $customField['name'])
                          ->where('field_value', 'like', "%{$customField['value']}%");
                    });
                }
            }

            // Ordering
            $orderBy = $validated['order_by'] ?? 'created';
            $order = $validated['order'] ?? 'desc';

            $orderField = match ($orderBy) {
                'created' => 'created_at',
                'last_modified' => 'updated_at',
                'sent' => 'sent_date_time',
                'signed' => 'last_signed_date_time',
                'completed' => 'completed_date_time',
                'subject' => 'subject',
                'status' => 'status',
                'sender' => 'sender_email',
                default => 'created_at',
            };

            $query->orderBy($orderField, $order);

            // Pagination
            $count = $validated['count'] ?? 20;
            $startPosition = $validated['start_position'] ?? 0;

            $totalRecords = $query->count();

            // Load relationships based on 'include' parameter
            $includes = isset($validated['include'])
                ? array_map('trim', explode(',', $validated['include']))
                : ['recipients', 'documents'];

            $envelopes = $query
                ->with($includes)
                ->skip($startPosition)
                ->take($count)
                ->get();

            return $this->successResponse([
                'envelopes' => $envelopes->map(function ($envelope) {
                    return [
                        'envelope_id' => $envelope->envelope_id,
                        'status' => $envelope->status,
                        'subject' => $envelope->subject,
                        'sender_email' => $envelope->sender_email,
                        'sender_name' => $envelope->sender_name,
                        'created_at' => $envelope->created_at->toIso8601String(),
                        'sent_at' => $envelope->sent_date_time?->toIso8601String(),
                        'delivered_at' => $envelope->delivered_date_time?->toIso8601String(),
                        'completed_at' => $envelope->completed_date_time?->toIso8601String(),
                        'recipients_count' => $envelope->recipients->count(),
                        'documents_count' => $envelope->documents->count(),
                    ];
                }),
                'result_set_size' => $envelopes->count(),
                'start_position' => $startPosition,
                'total_set_size' => $totalRecords,
                'end_position' => min($startPosition + $envelopes->count(), $totalRecords),
            ], 'Envelope search completed successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /accounts/{accountId}/envelopes/search_folders
     *
     * Get folder information for search context
     */
    public function searchFolders(string $accountId): JsonResponse
    {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();

            // Get all folders with envelope counts
            $folders = DB::table('folders')
                ->where('account_id', $account->id)
                ->select('id', 'folder_id', 'name', 'type', 'parent_folder_id')
                ->get()
                ->map(function ($folder) {
                    $envelopeCount = DB::table('envelopes')
                        ->where('folder_id', $folder->id)
                        ->whereNull('deleted_at')
                        ->count();

                    return [
                        'folder_id' => $folder->folder_id,
                        'name' => $folder->name,
                        'type' => $folder->type,
                        'parent_folder_id' => $folder->parent_folder_id,
                        'envelope_count' => $envelopeCount,
                    ];
                });

            return $this->successResponse([
                'folders' => $folders,
                'total_folders' => $folders->count(),
            ], 'Search folders retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /accounts/{accountId}/envelopes/search_status
     *
     * Get available envelope statuses for search filters
     */
    public function searchStatus(string $accountId): JsonResponse
    {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();

            // Get distinct statuses with counts
            $statuses = DB::table('envelopes')
                ->where('account_id', $account->id)
                ->whereNull('deleted_at')
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->map(function ($item) {
                    return [
                        'status' => $item->status,
                        'count' => $item->count,
                    ];
                });

            return $this->successResponse([
                'statuses' => $statuses,
                'available_statuses' => [
                    'draft',
                    'sent',
                    'delivered',
                    'signed',
                    'completed',
                    'declined',
                    'voided',
                    'deleted',
                ],
            ], 'Search statuses retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
