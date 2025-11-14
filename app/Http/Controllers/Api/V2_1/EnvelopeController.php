<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Http\Controllers\Api\BaseController;
use App\Models\Account;
use App\Models\Envelope;
use App\Services\EnvelopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Envelope Controller
 *
 * Handles envelope CRUD operations and state management.
 *
 * @group Envelopes
 */
class EnvelopeController extends BaseController
{
    /**
     * Envelope service instance.
     *
     * @var EnvelopeService
     */
    protected EnvelopeService $envelopeService;

    /**
     * Create a new controller instance.
     *
     * @param  EnvelopeService  $envelopeService
     */
    public function __construct(EnvelopeService $envelopeService)
    {
        $this->envelopeService = $envelopeService;
    }

    /**
     * List envelopes for an account.
     *
     * @param  Request  $request
     * @param  string  $accountId
     * @return JsonResponse
     */
    public function index(Request $request, string $accountId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        // Validate query parameters
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|string|in:created,sent,delivered,signed,completed,declined,voided',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'sender_user_id' => 'nullable|integer|exists:users,id',
            'search' => 'nullable|string|max:255',
            'sort_by' => 'nullable|string|in:created_date_time,sent_date_time,email_subject,status',
            'sort_order' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $filters = $validator->validated();
        $perPage = $filters['per_page'] ?? 20;
        unset($filters['per_page']);

        $envelopes = $this->envelopeService->listEnvelopes($account, $filters, $perPage);

        return $this->paginated($envelopes, 'Envelopes retrieved successfully');
    }

