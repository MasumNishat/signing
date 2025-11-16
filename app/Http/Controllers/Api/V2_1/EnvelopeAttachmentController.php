<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Models\Envelope;
use App\Models\EnvelopeAttachment;
use App\Services\EnvelopeAttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * EnvelopeAttachmentController
 *
 * Handles envelope attachment operations.
 * Supports 7 endpoints for managing envelope attachments.
 */
class EnvelopeAttachmentController extends BaseController
{
    protected EnvelopeAttachmentService $attachmentService;

    public function __construct(EnvelopeAttachmentService $attachmentService)
    {
        $this->attachmentService = $attachmentService;
    }

    /**
     * GET /accounts/{accountId}/envelopes/{envelopeId}/attachments
     *
     * Get all attachments for an envelope
     */
    public function index(string $accountId, string $envelopeId): JsonResponse
    {
        try {
            $envelope = Envelope::where('account_id', $accountId)
                ->where('envelope_id', $envelopeId)
                ->firstOrFail();

            $attachments = $this->attachmentService->getAttachments($envelope);

            return $this->successResponse([
                'attachments' => $attachments->map(function ($attachment) {
                    return $this->formatAttachment($attachment);
                }),
            ], 'Attachments retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /accounts/{accountId}/envelopes/{envelopeId}/attachments
     *
     * Create attachments for an envelope (bulk create)
     */
    public function store(Request $request, string $accountId, string $envelopeId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'attachments' => 'required|array|min:1',
                'attachments.*.attachment_id' => 'sometimes|string|max:100',
                'attachments.*.label' => 'sometimes|string|max:255',
                'attachments.*.name' => 'required|string|max:255',
                'attachments.*.attachment_type' => 'sometimes|string|in:signer,sender',
                'attachments.*.data_base64' => 'required_without:attachments.*.remote_url|string',
                'attachments.*.remote_url' => 'required_without:attachments.*.data_base64|url',
                'attachments.*.file_extension' => 'sometimes|string|max:10',
                'attachments.*.access_control' => 'sometimes|string|in:signer,sender,all',
                'attachments.*.display' => 'sometimes|string|max:50',
            ]);

            $envelope = Envelope::where('account_id', $accountId)
                ->where('envelope_id', $envelopeId)
                ->firstOrFail();

            $attachments = $this->attachmentService->createAttachments($envelope, $validated['attachments']);

            return $this->createdResponse([
                'attachments' => $attachments->map(function ($attachment) {
                    return $this->formatAttachment($attachment);
                }),
            ], 'Attachments created successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * PUT /accounts/{accountId}/envelopes/{envelopeId}/attachments
     *
     * Update all attachments for an envelope (replace all)
     */
    public function update(Request $request, string $accountId, string $envelopeId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'attachments' => 'required|array',
                'attachments.*.attachment_id' => 'sometimes|string|max:100',
                'attachments.*.label' => 'sometimes|string|max:255',
                'attachments.*.name' => 'required|string|max:255',
                'attachments.*.attachment_type' => 'sometimes|string|in:signer,sender',
                'attachments.*.data_base64' => 'required_without:attachments.*.remote_url|string',
                'attachments.*.remote_url' => 'required_without:attachments.*.data_base64|url',
                'attachments.*.file_extension' => 'sometimes|string|max:10',
                'attachments.*.access_control' => 'sometimes|string|in:signer,sender,all',
                'attachments.*.display' => 'sometimes|string|max:50',
            ]);

            $envelope = Envelope::where('account_id', $accountId)
                ->where('envelope_id', $envelopeId)
                ->firstOrFail();

            $attachments = $this->attachmentService->updateAttachments($envelope, $validated['attachments']);

            return $this->successResponse([
                'attachments' => $attachments->map(function ($attachment) {
                    return $this->formatAttachment($attachment);
                }),
            ], 'Attachments updated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * DELETE /accounts/{accountId}/envelopes/{envelopeId}/attachments
     *
     * Delete all attachments for an envelope
     */
    public function destroy(string $accountId, string $envelopeId): JsonResponse
    {
        try {
            $envelope = Envelope::where('account_id', $accountId)
                ->where('envelope_id', $envelopeId)
                ->firstOrFail();

            $count = $this->attachmentService->deleteAttachments($envelope);

            return $this->successResponse([
                'deleted_count' => $count,
            ], 'Attachments deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /accounts/{accountId}/envelopes/{envelopeId}/attachments/{attachmentId}
     *
     * Get a specific attachment
     */
    public function show(string $accountId, string $envelopeId, string $attachmentId): JsonResponse
    {
        try {
            $envelope = Envelope::where('account_id', $accountId)
                ->where('envelope_id', $envelopeId)
                ->firstOrFail();

            $attachment = $this->attachmentService->getAttachment($envelope, $attachmentId);

            return $this->successResponse(
                $this->formatAttachment($attachment, true), // Include base64 for single attachment
                'Attachment retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * PUT /accounts/{accountId}/envelopes/{envelopeId}/attachments/{attachmentId}
     *
     * Update a specific attachment
     */
    public function updateSingle(Request $request, string $accountId, string $envelopeId, string $attachmentId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'label' => 'sometimes|string|max:255',
                'name' => 'sometimes|string|max:255',
                'attachment_type' => 'sometimes|string|in:signer,sender',
                'data_base64' => 'sometimes|string',
                'remote_url' => 'sometimes|url',
                'file_extension' => 'sometimes|string|max:10',
                'access_control' => 'sometimes|string|in:signer,sender,all',
                'display' => 'sometimes|string|max:50',
            ]);

            $envelope = Envelope::where('account_id', $accountId)
                ->where('envelope_id', $envelopeId)
                ->firstOrFail();

            $attachment = $this->attachmentService->getAttachment($envelope, $attachmentId);
            $attachment = $this->attachmentService->updateAttachment($attachment, $validated);

            return $this->successResponse(
                $this->formatAttachment($attachment),
                'Attachment updated successfully'
            );
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * DELETE /accounts/{accountId}/envelopes/{envelopeId}/attachments/{attachmentId}
     *
     * Delete a specific attachment (not in spec, but added for completeness)
     */
    public function destroySingle(string $accountId, string $envelopeId, string $attachmentId): JsonResponse
    {
        try {
            $envelope = Envelope::where('account_id', $accountId)
                ->where('envelope_id', $envelopeId)
                ->firstOrFail();

            $attachment = $this->attachmentService->getAttachment($envelope, $attachmentId);
            $this->attachmentService->deleteAttachment($attachment);

            return $this->noContent();
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Format attachment for response
     *
     * @param EnvelopeAttachment $attachment
     * @param bool $includeBase64 Whether to include base64 data in response
     * @return array
     */
    protected function formatAttachment(EnvelopeAttachment $attachment, bool $includeBase64 = false): array
    {
        $formatted = [
            'attachment_id' => $attachment->attachment_id,
            'label' => $attachment->label,
            'name' => $attachment->name,
            'attachment_type' => $attachment->attachment_type,
            'file_extension' => $attachment->file_extension,
            'access_control' => $attachment->access_control,
            'display' => $attachment->display,
            'size_bytes' => $attachment->size_bytes,
            'remote_url' => $attachment->remote_url,
            'created_at' => $attachment->created_at?->toIso8601String(),
            'updated_at' => $attachment->updated_at?->toIso8601String(),
        ];

        // Only include base64 data when specifically requested (e.g., for single attachment retrieval)
        if ($includeBase64 && $attachment->hasBase64Data()) {
            $formatted['data_base64'] = $attachment->data_base64;
        }

        return $formatted;
    }
}
