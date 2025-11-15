<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Models\Account;
use App\Models\Envelope;
use Illuminate\Http\JsonResponse;

/**
 * EnvelopeSummaryController
 *
 * Provides envelope summary information and metadata.
 * Returns condensed envelope information for reporting and dashboards.
 *
 * Total Endpoints: 2
 */
class EnvelopeSummaryController extends BaseController
{
    /**
     * GET /accounts/{accountId}/envelopes/{envelopeId}/summary
     *
     * Get envelope summary with key metadata
     */
    public function getSummary(string $accountId, string $envelopeId): JsonResponse
    {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();

            $envelope = Envelope::where('account_id', $account->id)
                ->where('envelope_id', $envelopeId)
                ->with(['recipients', 'documents', 'customFields', 'auditEvents'])
                ->firstOrFail();

            // Calculate summary statistics
            $recipientStats = [
                'total' => $envelope->recipients->count(),
                'signed' => $envelope->recipients->whereNotNull('signed_date_time')->count(),
                'delivered' => $envelope->recipients->whereNotNull('delivered_date_time')->count(),
                'pending' => $envelope->recipients->whereNull('signed_date_time')->count(),
                'declined' => $envelope->recipients->whereNotNull('declined_date_time')->count(),
            ];

            $documentStats = [
                'total' => $envelope->documents->count(),
                'total_pages' => $envelope->documents->sum('pages') ?? 0,
                'total_size_bytes' => $envelope->documents->sum('file_size') ?? 0,
            ];

            // Get key dates
            $keyDates = [
                'created_at' => $envelope->created_at->toIso8601String(),
                'sent_at' => $envelope->sent_date_time?->toIso8601String(),
                'delivered_at' => $envelope->delivered_date_time?->toIso8601String(),
                'completed_at' => $envelope->completed_date_time?->toIso8601String(),
                'voided_at' => $envelope->voided_date_time?->toIso8601String(),
                'expires_at' => $envelope->expires_date_time?->toIso8601String(),
                'last_modified_at' => $envelope->updated_at->toIso8601String(),
            ];

            // Get audit event summary
            $auditEventCount = $envelope->auditEvents->count();
            $latestAuditEvent = $envelope->auditEvents->sortByDesc('created_at')->first();

            return $this->successResponse([
                'envelope_id' => $envelope->envelope_id,
                'status' => $envelope->status,
                'subject' => $envelope->subject,
                'email_subject' => $envelope->email_subject,
                'sender' => [
                    'user_id' => $envelope->sender_user_id,
                    'email' => $envelope->sender_email,
                    'name' => $envelope->sender_name,
                ],
                'recipients' => $recipientStats,
                'documents' => $documentStats,
                'custom_fields_count' => $envelope->customFields->count(),
                'audit_events_count' => $auditEventCount,
                'latest_audit_event' => $latestAuditEvent ? [
                    'event_type' => $latestAuditEvent->event_type,
                    'timestamp' => $latestAuditEvent->created_at->toIso8601String(),
                ] : null,
                'dates' => $keyDates,
                'notification_settings' => [
                    'reminder_enabled' => $envelope->reminder_enabled,
                    'reminder_delay' => $envelope->reminder_delay,
                    'expiration_enabled' => $envelope->expiration_enabled,
                    'expiration_after' => $envelope->expiration_after,
                ],
                'is_expired' => $envelope->hasExpired(),
                'can_be_modified' => $envelope->canBeModified(),
                'can_be_voided' => $envelope->canBeVoided(),
            ], 'Envelope summary retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /accounts/{accountId}/envelopes/{envelopeId}/status_changes
     *
     * Get envelope status change history from audit events
     */
    public function getStatusChanges(string $accountId, string $envelopeId): JsonResponse
    {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();

            $envelope = Envelope::where('account_id', $account->id)
                ->where('envelope_id', $envelopeId)
                ->with('auditEvents')
                ->firstOrFail();

            // Filter status change events
            $statusEvents = $envelope->auditEvents()
                ->whereIn('event_type', [
                    'envelope_created',
                    'envelope_sent',
                    'envelope_delivered',
                    'envelope_completed',
                    'envelope_declined',
                    'envelope_voided',
                    'envelope_corrected',
                    'envelope_resent',
                ])
                ->orderBy('created_at', 'asc')
                ->get();

            $statusChanges = $statusEvents->map(function ($event) {
                return [
                    'event_type' => $event->event_type,
                    'status' => $this->mapEventToStatus($event->event_type),
                    'timestamp' => $event->created_at->toIso8601String(),
                    'user_id' => $event->user_id,
                    'metadata' => $event->metadata,
                ];
            });

            return $this->successResponse([
                'envelope_id' => $envelope->envelope_id,
                'current_status' => $envelope->status,
                'status_changes' => $statusChanges,
                'total_changes' => $statusChanges->count(),
            ], 'Envelope status changes retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Helper: Map audit event type to envelope status
     */
    protected function mapEventToStatus(string $eventType): string
    {
        return match ($eventType) {
            'envelope_created' => 'created',
            'envelope_sent' => 'sent',
            'envelope_delivered' => 'delivered',
            'envelope_completed' => 'completed',
            'envelope_declined' => 'declined',
            'envelope_voided' => 'voided',
            'envelope_corrected' => 'corrected',
            'envelope_resent' => 'resent',
            default => 'unknown',
        };
    }
}