    /**
     * Create a new envelope.
     *
     * @param  Request  $request
     * @param  string  $accountId
     * @return JsonResponse
     */
    public function store(Request $request, string $accountId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        // Validate request
        $validator = Validator::make($request->all(), [
            'email_subject' => 'required|string|max:500',
            'email_blurb' => 'nullable|string',
            'sender_user_id' => 'nullable|integer|exists:users,id',

            // Envelope settings
            'enable_wet_sign' => 'nullable|boolean',
            'allow_markup' => 'nullable|boolean',
            'allow_reassign' => 'nullable|boolean',
            'allow_view_history' => 'nullable|boolean',
            'enforce_signer_visibility' => 'nullable|boolean',

            // Notification settings
            'reminder_enabled' => 'nullable|boolean',
            'reminder_delay' => 'nullable|integer|min:1',
            'reminder_frequency' => 'nullable|integer|min:1',
            'expire_enabled' => 'nullable|boolean',
            'expire_after' => 'nullable|integer|min:1',
            'expire_warn' => 'nullable|integer|min:1',

            // Workflow settings
            'enable_sequential_signing' => 'nullable|boolean',

            // Documents
            'documents' => 'required|array|min:1',
            'documents.*.name' => 'required|string|max:255',
            'documents.*.document_base64' => 'nullable|string',
            'documents.*.file' => 'nullable|file|max:25000|mimes:pdf,doc,docx',
            'documents.*.file_extension' => 'nullable|string|max:20',
            'documents.*.order' => 'nullable|integer|min:1',
            'documents.*.signable' => 'nullable|boolean',
            'documents.*.include_in_download' => 'nullable|boolean',

            // Recipients
            'recipients' => 'required|array|min:1',
            'recipients.*.type' => 'required|string|in:signer,viewer,approver,certifiedDelivery',
            'recipients.*.name' => 'required|string|max:255',
            'recipients.*.email' => 'required|email|max:255',
            'recipients.*.routing_order' => 'nullable|integer|min:1',
            'recipients.*.tabs' => 'nullable|array',
            'recipients.*.tabs.*.type' => 'required|string',
            'recipients.*.tabs.*.tab_label' => 'nullable|string|max:255',
            'recipients.*.tabs.*.page_number' => 'required|integer|min:1',
            'recipients.*.tabs.*.x_position' => 'required|integer|min:0',
            'recipients.*.tabs.*.y_position' => 'required|integer|min:0',
            'recipients.*.tabs.*.width' => 'nullable|integer|min:1',
            'recipients.*.tabs.*.height' => 'nullable|integer|min:1',
            'recipients.*.tabs.*.required' => 'nullable|boolean',

            // Custom fields
            'custom_fields' => 'nullable|array',
            'custom_fields.*.name' => 'required|string|max:255',
            'custom_fields.*.value' => 'nullable|string',
            'custom_fields.*.type' => 'nullable|string|in:text,list',
            'custom_fields.*.required' => 'nullable|boolean',
            'custom_fields.*.show' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $envelope = $this->envelopeService->createEnvelope($account, $validator->validated());

            return $this->created($envelope, 'Envelope created successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get a specific envelope.
     *
     * @param  string  $accountId
     * @param  string  $envelopeId
     * @return JsonResponse
     */
    public function show(string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = $this->envelopeService->getEnvelope($account, $envelopeId);

        if (!$envelope) {
            return $this->notFound('Envelope not found');
        }

        return $this->success($envelope, 'Envelope retrieved successfully');
    }

    /**
     * Update an envelope.
     *
     * @param  Request  $request
     * @param  string  $accountId
     * @param  string  $envelopeId
     * @return JsonResponse
     */
    public function update(Request $request, string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        // Validate request
        $validator = Validator::make($request->all(), [
            'email_subject' => 'nullable|string|max:500',
            'email_blurb' => 'nullable|string',
            'enable_wet_sign' => 'nullable|boolean',
            'allow_markup' => 'nullable|boolean',
            'allow_reassign' => 'nullable|boolean',
            'allow_view_history' => 'nullable|boolean',
            'enforce_signer_visibility' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $updatedEnvelope = $this->envelopeService->updateEnvelope($envelope, $validator->validated());

            return $this->success($updatedEnvelope, 'Envelope updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Delete an envelope (soft delete).
     *
     * @param  string  $accountId
     * @param  string  $envelopeId
     * @return JsonResponse
     */
    public function destroy(string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        try {
            $this->envelopeService->deleteEnvelope($envelope);

            return $this->noContent('Envelope deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Send an envelope.
     *
     * @param  string  $accountId
     * @param  string  $envelopeId
     * @return JsonResponse
     */
    public function send(string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        try {
            $sentEnvelope = $this->envelopeService->sendEnvelope($envelope);

            return $this->success($sentEnvelope, 'Envelope sent successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Void an envelope.
     *
     * @param  Request  $request
     * @param  string  $accountId
     * @param  string  $envelopeId
     * @return JsonResponse
     */
    public function void(Request $request, string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        // Validate request
        $validator = Validator::make($request->all(), [
            'voided_reason' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $voidedEnvelope = $this->envelopeService->voidEnvelope(
                $envelope,
                $request->input('voided_reason')
            );

            return $this->success($voidedEnvelope, 'Envelope voided successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get envelope statistics for an account.
     *
     * @param  string  $accountId
     * @return JsonResponse
     */
    public function statistics(string $accountId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $statistics = $this->envelopeService->getEnvelopeStatistics($account);

        return $this->success($statistics, 'Envelope statistics retrieved successfully');
    }

    /**
     * Get envelope notification settings.
     *
     * @param  string  $accountId
     * @param  string  $envelopeId
     * @return JsonResponse
     */
    public function getNotification(string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $settings = $this->envelopeService->getNotificationSettings($envelope);

        return $this->success($settings, 'Notification settings retrieved successfully');
    }

    /**
     * Update envelope notification settings.
     *
     * @param  Request  $request
     * @param  string  $accountId
     * @param  string  $envelopeId
     * @return JsonResponse
     */
    public function updateNotification(Request $request, string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        // Validate request
        $validator = Validator::make($request->all(), [
            'reminders.reminderEnabled' => 'nullable|string|in:true,false',
            'reminders.reminderDelay' => 'nullable|string',
            'reminders.reminderFrequency' => 'nullable|string',
            'expirations.expireEnabled' => 'nullable|string|in:true,false',
            'expirations.expireAfter' => 'nullable|string',
            'expirations.expireWarn' => 'nullable|string',
            'useAccountDefaults' => 'nullable|string|in:true,false',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $updatedEnvelope = $this->envelopeService->updateNotificationSettings(
                $envelope,
                $validator->validated()
            );

            $settings = $this->envelopeService->getNotificationSettings($updatedEnvelope);

            return $this->success($settings, 'Notification settings updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get envelope email settings.
     *
     * @param  string  $accountId
     * @param  string  $envelopeId
     * @return JsonResponse
     */
    public function getEmailSettings(string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $settings = $this->envelopeService->getEmailSettings($envelope);

        return $this->success($settings, 'Email settings retrieved successfully');
    }

    /**
     * Update envelope email settings.
     *
     * @param  Request  $request
     * @param  string  $accountId
     * @param  string  $envelopeId
     * @return JsonResponse
     */
    public function updateEmailSettings(Request $request, string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        // Validate request
        $validator = Validator::make($request->all(), [
            'replyEmailAddressOverride' => 'nullable|email|max:255',
            'replyEmailNameOverride' => 'nullable|string|max:255',
            'bccEmailAddresses' => 'nullable|array',
            'bccEmailAddresses.*' => 'email|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $updatedEnvelope = $this->envelopeService->updateEmailSettings(
                $envelope,
                $validator->validated()
            );

            $settings = $this->envelopeService->getEmailSettings($updatedEnvelope);

            return $this->success($settings, 'Email settings updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get envelope custom fields.
     *
     * @param  string  $accountId
     * @param  string  $envelopeId
     * @return JsonResponse
     */
    public function getCustomFields(string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $customFields = $this->envelopeService->getCustomFields($envelope);

        return $this->success($customFields, 'Custom fields retrieved successfully');
    }

    /**
     * Create or update envelope custom fields.
     *
     * @param  Request  $request
     * @param  string  $accountId
     * @param  string  $envelopeId
     * @return JsonResponse
     */
    public function updateCustomFields(Request $request, string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        // Validate request
        $validator = Validator::make($request->all(), [
            'textCustomFields' => 'nullable|array',
            'textCustomFields.*.name' => 'required|string|max:255',
            'textCustomFields.*.value' => 'nullable|string',
            'textCustomFields.*.required' => 'nullable|string|in:true,false',
            'textCustomFields.*.show' => 'nullable|string|in:true,false',
            'listCustomFields' => 'nullable|array',
            'listCustomFields.*.name' => 'required|string|max:255',
            'listCustomFields.*.value' => 'nullable|string',
            'listCustomFields.*.required' => 'nullable|string|in:true,false',
            'listCustomFields.*.show' => 'nullable|string|in:true,false',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $updatedEnvelope = $this->envelopeService->updateCustomFields(
                $envelope,
                $validator->validated()
            );

            $customFields = $this->envelopeService->getCustomFields($updatedEnvelope);

            return $this->success($customFields, 'Custom fields updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Create envelope custom fields (POST).
     *
     * @param  Request  $request
     * @param  string  $accountId
     * @param  string  $envelopeId
     * @return JsonResponse
     */
    public function createCustomFields(Request $request, string $accountId, string $envelopeId): JsonResponse
    {
        // POST and PUT do the same thing for custom fields (replace all)
        return $this->updateCustomFields($request, $accountId, $envelopeId);
    }

    /**
     * Delete envelope custom fields.
     *
     * @param  string  $accountId
     * @param  string  $envelopeId
     * @return JsonResponse
     */
    public function deleteCustomFields(string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        try {
            $this->envelopeService->deleteCustomFields($envelope);

            return $this->noContent('Custom fields deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get envelope lock status.
     *
     * @param  string  $accountId
     * @param  string  $envelopeId
     * @return JsonResponse
     */
    public function getLock(string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $lock = $this->envelopeService->getLock($envelope);

        if (!$lock) {
            return $this->error('Envelope is not locked', 404);
        }

        return $this->success($lock, 'Lock information retrieved successfully');
    }

    /**
     * Create envelope lock.
     *
     * @param  Request  $request
     * @param  string  $accountId
     * @param  string  $envelopeId
     * @return JsonResponse
     */
    public function createLock(Request $request, string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        // Validate request
        $validator = Validator::make($request->all(), [
            'lockDurationInSeconds' => 'nullable|integer|min:60|max:3600',
            'lockedByApp' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $duration = $request->input('lockDurationInSeconds', 300);

        try {
            $lock = $this->envelopeService->createLock(
                $envelope,
                $request->user(),
                (int) $duration
            );

            return $this->created($lock, 'Envelope locked successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Update envelope lock.
     *
     * @param  Request  $request
     * @param  string  $accountId
     * @param  string  $envelopeId
     * @return JsonResponse
     */
    public function updateLock(Request $request, string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        // Validate request
        $validator = Validator::make($request->all(), [
            'lockToken' => 'required|string',
            'lockDurationInSeconds' => 'nullable|integer|min:60|max:3600',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $duration = $request->input('lockDurationInSeconds', 300);

        try {
            $lock = $this->envelopeService->updateLock(
                $envelope,
                $request->input('lockToken'),
                (int) $duration
            );

            return $this->success($lock, 'Lock updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Delete envelope lock.
     *
     * @param  Request  $request
     * @param  string  $accountId
     * @param  string  $envelopeId
     * @return JsonResponse
     */
    public function deleteLock(Request $request, string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $lockToken = $request->header('X-DocuSign-Lock-Token') ?? $request->input('lockToken');

        try {
            $this->envelopeService->deleteLock($envelope, $lockToken);

            return $this->noContent('Lock deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
