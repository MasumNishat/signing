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

    /**
     * Get envelope audit events.
     *
     * @param  string  $accountId
     * @param  string  $envelopeId
     * @return JsonResponse
     */
    public function getAuditEvents(string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $auditEvents = $this->envelopeService->getAuditEvents($envelope);

        $formattedEvents = $auditEvents->map(function ($event) {
            return [
                'eventType' => $event->event_type,
                'eventTimestamp' => $event->event_timestamp->toIso8601String(),
                'userId' => (string) ($event->user_id ?? ''),
                'userName' => $event->user_name ?? '',
                'userEmail' => $event->user_email ?? '',
                'metadata' => $event->metadata ? json_decode($event->metadata, true) : null,
            ];
        });

        return $this->success(['auditEvents' => $formattedEvents], 'Audit events retrieved successfully');
    }

    /**
     * Get envelope workflow.
     *
     * @param  string  $accountId
     * @param  string  $envelopeId
     * @return JsonResponse
     */
    public function getWorkflow(string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $workflow = $this->envelopeService->getWorkflow($envelope);

        if (!$workflow) {
            return $this->error('Envelope does not have a workflow configured', 404);
        }

        return $this->success($workflow, 'Workflow retrieved successfully');
    }

    /**
     * Update envelope workflow.
     *
     * @param  Request  $request
     * @param  string  $accountId
     * @param  string  $envelopeId
     * @return JsonResponse
     */
    public function updateWorkflow(Request $request, string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        // Validate request
        $validator = Validator::make($request->all(), [
            'workflowStatus' => 'nullable|string|in:in_progress,paused,completed',
            'scheduledSending.resumeDate' => 'nullable|date',
            'workflowSteps' => 'nullable|array',
            'workflowSteps.*.action' => 'nullable|string|in:sign,approve,view,certify',
            'workflowSteps.*.itemId' => 'nullable|string',
            'workflowSteps.*.recipientId' => 'nullable|string',
            'workflowSteps.*.status' => 'nullable|string|in:pending,in_progress,completed,failed',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $updatedEnvelope = $this->envelopeService->updateWorkflow(
                $envelope,
                $validator->validated()
            );

            $workflow = $this->envelopeService->getWorkflow($updatedEnvelope);

            return $this->success($workflow, 'Workflow updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get envelope correction view URL (placeholder).
     *
     * @param  Request  $request
     * @param  string  $accountId
     * @param  string  $envelopeId
     * @return JsonResponse
     */
    public function getCorrectView(Request $request, string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        // In a real implementation, this would generate a URL for the correction UI
        $url = config('app.url') . "/envelopes/{$envelope->envelope_id}/correct";

        return $this->success([
            'url' => $url,
            'expiresIn' => 300, // 5 minutes
        ], 'Correction view URL generated successfully');
    }

    /**
     * Get envelope sender view URL (placeholder).
     *
     * @param  Request  $request
     * @param  string  $accountId
     * @param  string  $envelopeId
     * @return JsonResponse
     */
    public function getSenderView(Request $request, string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        // In a real implementation, this would generate a URL for the sender UI
        $url = config('app.url') . "/envelopes/{$envelope->envelope_id}/sender";

        return $this->success([
            'url' => $url,
            'expiresIn' => 300, // 5 minutes
        ], 'Sender view URL generated successfully');
    }

    /**
     * Get envelope recipient view URL (placeholder).
     *
     * @param  Request  $request
     * @param  string  $accountId
     * @param  string  $envelopeId
     * @return JsonResponse
     */
    public function getRecipientView(Request $request, string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        // Validate recipient information
        $validator = Validator::make($request->all(), [
            'recipientId' => 'required|string',
            'returnUrl' => 'nullable|url',
            'authenticationMethod' => 'nullable|string|in:none,email,password,phone,knowledge',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        // In a real implementation, this would generate a URL for the recipient signing UI
        $recipientId = $request->input('recipientId');
        $url = config('app.url') . "/envelopes/{$envelope->envelope_id}/recipients/{$recipientId}/sign";

        return $this->success([
            'url' => $url,
            'expiresIn' => 300, // 5 minutes
        ], 'Recipient view URL generated successfully');
    }

    /**
     * Get HTML definitions for all documents in envelope
     *
     * GET /v2.1/accounts/{accountId}/envelopes/{envelopeId}/html_definitions
     *
     * @param string $accountId
     * @param string $envelopeId
     * @return JsonResponse
     */
    public function getHtmlDefinitions(string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        // Use DocumentService for HTML definitions
        $documentService = app(\App\Services\DocumentService::class);

        return $this->success(
            $documentService->getEnvelopeHtmlDefinitions($envelope),
            'HTML definitions retrieved successfully'
        );
    }

    /**
     * Generate responsive HTML preview for all envelope documents
     *
     * POST /v2.1/accounts/{accountId}/envelopes/{envelopeId}/responsive_html_preview
     *
     * @param Request $request
     * @param string $accountId
     * @param string $envelopeId
     * @return JsonResponse
     */
    public function generateResponsiveHtmlPreview(
        Request $request,
        string $accountId,
        string $envelopeId
    ): JsonResponse {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $htmlDefinition = $request->input('html_definition', []);

        // Use DocumentService for responsive HTML preview
        $documentService = app(\App\Services\DocumentService::class);

        return $this->success(
            $documentService->generateEnvelopeResponsiveHtmlPreview($envelope, $htmlDefinition),
            'Responsive HTML preview generated successfully'
        );
    }

    /**
     * Get comments transcript for an envelope
     *
     * GET /v2.1/accounts/{accountId}/envelopes/{envelopeId}/comments/transcript
     *
     * Returns a transcript of all comments and notes made on the envelope
     */
    public function getCommentsTranscript(string $accountId, string $envelopeId): JsonResponse
    {
        try {
            $envelope = Envelope::where('account_id', $accountId)
                ->where('envelope_id', $envelopeId)
                ->with(['auditEvents' => function ($query) {
                    $query->whereIn('event_type', ['comment_added', 'note_added', 'declined_reason'])
                        ->orderBy('created_at', 'asc');
                }])
                ->firstOrFail();

            // Build transcript from audit events
            $transcript = [];
            foreach ($envelope->auditEvents as $event) {
                $transcript[] = [
                    'timestamp' => $event->created_at?->toIso8601String(),
                    'user' => $event->user_name,
                    'event_type' => $event->event_type,
                    'comment' => $event->metadata['comment'] ?? $event->metadata['note'] ?? $event->metadata['reason'] ?? '',
                ];
            }

            return $this->successResponse([
                'envelope_id' => $envelope->envelope_id,
                'comments' => $transcript,
                'total_comments' => count($transcript),
            ], 'Comments transcript retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get form data for an envelope
     *
     * GET /v2.1/accounts/{accountId}/envelopes/{envelopeId}/form_data
     *
     * Returns all form field data collected from recipients
     */
    public function getFormData(string $accountId, string $envelopeId): JsonResponse
    {
        try {
            $envelope = Envelope::where('account_id', $accountId)
                ->where('envelope_id', $envelopeId)
                ->with(['tabs.recipient', 'recipients'])
                ->firstOrFail();

            // Extract form data from tabs
            $formData = [];
            $recipientData = [];

            foreach ($envelope->tabs as $tab) {
                // Only include tabs with values (filled by recipients)
                if (!empty($tab->value)) {
                    if (!isset($recipientData[$tab->recipient_id])) {
                        $recipientData[$tab->recipient_id] = [
                            'recipient_id' => $tab->recipient->recipient_id ?? null,
                            'recipient_name' => $tab->recipient->name ?? 'Unknown',
                            'recipient_email' => $tab->recipient->email ?? null,
                            'tabs' => [],
                        ];
                    }

                    $recipientData[$tab->recipient_id]['tabs'][] = [
                        'tab_id' => $tab->tab_id,
                        'tab_label' => $tab->tab_label,
                        'tab_type' => $tab->tab_type,
                        'value' => $tab->value,
                        'document_id' => $tab->document_id,
                        'page_number' => $tab->page_number,
                    ];
                }
            }

            // Convert to indexed array
            $formData = array_values($recipientData);

            return $this->successResponse([
                'envelope_id' => $envelope->envelope_id,
                'envelope_status' => $envelope->status,
                'recipients' => $formData,
                'total_recipients_with_data' => count($formData),
            ], 'Form data retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
