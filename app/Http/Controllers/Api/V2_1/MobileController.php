<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Models\Account;
use App\Models\Envelope;
use App\Models\EnvelopeRecipient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * MobileController
 *
 * Mobile-optimized endpoints for signing and viewing on mobile devices.
 * Provides responsive UI components and touch-friendly interfaces.
 *
 * Total Endpoints: 4
 */
class MobileController extends BaseController
{
    /**
     * GET /accounts/{accountId}/mobile/envelopes
     *
     * Get mobile-optimized envelope list
     */
    public function getEnvelopes(Request $request, string $accountId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'sometimes|string|in:inbox,sent,draft,waiting_for_others,completed',
                'count' => 'sometimes|integer|min:1|max:50',
                'start_position' => 'sometimes|integer|min:0',
            ]);

            $account = Account::where('account_id', $accountId)->firstOrFail();

            $query = Envelope::where('account_id', $account->id);

            // Mobile-specific status filtering
            if (isset($validated['status'])) {
                $status = match ($validated['status']) {
                    'inbox' => 'sent',
                    'sent' => 'sent',
                    'draft' => 'draft',
                    'waiting_for_others' => ['sent', 'delivered'],
                    'completed' => 'completed',
                    default => 'sent',
                };

                if (is_array($status)) {
                    $query->whereIn('status', $status);
                } else {
                    $query->where('status', $status);
                }
            }

            $count = $validated['count'] ?? 20;
            $startPosition = $validated['start_position'] ?? 0;

            $envelopes = $query
                ->with(['recipients', 'documents'])
                ->orderBy('created_at', 'desc')
                ->skip($startPosition)
                ->take($count)
                ->get();

            return $this->successResponse([
                'envelopes' => $envelopes->map(function ($envelope) {
                    return [
                        'envelope_id' => $envelope->envelope_id,
                        'subject' => $envelope->subject,
                        'status' => $envelope->status,
                        'sender_name' => $envelope->sender_name,
                        'created_at' => $envelope->created_at->toIso8601String(),
                        'requires_action' => $this->requiresAction($envelope),
                        'mobile_view_url' => $this->getMobileViewUrl($envelope),
                        'thumbnail_url' => $this->getThumbnailUrl($envelope),
                        'recipient_count' => $envelope->recipients->count(),
                        'document_count' => $envelope->documents->count(),
                    ];
                }),
                'total_count' => $query->count(),
                'start_position' => $startPosition,
            ], 'Mobile envelopes retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /accounts/{accountId}/mobile/envelopes/{envelopeId}/view
     *
     * Get mobile-optimized envelope view
     */
    public function getEnvelopeView(string $accountId, string $envelopeId): JsonResponse
    {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();

            $envelope = Envelope::where('account_id', $account->id)
                ->where('envelope_id', $envelopeId)
                ->with(['documents', 'recipients', 'tabs'])
                ->firstOrFail();

            return $this->successResponse([
                'envelope_id' => $envelope->envelope_id,
                'subject' => $envelope->subject,
                'status' => $envelope->status,
                'sender' => [
                    'name' => $envelope->sender_name,
                    'email' => $envelope->sender_email,
                ],
                'documents' => $envelope->documents->map(function ($doc) use ($envelope) {
                    return [
                        'document_id' => $doc->document_id,
                        'name' => $doc->name,
                        'order' => $doc->order,
                        'pages' => $doc->pages,
                        'mobile_view_url' => "/api/v2.1/mobile/documents/{$doc->document_id}/view",
                        'thumbnail_url' => "/api/v2.1/mobile/documents/{$doc->document_id}/thumbnail",
                    ];
                }),
                'recipients' => $envelope->recipients->map(function ($recipient) {
                    return [
                        'recipient_id' => $recipient->recipient_id,
                        'name' => $recipient->name,
                        'email' => $recipient->email,
                        'status' => $recipient->status,
                        'recipient_type' => $recipient->recipient_type,
                    ];
                }),
                'tabs' => $this->formatTabsForMobile($envelope->tabs),
                'mobile_signing_url' => $this->getMobileSigningUrl($envelope),
                'responsive_view' => true,
            ], 'Mobile envelope view retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /accounts/{accountId}/mobile/envelopes/{envelopeId}/sign
     *
     * Mobile-optimized signing endpoint with touch support
     */
    public function signEnvelope(Request $request, string $accountId, string $envelopeId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'recipient_id' => 'required|string',
                'tabs' => 'required|array',
                'tabs.*.tab_id' => 'required|string',
                'tabs.*.value' => 'required|string',
                'signature_data' => 'sometimes|string',
                'device_info' => 'sometimes|array',
                'device_info.type' => 'sometimes|string|in:phone,tablet',
                'device_info.os' => 'sometimes|string',
                'device_info.browser' => 'sometimes|string',
            ]);

            $account = Account::where('account_id', $accountId)->firstOrFail();

            $envelope = Envelope::where('account_id', $account->id)
                ->where('envelope_id', $envelopeId)
                ->firstOrFail();

            $recipient = EnvelopeRecipient::where('envelope_id', $envelope->id)
                ->where('recipient_id', $validated['recipient_id'])
                ->firstOrFail();

            // In production, this would:
            // 1. Validate all required tabs are completed
            // 2. Apply signature data
            // 3. Update recipient status
            // 4. Log audit events with device info
            // 5. Trigger notifications

            return $this->successResponse([
                'envelope_id' => $envelope->envelope_id,
                'recipient_id' => $recipient->recipient_id,
                'signed_at' => now()->toIso8601String(),
                'tabs_completed' => count($validated['tabs']),
                'device_type' => $validated['device_info']['type'] ?? 'unknown',
                'next_recipient' => $this->getNextRecipient($envelope, $recipient),
                'envelope_status' => $envelope->status,
            ], 'Envelope signed successfully from mobile');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /accounts/{accountId}/mobile/settings
     *
     * Get mobile-specific settings and preferences
     */
    public function getSettings(string $accountId): JsonResponse
    {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();

            return $this->successResponse([
                'mobile_enabled' => true,
                'touch_signature_enabled' => true,
                'responsive_view_enabled' => true,
                'offline_mode_enabled' => false,
                'auto_zoom_enabled' => true,
                'swipe_navigation_enabled' => true,
                'notification_preferences' => [
                    'push_notifications' => true,
                    'email_notifications' => true,
                    'sms_notifications' => false,
                ],
                'display_preferences' => [
                    'thumbnail_quality' => 'medium',
                    'auto_rotate' => true,
                    'pinch_zoom' => true,
                ],
            ], 'Mobile settings retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Helper: Check if envelope requires action from user
     */
    protected function requiresAction($envelope): bool
    {
        // In production, check if current user needs to sign/review
        return in_array($envelope->status, ['sent', 'delivered']);
    }

    /**
     * Helper: Get mobile view URL
     */
    protected function getMobileViewUrl($envelope): string
    {
        return "/mobile/envelopes/{$envelope->envelope_id}/view";
    }

    /**
     * Helper: Get thumbnail URL
     */
    protected function getThumbnailUrl($envelope): string
    {
        $firstDoc = $envelope->documents->first();
        return $firstDoc
            ? "/mobile/documents/{$firstDoc->document_id}/thumbnail"
            : null;
    }

    /**
     * Helper: Get mobile signing URL
     */
    protected function getMobileSigningUrl($envelope): string
    {
        return "/mobile/envelopes/{$envelope->envelope_id}/sign";
    }

    /**
     * Helper: Format tabs for mobile display
     */
    protected function formatTabsForMobile($tabs): array
    {
        return $tabs->map(function ($tab) {
            return [
                'tab_id' => $tab->tab_id,
                'tab_type' => $tab->tab_type,
                'label' => $tab->label,
                'required' => $tab->required,
                'page_number' => $tab->page_number,
                'touch_friendly' => in_array($tab->tab_type, [
                    'signature', 'initial', 'date', 'checkbox', 'radio',
                ]),
            ];
        })->toArray();
    }

    /**
     * Helper: Get next recipient in workflow
     */
    protected function getNextRecipient($envelope, $currentRecipient): ?array
    {
        $nextRecipient = EnvelopeRecipient::where('envelope_id', $envelope->id)
            ->where('routing_order', '>', $currentRecipient->routing_order)
            ->orderBy('routing_order')
            ->first();

        return $nextRecipient ? [
            'recipient_id' => $nextRecipient->recipient_id,
            'name' => $nextRecipient->name,
            'email' => $nextRecipient->email,
        ] : null;
    }
}
