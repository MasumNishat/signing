<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Http\Controllers\Api\BaseController;
use App\Models\Account;
use App\Models\Envelope;
use App\Services\RecipientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Recipient Controller
 *
 * Handles envelope recipient management operations.
 * Supports adding, updating, deleting recipients and managing routing orders.
 *
 * Endpoints:
 * - GET    /recipients           - List all recipients
 * - POST   /recipients           - Add recipients
 * - GET    /recipients/{id}      - Get specific recipient
 * - PUT    /recipients/{id}      - Update recipient
 * - DELETE /recipients/{id}      - Delete recipient
 * - POST   /recipients/{id}/resend - Resend notification
 */
class RecipientController extends BaseController
{
    /**
     * Recipient service
     */
    protected RecipientService $recipientService;

    /**
     * Initialize controller
     */
    public function __construct(RecipientService $recipientService)
    {
        $this->recipientService = $recipientService;
    }

    /**
     * List all recipients for an envelope
     *
     * GET /v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients
     *
     * @param Request $request
     * @param string $accountId
     * @param string $envelopeId
     * @return JsonResponse
     */
    public function index(Request $request, string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $recipients = $this->recipientService->listRecipients($envelope, [
            'type' => $request->query('type'),
            'status' => $request->query('status'),
            'routing_order' => $request->query('routing_order'),
        ]);

        $formattedRecipients = $recipients->map(function ($recipient) {
            return $this->recipientService->getMetadata($recipient);
        });

        return $this->success([
            'envelope_id' => $envelope->envelope_id,
            'total_recipients' => $recipients->count(),
            'recipients' => $formattedRecipients,
        ], 'Recipients retrieved successfully');
    }

    /**
     * Add recipients to an envelope
     *
     * POST /v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients
     *
     * @param Request $request
     * @param string $accountId
     * @param string $envelopeId
     * @return JsonResponse
     */
    public function store(Request $request, string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'recipients' => 'required|array|min:1',
            'recipients.*.recipient_id' => 'nullable|string|max:100',
            'recipients.*.recipient_type' => 'required|string|in:signer,carbon_copy,certified_delivery,in_person_signer,agent,editor,intermediary',
            'recipients.*.role_name' => 'nullable|string|max:255',
            'recipients.*.name' => 'required|string|max:255',
            'recipients.*.email' => 'required|email|max:255',
            'recipients.*.routing_order' => 'nullable|integer|min:1',
            'recipients.*.access_code' => 'nullable|string|max:100',
            'recipients.*.require_id_lookup' => 'nullable|boolean',
            'recipients.*.id_check_configuration_name' => 'nullable|string|max:255',
            'recipients.*.phone_authentication' => 'nullable|array',
            'recipients.*.phone_authentication.country_code' => 'required_with:recipients.*.phone_authentication|string|max:10',
            'recipients.*.phone_authentication.number' => 'required_with:recipients.*.phone_authentication|string|max:50',
            'recipients.*.sms_authentication' => 'nullable|array',
            'recipients.*.sms_authentication.country_code' => 'required_with:recipients.*.sms_authentication|string|max:10',
            'recipients.*.sms_authentication.number' => 'required_with:recipients.*.sms_authentication|string|max:50',
            'recipients.*.can_sign_offline' => 'nullable|boolean',
            'recipients.*.require_signer_certificate' => 'nullable|boolean',
            'recipients.*.require_sign_on_paper' => 'nullable|boolean',
            'recipients.*.sign_in_each_location' => 'nullable|boolean',
            'recipients.*.host_name' => 'nullable|string|max:255',
            'recipients.*.host_email' => 'nullable|email|max:255',
            'recipients.*.client_user_id' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $recipients = $this->recipientService->addRecipients(
                $envelope,
                $request->input('recipients', [])
            );

            $formattedRecipients = array_map(function ($recipient) {
                return $this->recipientService->getMetadata($recipient);
            }, $recipients);

            return $this->created([
                'envelope_id' => $envelope->envelope_id,
                'recipients' => $formattedRecipients,
            ], 'Recipients added successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get a specific recipient
     *
     * GET /v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients/{recipientId}
     *
     * @param string $accountId
     * @param string $envelopeId
     * @param string $recipientId
     * @return JsonResponse
     */
    public function show(string $accountId, string $envelopeId, string $recipientId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        try {
            $recipient = $this->recipientService->getRecipient($envelope, $recipientId);

            return $this->success(
                $this->recipientService->getMetadata($recipient),
                'Recipient retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * Update a recipient
     *
     * PUT /v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients/{recipientId}
     *
     * @param Request $request
     * @param string $accountId
     * @param string $envelopeId
     * @param string $recipientId
     * @return JsonResponse
     */
    public function update(
        Request $request,
        string $accountId,
        string $envelopeId,
        string $recipientId
    ): JsonResponse {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'role_name' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'routing_order' => 'nullable|integer|min:1',
            'access_code' => 'nullable|string|max:100',
            'require_id_lookup' => 'nullable|boolean',
            'id_check_configuration_name' => 'nullable|string|max:255',
            'phone_authentication' => 'nullable|array',
            'phone_authentication.country_code' => 'required_with:phone_authentication|string|max:10',
            'phone_authentication.number' => 'required_with:phone_authentication|string|max:50',
            'sms_authentication' => 'nullable|array',
            'sms_authentication.country_code' => 'required_with:sms_authentication|string|max:10',
            'sms_authentication.number' => 'required_with:sms_authentication|string|max:50',
            'can_sign_offline' => 'nullable|boolean',
            'require_signer_certificate' => 'nullable|boolean',
            'require_sign_on_paper' => 'nullable|boolean',
            'sign_in_each_location' => 'nullable|boolean',
            'host_name' => 'nullable|string|max:255',
            'host_email' => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $recipient = $this->recipientService->getRecipient($envelope, $recipientId);

            $updatedRecipient = $this->recipientService->updateRecipient(
                $recipient,
                $request->all()
            );

            return $this->success(
                $this->recipientService->getMetadata($updatedRecipient),
                'Recipient updated successfully'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Delete a recipient
     *
     * DELETE /v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients/{recipientId}
     *
     * @param string $accountId
     * @param string $envelopeId
     * @param string $recipientId
     * @return JsonResponse
     */
    public function destroy(string $accountId, string $envelopeId, string $recipientId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        try {
            $recipient = $this->recipientService->getRecipient($envelope, $recipientId);

            $this->recipientService->deleteRecipient($recipient);

            return $this->noContent('Recipient deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Resend notification to a recipient
     *
     * POST /v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients/{recipientId}/resend
     *
     * @param string $accountId
     * @param string $envelopeId
     * @param string $recipientId
     * @return JsonResponse
     */
    public function resend(string $accountId, string $envelopeId, string $recipientId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        try {
            $recipient = $this->recipientService->getRecipient($envelope, $recipientId);

            $this->recipientService->resendNotification($recipient);

            return $this->success([
                'recipient_id' => $recipient->recipient_id,
                'notification_sent' => true,
            ], 'Notification resent successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
