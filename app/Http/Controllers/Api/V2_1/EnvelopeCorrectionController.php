<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Models\Account;
use App\Models\Envelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * EnvelopeCorrectionController
 *
 * Manages envelope corrections after sending.
 * Allows senders to correct recipient information, tabs, and documents.
 *
 * Total Endpoints: 2
 */
class EnvelopeCorrectionController extends BaseController
{
    /**
     * POST /accounts/{accountId}/envelopes/{envelopeId}/correct
     *
     * Create a correction for an envelope (enters correction mode)
     */
    public function correct(Request $request, string $accountId, string $envelopeId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'recipient_corrections' => 'sometimes|array',
                'recipient_corrections.*.recipient_id' => 'required|string',
                'recipient_corrections.*.name' => 'sometimes|string|max:255',
                'recipient_corrections.*.email' => 'sometimes|email|max:255',
                'tab_corrections' => 'sometimes|array',
                'tab_corrections.*.tab_id' => 'required|string',
                'tab_corrections.*.value' => 'sometimes|string',
                'tab_corrections.*.required' => 'sometimes|boolean',
                'document_corrections' => 'sometimes|array',
                'document_corrections.*.document_id' => 'required|string',
                'document_corrections.*.name' => 'sometimes|string|max:255',
                'suppress_emails' => 'sometimes|boolean',
            ]);

            $account = Account::where('account_id', $accountId)->firstOrFail();

            $envelope = Envelope::where('account_id', $account->id)
                ->where('envelope_id', $envelopeId)
                ->firstOrFail();

            // Validate envelope can be corrected
            if ($envelope->status === 'completed') {
                return $this->errorResponse(
                    'Cannot correct a completed envelope',
                    400
                );
            }

            if ($envelope->status === 'voided') {
                return $this->errorResponse(
                    'Cannot correct a voided envelope',
                    400
                );
            }

            if ($envelope->status === 'draft') {
                return $this->errorResponse(
                    'Draft envelopes should be updated directly, not corrected',
                    400
                );
            }

            DB::beginTransaction();

            try {
                // Apply recipient corrections
                if (isset($validated['recipient_corrections'])) {
                    foreach ($validated['recipient_corrections'] as $correction) {
                        $recipient = $envelope->recipients()
                            ->where('recipient_id', $correction['recipient_id'])
                            ->firstOrFail();

                        $recipient->update(array_filter([
                            'name' => $correction['name'] ?? null,
                            'email' => $correction['email'] ?? null,
                        ]));
                    }
                }

                // Apply tab corrections
                if (isset($validated['tab_corrections'])) {
                    foreach ($validated['tab_corrections'] as $correction) {
                        $tab = $envelope->tabs()
                            ->where('tab_id', $correction['tab_id'])
                            ->firstOrFail();

                        $tab->update(array_filter([
                            'value' => $correction['value'] ?? null,
                            'required' => $correction['required'] ?? null,
                        ]));
                    }
                }

                // Apply document corrections
                if (isset($validated['document_corrections'])) {
                    foreach ($validated['document_corrections'] as $correction) {
                        $document = $envelope->documents()
                            ->where('document_id', $correction['document_id'])
                            ->firstOrFail();

                        $document->update(array_filter([
                            'name' => $correction['name'] ?? null,
                        ]));
                    }
                }

                // Log audit event
                $envelope->auditEvents()->create([
                    'event_type' => 'envelope_corrected',
                    'user_id' => auth()->id(),
                    'timestamp' => now(),
                    'metadata' => [
                        'recipient_corrections_count' => count($validated['recipient_corrections'] ?? []),
                        'tab_corrections_count' => count($validated['tab_corrections'] ?? []),
                        'document_corrections_count' => count($validated['document_corrections'] ?? []),
                        'suppress_emails' => $validated['suppress_emails'] ?? false,
                    ],
                ]);

                DB::commit();

                $envelope->load(['recipients', 'tabs', 'documents']);

                return $this->successResponse([
                    'envelope_id' => $envelope->envelope_id,
                    'status' => $envelope->status,
                    'corrected_at' => now()->toIso8601String(),
                    'corrections_applied' => [
                        'recipients' => count($validated['recipient_corrections'] ?? []),
                        'tabs' => count($validated['tab_corrections'] ?? []),
                        'documents' => count($validated['document_corrections'] ?? []),
                    ],
                ], 'Envelope corrections applied successfully');
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /accounts/{accountId}/envelopes/{envelopeId}/resend
     *
     * Resend envelope notifications to recipients
     */
    public function resend(Request $request, string $accountId, string $envelopeId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'resend_envelope' => 'sometimes|boolean',
            ]);

            $account = Account::where('account_id', $accountId)->firstOrFail();

            $envelope = Envelope::where('account_id', $account->id)
                ->where('envelope_id', $envelopeId)
                ->with('recipients')
                ->firstOrFail();

            // Validate envelope can be resent
            if (!in_array($envelope->status, ['sent', 'delivered'])) {
                return $this->errorResponse(
                    'Can only resend notifications for sent or delivered envelopes',
                    400
                );
            }

            DB::beginTransaction();

            try {
                // In production, this would trigger actual email notifications
                // For now, we'll just log the resend event

                $envelope->auditEvents()->create([
                    'event_type' => 'envelope_resent',
                    'user_id' => auth()->id(),
                    'timestamp' => now(),
                    'metadata' => [
                        'recipient_count' => $envelope->recipients->count(),
                        'resend_envelope' => $validated['resend_envelope'] ?? true,
                    ],
                ]);

                // Update sent_date_time for recipients
                $envelope->recipients()->whereNull('signed_date_time')->update([
                    'sent_date_time' => now(),
                ]);

                DB::commit();

                return $this->successResponse([
                    'envelope_id' => $envelope->envelope_id,
                    'status' => $envelope->status,
                    'resent_at' => now()->toIso8601String(),
                    'recipient_count' => $envelope->recipients->count(),
                    'pending_recipients' => $envelope->recipients->whereNull('signed_date_time')->count(),
                ], 'Envelope notifications resent successfully');
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
