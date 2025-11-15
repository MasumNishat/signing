<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Models\Template;
use App\Models\EnvelopeRecipient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * TemplateRecipientController
 *
 * Handles template recipient operations.
 * Templates reuse envelope_recipients table with template_id column.
 */
class TemplateRecipientController extends BaseController
{
    /**
     * GET /accounts/{accountId}/templates/{templateId}/recipients
     *
     * Get all recipients for a template
     */
    public function index(string $accountId, string $templateId): JsonResponse
    {
        try {
            $template = Template::where('account_id', $accountId)
                ->where('template_id', $templateId)
                ->with('recipients')
                ->firstOrFail();

            return $this->successResponse([
                'template_recipients' => $template->recipients->map(function ($recipient) {
                    return $this->formatRecipient($recipient);
                }),
            ], 'Template recipients retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /accounts/{accountId}/templates/{templateId}/recipients
     *
     * Add recipients to a template
     */
    public function store(Request $request, string $accountId, string $templateId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'recipients' => 'required|array|min:1',
                'recipients.*.recipient_id' => 'sometimes|string|max:100',
                'recipients.*.name' => 'required|string|max:255',
                'recipients.*.email' => 'required|email|max:255',
                'recipients.*.type' => 'required|string|in:signer,viewer,approver,certified_delivery,in_person_signer,carbon_copy,agent,intermediary',
                'recipients.*.routing_order' => 'sometimes|integer|min:1',
                'recipients.*.access_code' => 'sometimes|string|max:50',
                'recipients.*.note' => 'sometimes|string|max:1000',
                'recipients.*.phone_number' => 'sometimes|string|max:50',
            ]);

            $template = Template::where('account_id', $accountId)
                ->where('template_id', $templateId)
                ->firstOrFail();

            // Create recipients associated with template
            $recipients = [];
            foreach ($validated['recipients'] as $recipData) {
                $recipient = EnvelopeRecipient::create([
                    'template_id' => $template->id,
                    'recipient_id' => $recipData['recipient_id'] ?? null,
                    'name' => $recipData['name'],
                    'email' => $recipData['email'],
                    'type' => $recipData['type'],
                    'routing_order' => $recipData['routing_order'] ?? (count($recipients) + 1),
                    'access_code' => $recipData['access_code'] ?? null,
                    'note' => $recipData['note'] ?? null,
                    'phone_number' => $recipData['phone_number'] ?? null,
                    'status' => 'created',
                ]);
                $recipients[] = $recipient;
            }

            return $this->createdResponse([
                'template_recipients' => collect($recipients)->map(function ($recipient) {
                    return $this->formatRecipient($recipient);
                }),
            ], 'Template recipients created successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * PUT /accounts/{accountId}/templates/{templateId}/recipients
     *
     * Update all recipients for a template (replace)
     */
    public function update(Request $request, string $accountId, string $templateId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'recipients' => 'required|array',
                'recipients.*.recipient_id' => 'sometimes|string|max:100',
                'recipients.*.name' => 'required|string|max:255',
                'recipients.*.email' => 'required|email|max:255',
                'recipients.*.type' => 'required|string|in:signer,viewer,approver,certified_delivery,in_person_signer,carbon_copy,agent,intermediary',
                'recipients.*.routing_order' => 'sometimes|integer|min:1',
                'recipients.*.access_code' => 'sometimes|string|max:50',
                'recipients.*.note' => 'sometimes|string|max:1000',
                'recipients.*.phone_number' => 'sometimes|string|max:50',
            ]);

            $template = Template::where('account_id', $accountId)
                ->where('template_id', $templateId)
                ->firstOrFail();

            // Delete existing recipients
            EnvelopeRecipient::where('template_id', $template->id)->delete();

            // Create new recipients
            $recipients = [];
            foreach ($validated['recipients'] as $recipData) {
                $recipient = EnvelopeRecipient::create([
                    'template_id' => $template->id,
                    'recipient_id' => $recipData['recipient_id'] ?? null,
                    'name' => $recipData['name'],
                    'email' => $recipData['email'],
                    'type' => $recipData['type'],
                    'routing_order' => $recipData['routing_order'] ?? (count($recipients) + 1),
                    'access_code' => $recipData['access_code'] ?? null,
                    'note' => $recipData['note'] ?? null,
                    'phone_number' => $recipData['phone_number'] ?? null,
                    'status' => 'created',
                ]);
                $recipients[] = $recipient;
            }

            return $this->successResponse([
                'template_recipients' => collect($recipients)->map(function ($recipient) {
                    return $this->formatRecipient($recipient);
                }),
            ], 'Template recipients updated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * DELETE /accounts/{accountId}/templates/{templateId}/recipients
     *
     * Delete all recipients from a template
     */
    public function destroy(string $accountId, string $templateId): JsonResponse
    {
        try {
            $template = Template::where('account_id', $accountId)
                ->where('template_id', $templateId)
                ->firstOrFail();

            $count = EnvelopeRecipient::where('template_id', $template->id)->delete();

            return $this->successResponse([
                'deleted_count' => $count,
            ], 'Template recipients deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /accounts/{accountId}/templates/{templateId}/recipients/{recipientId}
     *
     * Get a specific template recipient
     */
    public function show(string $accountId, string $templateId, string $recipientId): JsonResponse
    {
        try {
            $template = Template::where('account_id', $accountId)
                ->where('template_id', $templateId)
                ->firstOrFail();

            $recipient = EnvelopeRecipient::where('template_id', $template->id)
                ->where('recipient_id', $recipientId)
                ->firstOrFail();

            return $this->successResponse(
                $this->formatRecipient($recipient),
                'Template recipient retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * PUT /accounts/{accountId}/templates/{templateId}/recipients/{recipientId}
     *
     * Update a specific template recipient
     */
    public function updateSingle(Request $request, string $accountId, string $templateId, string $recipientId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|max:255',
                'type' => 'sometimes|string|in:signer,viewer,approver,certified_delivery,in_person_signer,carbon_copy,agent,intermediary',
                'routing_order' => 'sometimes|integer|min:1',
                'access_code' => 'sometimes|string|max:50',
                'note' => 'sometimes|string|max:1000',
                'phone_number' => 'sometimes|string|max:50',
            ]);

            $template = Template::where('account_id', $accountId)
                ->where('template_id', $templateId)
                ->firstOrFail();

            $recipient = EnvelopeRecipient::where('template_id', $template->id)
                ->where('recipient_id', $recipientId)
                ->firstOrFail();

            $recipient->update($validated);

            return $this->successResponse(
                $this->formatRecipient($recipient->fresh()),
                'Template recipient updated successfully'
            );
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Format recipient for response
     */
    protected function formatRecipient(EnvelopeRecipient $recipient): array
    {
        return [
            'recipient_id' => $recipient->recipient_id,
            'name' => $recipient->name,
            'email' => $recipient->email,
            'type' => $recipient->type,
            'routing_order' => $recipient->routing_order,
            'status' => $recipient->status,
            'access_code' => $recipient->access_code,
            'note' => $recipient->note,
            'phone_number' => $recipient->phone_number,
            'created_at' => $recipient->created_at?->toIso8601String(),
            'updated_at' => $recipient->updated_at?->toIso8601String(),
        ];
    }
}
