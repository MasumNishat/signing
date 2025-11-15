<?php

namespace App\Services;

use App\Models\Envelope;
use App\Models\EnvelopeRecipient;
use App\Exceptions\Custom\BusinessLogicException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Recipient Service
 *
 * Handles all business logic for envelope recipient operations.
 * Manages recipient CRUD, routing orders, and status transitions.
 */
class RecipientService
{
    /**
     * Get all recipients for an envelope
     *
     * @param Envelope $envelope
     * @param array $options Filter options
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function listRecipients(Envelope $envelope, array $options = [])
    {
        $query = $envelope->recipients()->with('tabs');

        // Filter by type
        if (isset($options['type'])) {
            $query->where('recipient_type', $options['type']);
        }

        // Filter by status
        if (isset($options['status'])) {
            $query->where('status', $options['status']);
        }

        // Filter by routing order
        if (isset($options['routing_order'])) {
            $query->where('routing_order', $options['routing_order']);
        }

        // Default sort by routing order
        $query->orderBy('routing_order')->orderBy('name');

        return $query->get();
    }

    /**
     * Add recipients to an envelope
     *
     * @param Envelope $envelope
     * @param array $recipients Array of recipient data
     * @return array Created recipients
     * @throws BusinessLogicException
     */
    public function addRecipients(Envelope $envelope, array $recipients): array
    {
        // Validate envelope is in draft or sent status
        if (!in_array($envelope->status, ['draft', 'sent'])) {
            throw new BusinessLogicException('Recipients can only be added to draft or sent envelopes');
        }

        $createdRecipients = [];

        DB::beginTransaction();

        try {
            foreach ($recipients as $recipientData) {
                $recipient = $this->addRecipient($envelope, $recipientData);
                $createdRecipients[] = $recipient;
            }

            DB::commit();

            return $createdRecipients;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to add recipients to envelope', [
                'envelope_id' => $envelope->envelope_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Add a single recipient to an envelope
     *
     * @param Envelope $envelope
     * @param array $data Recipient data
     * @return EnvelopeRecipient
     */
    protected function addRecipient(Envelope $envelope, array $data): EnvelopeRecipient
    {
        // Determine routing order if not specified
        if (!isset($data['routing_order'])) {
            $maxOrder = $envelope->recipients()->max('routing_order') ?? 0;
            $data['routing_order'] = $maxOrder + 1;
        }

        // Create recipient
        $recipient = $envelope->recipients()->create([
            'recipient_id' => $data['recipient_id'] ?? null, // Auto-generated if null
            'recipient_type' => $data['recipient_type'] ?? EnvelopeRecipient::TYPE_SIGNER,
            'role_name' => $data['role_name'] ?? null,
            'name' => $data['name'],
            'email' => $data['email'],
            'routing_order' => $data['routing_order'],
            'status' => EnvelopeRecipient::STATUS_CREATED,

            // Authentication
            'access_code' => $data['access_code'] ?? null,
            'require_id_lookup' => $data['require_id_lookup'] ?? false,
            'id_check_configuration_name' => $data['id_check_configuration_name'] ?? null,
            'phone_authentication_country_code' => $data['phone_authentication_country_code'] ?? null,
            'phone_authentication_number' => $data['phone_authentication_number'] ?? null,
            'sms_authentication_country_code' => $data['sms_authentication_country_code'] ?? null,
            'sms_authentication_number' => $data['sms_authentication_number'] ?? null,

            // Settings
            'can_sign_offline' => $data['can_sign_offline'] ?? false,
            'require_signer_certificate' => $data['require_signer_certificate'] ?? false,
            'require_sign_on_paper' => $data['require_sign_on_paper'] ?? false,
            'sign_in_each_location' => $data['sign_in_each_location'] ?? false,

            // Host info (for in-person signing)
            'host_name' => $data['host_name'] ?? null,
            'host_email' => $data['host_email'] ?? null,

            // Metadata
            'client_user_id' => $data['client_user_id'] ?? null,
            'embedded_recipient_start_url' => $data['embedded_recipient_start_url'] ?? null,
        ]);

        return $recipient;
    }

    /**
     * Get a specific recipient
     *
     * @param Envelope $envelope
     * @param string $recipientId
     * @return EnvelopeRecipient
     * @throws BusinessLogicException
     */
    public function getRecipient(Envelope $envelope, string $recipientId): EnvelopeRecipient
    {
        $recipient = $envelope->recipients()
            ->where('recipient_id', $recipientId)
            ->with('tabs')
            ->first();

        if (!$recipient) {
            throw new BusinessLogicException('Recipient not found');
        }

        return $recipient;
    }

    /**
     * Update a recipient
     *
     * @param EnvelopeRecipient $recipient
     * @param array $data Update data
     * @return EnvelopeRecipient
     * @throws BusinessLogicException
     */
    public function updateRecipient(EnvelopeRecipient $recipient, array $data): EnvelopeRecipient
    {
        // Validate envelope status
        if ($recipient->hasSigned()) {
            throw new BusinessLogicException('Cannot update recipient who has already signed');
        }

        DB::beginTransaction();

        try {
            // Update basic fields
            if (isset($data['name'])) {
                $recipient->name = $data['name'];
            }

            if (isset($data['email'])) {
                $recipient->email = $data['email'];
            }

            if (isset($data['role_name'])) {
                $recipient->role_name = $data['role_name'];
            }

            if (isset($data['routing_order'])) {
                $this->updateRoutingOrder($recipient, $data['routing_order']);
            }

            // Update authentication settings
            if (isset($data['access_code'])) {
                $recipient->access_code = $data['access_code'];
            }

            if (isset($data['require_id_lookup'])) {
                $recipient->require_id_lookup = $data['require_id_lookup'];
            }

            if (isset($data['id_check_configuration_name'])) {
                $recipient->id_check_configuration_name = $data['id_check_configuration_name'];
            }

            // Update phone/SMS authentication
            if (isset($data['phone_authentication'])) {
                $recipient->phone_authentication_country_code = $data['phone_authentication']['country_code'] ?? null;
                $recipient->phone_authentication_number = $data['phone_authentication']['number'] ?? null;
            }

            if (isset($data['sms_authentication'])) {
                $recipient->sms_authentication_country_code = $data['sms_authentication']['country_code'] ?? null;
                $recipient->sms_authentication_number = $data['sms_authentication']['number'] ?? null;
            }

            // Update settings
            if (isset($data['can_sign_offline'])) {
                $recipient->can_sign_offline = $data['can_sign_offline'];
            }

            if (isset($data['require_signer_certificate'])) {
                $recipient->require_signer_certificate = $data['require_signer_certificate'];
            }

            if (isset($data['require_sign_on_paper'])) {
                $recipient->require_sign_on_paper = $data['require_sign_on_paper'];
            }

            if (isset($data['sign_in_each_location'])) {
                $recipient->sign_in_each_location = $data['sign_in_each_location'];
            }

            // Update host info
            if (isset($data['host_name'])) {
                $recipient->host_name = $data['host_name'];
            }

            if (isset($data['host_email'])) {
                $recipient->host_email = $data['host_email'];
            }

            $recipient->save();

            DB::commit();

            return $recipient->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a recipient
     *
     * @param EnvelopeRecipient $recipient
     * @return bool
     * @throws BusinessLogicException
     */
    public function deleteRecipient(EnvelopeRecipient $recipient): bool
    {
        // Validate recipient hasn't signed
        if ($recipient->hasSigned()) {
            throw new BusinessLogicException('Cannot delete recipient who has already signed');
        }

        DB::beginTransaction();

        try {
            $envelope = $recipient->envelope;
            $deletedRoutingOrder = $recipient->routing_order;

            // Delete associated tabs
            $recipient->tabs()->delete();

            // Delete recipient
            $recipient->delete();

            // Reorder remaining recipients in the same routing order group
            $envelope->recipients()
                ->where('routing_order', '>', $deletedRoutingOrder)
                ->decrement('routing_order');

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete recipient', [
                'recipient_id' => $recipient->recipient_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update recipient routing order
     *
     * @param EnvelopeRecipient $recipient
     * @param int $newOrder
     * @return void
     */
    protected function updateRoutingOrder(EnvelopeRecipient $recipient, int $newOrder): void
    {
        if ($recipient->routing_order === $newOrder) {
            return;
        }

        $oldOrder = $recipient->routing_order;
        $envelope = $recipient->envelope;

        // Adjust other recipients' routing orders
        if ($newOrder < $oldOrder) {
            // Moving up: shift others down
            $envelope->recipients()
                ->where('id', '!=', $recipient->id)
                ->whereBetween('routing_order', [$newOrder, $oldOrder - 1])
                ->increment('routing_order');
        } else {
            // Moving down: shift others up
            $envelope->recipients()
                ->where('id', '!=', $recipient->id)
                ->whereBetween('routing_order', [$oldOrder + 1, $newOrder])
                ->decrement('routing_order');
        }

        $recipient->routing_order = $newOrder;
    }

    /**
     * Get recipient metadata for API response
     *
     * @param EnvelopeRecipient $recipient
     * @return array
     */
    public function getMetadata(EnvelopeRecipient $recipient): array
    {
        return [
            'recipient_id' => $recipient->recipient_id,
            'recipient_type' => $recipient->recipient_type,
            'role_name' => $recipient->role_name,
            'name' => $recipient->name,
            'email' => $recipient->email,
            'routing_order' => $recipient->routing_order,
            'status' => $recipient->status,
            'sent_date_time' => $recipient->sent_date_time?->toIso8601String(),
            'delivered_date_time' => $recipient->delivered_date_time?->toIso8601String(),
            'signed_date_time' => $recipient->signed_date_time?->toIso8601String(),
            'declined_date_time' => $recipient->declined_date_time?->toIso8601String(),
            'declined_reason' => $recipient->declined_reason,
            'authentication' => [
                'access_code_required' => !empty($recipient->access_code),
                'require_id_lookup' => $recipient->require_id_lookup,
                'id_check_configuration' => $recipient->id_check_configuration_name,
                'phone_authentication' => !empty($recipient->phone_authentication_number) ? [
                    'country_code' => $recipient->phone_authentication_country_code,
                    'number' => $recipient->phone_authentication_number,
                ] : null,
                'sms_authentication' => !empty($recipient->sms_authentication_number) ? [
                    'country_code' => $recipient->sms_authentication_country_code,
                    'number' => $recipient->sms_authentication_number,
                ] : null,
            ],
            'settings' => [
                'can_sign_offline' => $recipient->can_sign_offline,
                'require_signer_certificate' => $recipient->require_signer_certificate,
                'require_sign_on_paper' => $recipient->require_sign_on_paper,
                'sign_in_each_location' => $recipient->sign_in_each_location,
            ],
            'host_info' => $recipient->recipient_type === EnvelopeRecipient::TYPE_IN_PERSON_SIGNER && $recipient->host_name ? [
                'host_name' => $recipient->host_name,
                'host_email' => $recipient->host_email,
            ] : null,
            'client_user_id' => $recipient->client_user_id,
            'created_at' => $recipient->created_at->toIso8601String(),
            'updated_at' => $recipient->updated_at->toIso8601String(),
        ];
    }

    /**
     * Resend notification to a recipient
     *
     * @param EnvelopeRecipient $recipient
     * @return bool
     * @throws BusinessLogicException
     */
    public function resendNotification(EnvelopeRecipient $recipient): bool
    {
        if ($recipient->hasSigned()) {
            throw new BusinessLogicException('Cannot resend notification to recipient who has already signed');
        }

        if ($recipient->hasDeclined()) {
            throw new BusinessLogicException('Cannot resend notification to recipient who has declined');
        }

        // In production, this would trigger email/SMS notification
        Log::info('Recipient notification resent', [
            'recipient_id' => $recipient->recipient_id,
            'envelope_id' => $recipient->envelope->envelope_id,
            'email' => $recipient->email,
        ]);

        return true;
    }

    /**
     * Get current routing order recipient(s)
     *
     * @param Envelope $envelope
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCurrentRoutingOrderRecipients(Envelope $envelope)
    {
        // Find the lowest routing order where recipient hasn't signed
        $currentOrder = $envelope->recipients()
            ->where('status', '!=', EnvelopeRecipient::STATUS_SIGNED)
            ->where('status', '!=', EnvelopeRecipient::STATUS_COMPLETED)
            ->where('status', '!=', EnvelopeRecipient::STATUS_DECLINED)
            ->min('routing_order');

        if (!$currentOrder) {
            return collect();
        }

        return $envelope->recipients()
            ->where('routing_order', $currentOrder)
            ->get();
    }

    /**
     * Bulk update recipients
     *
     * @param Envelope $envelope
     * @param array $updates Array of [recipient_id => data]
     * @return array Updated recipients
     * @throws BusinessLogicException
     */
    public function bulkUpdateRecipients(Envelope $envelope, array $updates): array
    {
        if (!in_array($envelope->status, ['draft', 'sent'])) {
            throw new BusinessLogicException('Recipients can only be updated in draft or sent envelopes');
        }

        $updatedRecipients = [];

        DB::beginTransaction();

        try {
            foreach ($updates as $recipientId => $data) {
                $recipient = $this->getRecipient($envelope, $recipientId);

                // Skip if already signed
                if (!$recipient->hasSigned()) {
                    $this->updateRecipient($recipient, $data);
                    $updatedRecipients[] = $recipient->fresh();
                }
            }

            DB::commit();

            return $updatedRecipients;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to bulk update recipients', [
                'envelope_id' => $envelope->envelope_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Bulk delete recipients
     *
     * @param Envelope $envelope
     * @param array $recipientIds
     * @return int Number of deleted recipients
     * @throws BusinessLogicException
     */
    public function bulkDeleteRecipients(Envelope $envelope, array $recipientIds): int
    {
        if (!$envelope->isDraft()) {
            throw new BusinessLogicException('Recipients can only be deleted from draft envelopes');
        }

        DB::beginTransaction();

        try {
            $count = 0;
            foreach ($recipientIds as $recipientId) {
                $recipient = $envelope->recipients()
                    ->where('recipient_id', $recipientId)
                    ->first();

                if ($recipient && !$recipient->hasSigned()) {
                    $this->deleteRecipient($recipient);
                    $count++;
                }
            }

            DB::commit();

            return $count;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to bulk delete recipients', [
                'envelope_id' => $envelope->envelope_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate signing URL for recipient
     *
     * @param EnvelopeRecipient $recipient
     * @param array $options URL options (return_url, authentication, etc.)
     * @return array URL data
     * @throws BusinessLogicException
     */
    public function generateSigningUrl(EnvelopeRecipient $recipient, array $options = []): array
    {
        if ($recipient->hasSigned()) {
            throw new BusinessLogicException('Recipient has already signed');
        }

        if ($recipient->hasDeclined()) {
            throw new BusinessLogicException('Recipient has declined');
        }

        // Generate secure token
        $token = $this->generateSecureToken($recipient);

        // Build signing URL
        $baseUrl = config('app.url');
        $signingUrl = sprintf(
            '%s/signing/%s/%s',
            $baseUrl,
            $recipient->envelope->envelope_id,
            $token
        );

        // Add return URL if specified
        if (isset($options['return_url'])) {
            $signingUrl .= '?return_url=' . urlencode($options['return_url']);
        }

        // Calculate expiration (default 30 days)
        $expiresIn = $options['expires_in'] ?? 2592000; // 30 days in seconds
        $expiresAt = now()->addSeconds($expiresIn);

        // Store token in database (in production, use cache or database)
        // For now, we'll just return the URL

        Log::info('Signing URL generated', [
            'recipient_id' => $recipient->recipient_id,
            'envelope_id' => $recipient->envelope->envelope_id,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

        return [
            'url' => $signingUrl,
            'token' => $token,
            'expires_at' => $expiresAt->toIso8601String(),
            'expires_in' => $expiresIn,
            'recipient_id' => $recipient->recipient_id,
            'recipient_name' => $recipient->name,
            'recipient_email' => $recipient->email,
        ];
    }

    /**
     * Generate secure token for recipient signing
     *
     * @param EnvelopeRecipient $recipient
     * @return string
     */
    protected function generateSecureToken(EnvelopeRecipient $recipient): string
    {
        $data = sprintf(
            '%s:%s:%s:%d',
            $recipient->envelope->envelope_id,
            $recipient->recipient_id,
            $recipient->email,
            time()
        );

        return hash_hmac('sha256', $data, config('app.key'));
    }

    /**
     * Verify recipient access (for signing)
     *
     * @param EnvelopeRecipient $recipient
     * @param array $authData Authentication data (access_code, etc.)
     * @return bool
     * @throws BusinessLogicException
     */
    public function verifyRecipientAccess(EnvelopeRecipient $recipient, array $authData = []): bool
    {
        // Check if recipient can currently act (based on routing order)
        $workflowService = app(WorkflowService::class);
        if (!$workflowService->canRecipientAct($recipient)) {
            throw new BusinessLogicException('It is not your turn to sign yet. Please wait for previous recipients to complete.');
        }

        // Verify access code if required
        if ($recipient->access_code && !empty($recipient->access_code)) {
            if (!isset($authData['access_code']) || $authData['access_code'] !== $recipient->access_code) {
                throw new BusinessLogicException('Invalid access code');
            }
        }

        // Verify ID lookup if required (placeholder)
        if ($recipient->require_id_lookup) {
            // In production, this would integrate with ID verification service
            Log::info('ID lookup verification required', [
                'recipient_id' => $recipient->recipient_id,
            ]);
        }

        // Verify phone authentication if required (placeholder)
        if ($recipient->phone_authentication_number) {
            // In production, this would send SMS code and verify
            Log::info('Phone authentication required', [
                'recipient_id' => $recipient->recipient_id,
                'phone' => $recipient->phone_authentication_number,
            ]);
        }

        return true;
    }

    /**
     * Get consumer disclosure for recipient
     *
     * @param EnvelopeRecipient $recipient
     * @param string $langCode Language code (e.g., 'en', 'es')
     * @return array
     */
    public function getConsumerDisclosure(EnvelopeRecipient $recipient, string $langCode = 'en'): array
    {
        $account = $recipient->envelope->account;

        // Get account's consumer disclosure for the language
        $disclosure = $account->consumerDisclosures()
            ->where('language_code', $langCode)
            ->first();

        if (!$disclosure) {
            // Fall back to default language
            $disclosure = $account->consumerDisclosures()
                ->where('language_code', 'en')
                ->first();
        }

        return [
            'account_id' => $account->account_id,
            'recipient_id' => $recipient->recipient_id,
            'language_code' => $disclosure->language_code ?? 'en',
            'esign_text' => $disclosure->esign_text ?? 'Electronic Record and Signature Disclosure',
            'esign_agreement' => $disclosure->esign_agreement ?? true,
            'must_agree_to_esign' => $disclosure->must_agree_to_esign ?? true,
            'pdf_id' => $disclosure->pdf_id ?? null,
            'withdrawal_address_1' => $disclosure->withdrawal_address_1 ?? null,
            'withdrawal_address_2' => $disclosure->withdrawal_address_2 ?? null,
            'withdrawal_email' => $disclosure->withdrawal_email ?? null,
            'withdrawal_phone' => $disclosure->withdrawal_phone ?? null,
            'custom_disclosure_text' => $disclosure->custom_disclosure_text ?? null,
        ];
    }

    /**
     * Generate identity proof token for recipient
     *
     * @param EnvelopeRecipient $recipient
     * @return array
     * @throws BusinessLogicException
     */
    public function generateIdentityProofToken(EnvelopeRecipient $recipient): array
    {
        if (!$recipient->require_id_lookup) {
            throw new BusinessLogicException('Identity verification is not required for this recipient');
        }

        // Generate token for identity verification session
        $token = hash_hmac('sha256', sprintf(
            '%s:%s:%d',
            $recipient->recipient_id,
            $recipient->email,
            time()
        ), config('app.key'));

        // In production, this would create a session with ID verification provider
        Log::info('Identity proof token generated', [
            'recipient_id' => $recipient->recipient_id,
            'envelope_id' => $recipient->envelope->envelope_id,
        ]);

        return [
            'token' => $token,
            'recipient_id' => $recipient->recipient_id,
            'recipient_name' => $recipient->name,
            'recipient_email' => $recipient->email,
            'verification_url' => config('app.url') . '/verify/' . $token,
            'expires_at' => now()->addHours(24)->toIso8601String(),
            'id_check_configuration' => $recipient->id_check_configuration_name ?? 'default',
        ];
    }

    /**
     * Get recipient signature image
     *
     * @param EnvelopeRecipient $recipient
     * @param string $imageType 'signature' or 'initials'
     * @return array|null
     */
    public function getRecipientImage(EnvelopeRecipient $recipient, string $imageType = 'signature'): ?array
    {
        // In production, this would retrieve from storage or signature service
        $columnName = $imageType === 'signature' ? 'signature_image_uri' : 'initials_image_uri';

        if (empty($recipient->$columnName)) {
            return null;
        }

        return [
            'recipient_id' => $recipient->recipient_id,
            'image_type' => $imageType,
            'image_uri' => $recipient->$columnName,
            'content_type' => 'image/png',
            'created_at' => $recipient->signed_date_time?->toIso8601String(),
        ];
    }

    /**
     * Update recipient signature/initials image
     *
     * @param EnvelopeRecipient $recipient
     * @param string $imageType 'signature' or 'initials'
     * @param array $data Image data (base64 or file)
     * @return array
     */
    public function updateRecipientImage(EnvelopeRecipient $recipient, string $imageType, array $data): array
    {
        DB::beginTransaction();

        try {
            // In production, this would:
            // 1. Validate image format and size
            // 2. Store image in secure storage (S3, etc.)
            // 3. Generate thumbnail if needed
            // 4. Update recipient record

            $columnName = $imageType === 'signature' ? 'signature_image_uri' : 'initials_image_uri';

            // Placeholder: generate storage path
            $storagePath = sprintf(
                'signatures/%s/%s/%s.png',
                $recipient->envelope->account->account_id,
                $recipient->recipient_id,
                $imageType
            );

            $recipient->$columnName = $storagePath;
            $recipient->save();

            DB::commit();

            Log::info('Recipient image updated', [
                'recipient_id' => $recipient->recipient_id,
                'image_type' => $imageType,
                'storage_path' => $storagePath,
            ]);

            return [
                'recipient_id' => $recipient->recipient_id,
                'image_type' => $imageType,
                'image_uri' => $storagePath,
                'content_type' => 'image/png',
                'updated_at' => $recipient->updated_at->toIso8601String(),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update recipient image', [
                'recipient_id' => $recipient->recipient_id,
                'image_type' => $imageType,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update recipient tabs
     *
     * @param EnvelopeRecipient $recipient
     * @param array $tabs Array of tab data
     * @return array
     * @throws BusinessLogicException
     */
    public function updateRecipientTabs(EnvelopeRecipient $recipient, array $tabs): array
    {
        if ($recipient->hasSigned()) {
            throw new BusinessLogicException('Cannot update tabs for recipient who has already signed');
        }

        DB::beginTransaction();

        try {
            $tabService = app(\App\Services\TabService::class);
            $updatedTabs = [];

            foreach ($tabs as $tabData) {
                if (!isset($tabData['tab_id'])) {
                    continue;
                }

                $tab = $recipient->tabs()
                    ->where('tab_id', $tabData['tab_id'])
                    ->first();

                if ($tab) {
                    $tab = $tabService->updateTab($tab, $tabData);
                    $updatedTabs[] = $tab;
                }
            }

            DB::commit();

            return array_map(function ($tab) use ($tabService) {
                return $tabService->getMetadata($tab);
            }, $updatedTabs);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update recipient tabs', [
                'recipient_id' => $recipient->recipient_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete recipient tabs
     *
     * @param EnvelopeRecipient $recipient
     * @param array $tabIds Array of tab IDs to delete
     * @return int Number of deleted tabs
     * @throws BusinessLogicException
     */
    public function deleteRecipientTabs(EnvelopeRecipient $recipient, array $tabIds): int
    {
        if ($recipient->hasSigned()) {
            throw new BusinessLogicException('Cannot delete tabs for recipient who has already signed');
        }

        DB::beginTransaction();

        try {
            $count = $recipient->tabs()
                ->whereIn('tab_id', $tabIds)
                ->delete();

            DB::commit();

            Log::info('Recipient tabs deleted', [
                'recipient_id' => $recipient->recipient_id,
                'deleted_count' => $count,
            ]);

            return $count;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete recipient tabs', [
                'recipient_id' => $recipient->recipient_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
