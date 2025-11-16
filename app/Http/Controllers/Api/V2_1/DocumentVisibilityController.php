<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Models\Account;
use App\Models\Envelope;
use App\Models\Template;
use App\Models\EnvelopeDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * DocumentVisibilityController
 *
 * Manages document visibility settings for recipients.
 * Controls which recipients can see which documents in an envelope.
 *
 * Total Endpoints: 4
 */
class DocumentVisibilityController extends BaseController
{
    /**
     * GET /accounts/{accountId}/envelopes/{envelopeId}/document_visibility
     *
     * Get document visibility settings for all documents
     */
    public function index(string $accountId, string $envelopeId): JsonResponse
    {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();

            $envelope = Envelope::where('account_id', $account->id)
                ->where('envelope_id', $envelopeId)
                ->with(['documents', 'recipients'])
                ->firstOrFail();

            $visibilitySettings = $this->getDocumentVisibilitySettings($envelope);

            return $this->successResponse([
                'document_visibility' => $visibilitySettings,
            ], 'Document visibility settings retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * PUT /accounts/{accountId}/envelopes/{envelopeId}/document_visibility
     *
     * Update document visibility settings for multiple documents
     */
    public function update(Request $request, string $accountId, string $envelopeId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'document_visibility' => 'required|array|min:1',
                'document_visibility.*.document_id' => 'required|string',
                'document_visibility.*.visible_to_recipients' => 'sometimes|array',
                'document_visibility.*.visible_to_recipients.*' => 'string', // recipient IDs
                'document_visibility.*.rights' => 'sometimes|string|in:view,download,edit',
            ]);

            $account = Account::where('account_id', $accountId)->firstOrFail();

            $envelope = Envelope::where('account_id', $account->id)
                ->where('envelope_id', $envelopeId)
                ->firstOrFail();

            // Validate envelope is in draft status
            if (!$envelope->isDraft()) {
                return $this->errorResponse(
                    'Cannot modify document visibility for non-draft envelopes',
                    400
                );
            }

            DB::beginTransaction();

            try {
                foreach ($validated['document_visibility'] as $setting) {
                    $document = EnvelopeDocument::where('envelope_id', $envelope->id)
                        ->where('document_id', $setting['document_id'])
                        ->firstOrFail();

                    // Update document visibility settings
                    $document->update([
                        'visible_to_recipients' => $setting['visible_to_recipients'] ?? null,
                        'document_rights' => $setting['rights'] ?? 'view',
                    ]);
                }

                DB::commit();

                $envelope->load('documents');
                $visibilitySettings = $this->getDocumentVisibilitySettings($envelope);

                return $this->successResponse([
                    'document_visibility' => $visibilitySettings,
                ], 'Document visibility settings updated successfully');
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/recipients
     *
     * Get recipients who can view a specific document
     */
    public function getDocumentRecipients(
        string $accountId,
        string $envelopeId,
        string $documentId
    ): JsonResponse {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();

            $envelope = Envelope::where('account_id', $account->id)
                ->where('envelope_id', $envelopeId)
                ->with('recipients')
                ->firstOrFail();

            $document = EnvelopeDocument::where('envelope_id', $envelope->id)
                ->where('document_id', $documentId)
                ->firstOrFail();

            // Get visible recipients
            $visibleRecipientIds = $document->visible_to_recipients ?? [];

            // If null or empty, all recipients can see it
            if (empty($visibleRecipientIds)) {
                $visibleRecipients = $envelope->recipients;
            } else {
                $visibleRecipients = $envelope->recipients->whereIn(
                    'recipient_id',
                    $visibleRecipientIds
                );
            }

            return $this->successResponse([
                'document_id' => $document->document_id,
                'document_name' => $document->name,
                'visible_to_all_recipients' => empty($visibleRecipientIds),
                'recipients' => $visibleRecipients->map(function ($recipient) {
                    return [
                        'recipient_id' => $recipient->recipient_id,
                        'name' => $recipient->name,
                        'email' => $recipient->email,
                        'type' => $recipient->type,
                        'routing_order' => $recipient->routing_order,
                    ];
                })->values(),
            ], 'Document recipients retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * PUT /accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/recipients
     *
     * Update recipients who can view a specific document
     */
    public function updateDocumentRecipients(
        Request $request,
        string $accountId,
        string $envelopeId,
        string $documentId
    ): JsonResponse {
        try {
            $validated = $request->validate([
                'visible_to_recipients' => 'required|array',
                'visible_to_recipients.*' => 'string', // recipient IDs
                'rights' => 'sometimes|string|in:view,download,edit',
            ]);

            $account = Account::where('account_id', $accountId)->firstOrFail();

            $envelope = Envelope::where('account_id', $account->id)
                ->where('envelope_id', $envelopeId)
                ->with('recipients')
                ->firstOrFail();

            // Validate envelope is in draft status
            if (!$envelope->isDraft()) {
                return $this->errorResponse(
                    'Cannot modify document visibility for non-draft envelopes',
                    400
                );
            }

            $document = EnvelopeDocument::where('envelope_id', $envelope->id)
                ->where('document_id', $documentId)
                ->firstOrFail();

            // Validate all recipient IDs exist
            $validRecipientIds = $envelope->recipients->pluck('recipient_id')->toArray();
            $invalidRecipientIds = array_diff(
                $validated['visible_to_recipients'],
                $validRecipientIds
            );

            if (!empty($invalidRecipientIds)) {
                return $this->validationErrorResponse([
                    'visible_to_recipients' => [
                        'Invalid recipient IDs: ' . implode(', ', $invalidRecipientIds)
                    ]
                ]);
            }

            DB::beginTransaction();

            try {
                $document->update([
                    'visible_to_recipients' => $validated['visible_to_recipients'],
                    'document_rights' => $validated['rights'] ?? $document->document_rights ?? 'view',
                ]);

                DB::commit();

                // Get updated visible recipients
                $visibleRecipients = $envelope->recipients->whereIn(
                    'recipient_id',
                    $validated['visible_to_recipients']
                );

                return $this->successResponse([
                    'document_id' => $document->document_id,
                    'document_name' => $document->name,
                    'visible_to_all_recipients' => false,
                    'recipients' => $visibleRecipients->map(function ($recipient) {
                        return [
                            'recipient_id' => $recipient->recipient_id,
                            'name' => $recipient->name,
                            'email' => $recipient->email,
                            'type' => $recipient->type,
                            'routing_order' => $recipient->routing_order,
                        ];
                    })->values(),
                ], 'Document visibility updated successfully');
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /accounts/{accountId}/templates/{templateId}/document_visibility
     *
     * Get document visibility settings for a template
     */
    public function getTemplateVisibility(string $accountId, string $templateId): JsonResponse
    {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();

            $template = Template::where('account_id', $account->id)
                ->where('template_id', $templateId)
                ->firstOrFail();

            // Get all documents for this template
            $documents = EnvelopeDocument::where('template_id', $template->id)
                ->orderBy('order')
                ->get();

            $visibilitySettings = $documents->map(function ($document) {
                $visibleRecipientIds = $document->visible_to_recipients ?? [];

                return [
                    'document_id' => $document->document_id,
                    'document_name' => $document->name,
                    'visible_to_all_recipients' => empty($visibleRecipientIds),
                    'visible_to_recipients' => $visibleRecipientIds,
                    'rights' => $document->document_rights ?? 'view',
                ];
            })->values()->toArray();

            return $this->successResponse([
                'template_id' => $template->template_id,
                'document_visibility' => $visibilitySettings,
            ], 'Template document visibility settings retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * PUT /accounts/{accountId}/templates/{templateId}/document_visibility
     *
     * Update document visibility settings for a template
     */
    public function updateTemplateVisibility(
        Request $request,
        string $accountId,
        string $templateId
    ): JsonResponse {
        try {
            $validated = $request->validate([
                'document_visibility' => 'required|array|min:1',
                'document_visibility.*.document_id' => 'required|string',
                'document_visibility.*.visible_to_recipients' => 'sometimes|array',
                'document_visibility.*.visible_to_recipients.*' => 'string', // recipient IDs
                'document_visibility.*.rights' => 'sometimes|string|in:view,download,edit',
            ]);

            $account = Account::where('account_id', $accountId)->firstOrFail();

            $template = Template::where('account_id', $account->id)
                ->where('template_id', $templateId)
                ->firstOrFail();

            DB::beginTransaction();

            try {
                foreach ($validated['document_visibility'] as $setting) {
                    $document = EnvelopeDocument::where('template_id', $template->id)
                        ->where('document_id', $setting['document_id'])
                        ->firstOrFail();

                    // Update document visibility settings
                    $document->update([
                        'visible_to_recipients' => $setting['visible_to_recipients'] ?? null,
                        'document_rights' => $setting['rights'] ?? 'view',
                    ]);
                }

                DB::commit();

                // Get updated visibility settings
                $documents = EnvelopeDocument::where('template_id', $template->id)
                    ->orderBy('order')
                    ->get();

                $visibilitySettings = $documents->map(function ($document) {
                    $visibleRecipientIds = $document->visible_to_recipients ?? [];

                    return [
                        'document_id' => $document->document_id,
                        'document_name' => $document->name,
                        'visible_to_all_recipients' => empty($visibleRecipientIds),
                        'visible_to_recipients' => $visibleRecipientIds,
                        'rights' => $document->document_rights ?? 'view',
                    ];
                })->values()->toArray();

                return $this->successResponse([
                    'template_id' => $template->template_id,
                    'document_visibility' => $visibilitySettings,
                ], 'Template document visibility settings updated successfully');
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Helper: Get document visibility settings for all documents in envelope
     */
    protected function getDocumentVisibilitySettings(Envelope $envelope): array
    {
        return $envelope->documents->map(function ($document) use ($envelope) {
            $visibleRecipientIds = $document->visible_to_recipients ?? [];

            return [
                'document_id' => $document->document_id,
                'document_name' => $document->name,
                'visible_to_all_recipients' => empty($visibleRecipientIds),
                'visible_to_recipients' => $visibleRecipientIds,
                'rights' => $document->document_rights ?? 'view',
                'recipient_count' => empty($visibleRecipientIds)
                    ? $envelope->recipients->count()
                    : count($visibleRecipientIds),
            ];
        })->values()->toArray();
    }
}
