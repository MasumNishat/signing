<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Envelope;
use App\Models\EnvelopeDocument;
use App\Models\EnvelopeRecipient;
use App\Models\EnvelopeTab;
use App\Models\EnvelopeCustomField;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Envelope Service
 *
 * Handles business logic for envelope operations.
 */
class EnvelopeService
{
    /**
     * Create a new envelope.
     *
     * @param  Account  $account
     * @param  array  $data
     * @return Envelope
     * @throws \Exception
     */
    public function createEnvelope(Account $account, array $data): Envelope
    {
        DB::beginTransaction();

        try {
            // Create the envelope
            $envelope = new Envelope();
            $envelope->account_id = $account->id;
            $envelope->email_subject = $data['email_subject'] ?? null;
            $envelope->email_blurb = $data['email_blurb'] ?? null;
            $envelope->status = Envelope::STATUS_CREATED;

            // Set sender information
            if (isset($data['sender_user_id'])) {
                $sender = User::find($data['sender_user_id']);
                if ($sender) {
                    $envelope->sender_user_id = $sender->id;
                    $envelope->sender_name = $sender->first_name . ' ' . $sender->last_name;
                    $envelope->sender_email = $sender->email;
                }
            }

            // Set envelope settings
            $envelope->enable_wet_sign = $data['enable_wet_sign'] ?? false;
            $envelope->allow_markup = $data['allow_markup'] ?? true;
            $envelope->allow_reassign = $data['allow_reassign'] ?? true;
            $envelope->allow_view_history = $data['allow_view_history'] ?? true;
            $envelope->enforce_signer_visibility = $data['enforce_signer_visibility'] ?? false;

            // Notification settings
            $envelope->reminder_enabled = $data['reminder_enabled'] ?? false;
            $envelope->reminder_delay = $data['reminder_delay'] ?? null;
            $envelope->reminder_frequency = $data['reminder_frequency'] ?? null;
            $envelope->expire_enabled = $data['expire_enabled'] ?? false;
            $envelope->expire_after = $data['expire_after'] ?? null;
            $envelope->expire_warn = $data['expire_warn'] ?? null;

            // Workflow settings
            $envelope->enable_sequential_signing = $data['enable_sequential_signing'] ?? false;

            $envelope->save();

            // Add documents
            if (isset($data['documents']) && is_array($data['documents'])) {
                $this->addDocuments($envelope, $data['documents']);
            }

            // Add recipients
            if (isset($data['recipients']) && is_array($data['recipients'])) {
                $this->addRecipients($envelope, $data['recipients']);
            }

            // Add custom fields
            if (isset($data['custom_fields']) && is_array($data['custom_fields'])) {
                $this->addCustomFields($envelope, $data['custom_fields']);
            }

            DB::commit();

            return $envelope->fresh(['documents', 'recipients', 'customFields']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing envelope.
     *
     * @param  Envelope  $envelope
     * @param  array  $data
     * @return Envelope
     * @throws \Exception
     */
    public function updateEnvelope(Envelope $envelope, array $data): Envelope
    {
        if (!$envelope->canBeModified()) {
            throw new \Exception('Envelope cannot be modified in its current status: ' . $envelope->status);
        }

        DB::beginTransaction();

        try {
            // Update basic info
            if (isset($data['email_subject'])) {
                $envelope->email_subject = $data['email_subject'];
            }
            if (isset($data['email_blurb'])) {
                $envelope->email_blurb = $data['email_blurb'];
            }

            // Update settings
            if (isset($data['enable_wet_sign'])) {
                $envelope->enable_wet_sign = $data['enable_wet_sign'];
            }
            if (isset($data['allow_markup'])) {
                $envelope->allow_markup = $data['allow_markup'];
            }
            if (isset($data['allow_reassign'])) {
                $envelope->allow_reassign = $data['allow_reassign'];
            }

            $envelope->save();

            DB::commit();

            return $envelope->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Send an envelope.
     *
     * @param  Envelope  $envelope
     * @return Envelope
     * @throws \Exception
     */
    public function sendEnvelope(Envelope $envelope): Envelope
    {
        if (!$envelope->isDraft()) {
            throw new \Exception('Only draft envelopes can be sent');
        }

        // Validate envelope has required components
        if ($envelope->documents()->count() === 0) {
            throw new \Exception('Envelope must have at least one document');
        }

        if ($envelope->recipients()->count() === 0) {
            throw new \Exception('Envelope must have at least one recipient');
        }

        DB::beginTransaction();

        try {
            $envelope->markAsSent();

            // TODO: Send email notifications to recipients
            // TODO: Create audit event

            DB::commit();

            return $envelope->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Void an envelope.
     *
     * @param  Envelope  $envelope
     * @param  string  $reason
     * @return Envelope
     * @throws \Exception
     */
    public function voidEnvelope(Envelope $envelope, string $reason): Envelope
    {
        if (!$envelope->canBeVoided()) {
            throw new \Exception('Envelope cannot be voided in its current status: ' . $envelope->status);
        }

        DB::beginTransaction();

        try {
            $envelope->markAsVoided($reason);

            // TODO: Send void notifications to recipients
            // TODO: Create audit event

            DB::commit();

            return $envelope->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete an envelope (soft delete).
     *
     * @param  Envelope  $envelope
     * @return bool
     * @throws \Exception
     */
    public function deleteEnvelope(Envelope $envelope): bool
    {
        if (!$envelope->isDraft()) {
            throw new \Exception('Only draft envelopes can be deleted');
        }

        return $envelope->delete();
    }

    /**
     * Get envelope by ID.
     *
     * @param  Account  $account
     * @param  string  $envelopeId
     * @return Envelope|null
     */
    public function getEnvelope(Account $account, string $envelopeId): ?Envelope
    {
        return Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->with(['documents', 'recipients', 'customFields', 'tabs'])
            ->first();
    }

    /**
     * List envelopes for an account.
     *
     * @param  Account  $account
     * @param  array  $filters
     * @param  int  $perPage
     * @return LengthAwarePaginator
     */
    public function listEnvelopes(Account $account, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Envelope::where('account_id', $account->id);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['from_date'])) {
            $query->where('created_date_time', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('created_date_time', '<=', $filters['to_date']);
        }

        if (isset($filters['sender_user_id'])) {
            $query->where('sender_user_id', $filters['sender_user_id']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('email_subject', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('envelope_id', 'like', '%' . $filters['search'] . '%');
            });
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'created_date_time';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Add documents to an envelope.
     *
     * @param  Envelope  $envelope
     * @param  array  $documents
     * @return void
     */
    protected function addDocuments(Envelope $envelope, array $documents): void
    {
        foreach ($documents as $index => $documentData) {
            $document = new EnvelopeDocument();
            $document->envelope_id = $envelope->id;
            $document->name = $documentData['name'];
            $document->order_number = $documentData['order'] ?? ($index + 1);

            // Handle file upload or base64 data
            if (isset($documentData['document_base64'])) {
                $document->document_base64 = $documentData['document_base64'];
            } elseif (isset($documentData['file'])) {
                // TODO: Handle file upload to storage
                $filePath = Storage::put('documents/' . $envelope->envelope_id, $documentData['file']);
                $document->file_path = $filePath;
                $document->file_size = $documentData['file']->getSize();
                $document->mime_type = $documentData['file']->getMimeType();
            }

            $document->file_extension = $documentData['file_extension'] ?? 'pdf';
            $document->signable = $documentData['signable'] ?? true;
            $document->include_in_download = $documentData['include_in_download'] ?? true;

            $document->save();
        }
    }

    /**
     * Add recipients to an envelope.
     *
     * @param  Envelope  $envelope
     * @param  array  $recipients
     * @return void
     */
    protected function addRecipients(Envelope $envelope, array $recipients): void
    {
        foreach ($recipients as $index => $recipientData) {
            $recipient = new EnvelopeRecipient();
            $recipient->envelope_id = $envelope->id;
            $recipient->type = $recipientData['type'] ?? 'signer';
            $recipient->name = $recipientData['name'];
            $recipient->email = $recipientData['email'];
            $recipient->routing_order = $recipientData['routing_order'] ?? ($index + 1);
            $recipient->status = 'pending';

            $recipient->save();

            // Add tabs for this recipient if provided
            if (isset($recipientData['tabs']) && is_array($recipientData['tabs'])) {
                $this->addTabs($envelope, $recipient, $recipientData['tabs']);
            }
        }
    }

    /**
     * Add tabs to an envelope for a recipient.
     *
     * @param  Envelope  $envelope
     * @param  EnvelopeRecipient  $recipient
     * @param  array  $tabs
     * @return void
     */
    protected function addTabs(Envelope $envelope, EnvelopeRecipient $recipient, array $tabs): void
    {
        foreach ($tabs as $tabData) {
            $tab = new EnvelopeTab();
            $tab->envelope_id = $envelope->id;
            $tab->recipient_id = $recipient->id;
            $tab->document_id = $tabData['document_id'] ?? $envelope->documents()->first()?->id;
            $tab->type = $tabData['type'] ?? 'signhere';
            $tab->tab_label = $tabData['tab_label'] ?? '';
            $tab->required = $tabData['required'] ?? true;
            $tab->page_number = $tabData['page_number'] ?? 1;
            $tab->x_position = $tabData['x_position'] ?? 0;
            $tab->y_position = $tabData['y_position'] ?? 0;
            $tab->width = $tabData['width'] ?? 100;
            $tab->height = $tabData['height'] ?? 20;

            $tab->save();
        }
    }

    /**
     * Add custom fields to an envelope.
     *
     * @param  Envelope  $envelope
     * @param  array  $customFields
     * @return void
     */
    protected function addCustomFields(Envelope $envelope, array $customFields): void
    {
        foreach ($customFields as $fieldData) {
            $field = new EnvelopeCustomField();
            $field->envelope_id = $envelope->id;
            $field->name = $fieldData['name'];
            $field->value = $fieldData['value'] ?? '';
            $field->type = $fieldData['type'] ?? 'text';
            $field->required = $fieldData['required'] ?? false;
            $field->show = $fieldData['show'] ?? true;

            $field->save();
        }
    }

    /**
     * Get envelope statistics for an account.
     *
     * @param  Account  $account
     * @return array
     */
    public function getEnvelopeStatistics(Account $account): array
    {
        $total = Envelope::where('account_id', $account->id)->count();
        $sent = Envelope::where('account_id', $account->id)->sent()->count();
        $completed = Envelope::where('account_id', $account->id)->completed()->count();
        $voided = Envelope::where('account_id', $account->id)->voided()->count();
        $draft = Envelope::where('account_id', $account->id)->withStatus(Envelope::STATUS_CREATED)->count();

        return [
            'total' => $total,
            'sent' => $sent,
            'completed' => $completed,
            'voided' => $voided,
            'draft' => $draft,
        ];
    }

    /**
     * Get envelope notification settings.
     *
     * @param  Envelope  $envelope
     * @return array
     */
    public function getNotificationSettings(Envelope $envelope): array
    {
        return [
            'reminders' => [
                'reminderEnabled' => $envelope->reminder_enabled ? 'true' : 'false',
                'reminderDelay' => (string) ($envelope->reminder_delay ?? ''),
                'reminderFrequency' => (string) ($envelope->reminder_frequency ?? ''),
            ],
            'expirations' => [
                'expireEnabled' => $envelope->expire_enabled ? 'true' : 'false',
                'expireAfter' => (string) ($envelope->expire_after ?? ''),
                'expireWarn' => (string) ($envelope->expire_warn ?? ''),
            ],
        ];
    }

    /**
     * Update envelope notification settings.
     *
     * @param  Envelope  $envelope
     * @param  array  $data
     * @return Envelope
     * @throws \Exception
     */
    public function updateNotificationSettings(Envelope $envelope, array $data): Envelope
    {
        // If use account defaults, we would load from account settings
        // For now, we'll apply the provided settings

        if (isset($data['reminders'])) {
            $envelope->reminder_enabled = ($data['reminders']['reminderEnabled'] ?? 'false') === 'true';
            $envelope->reminder_delay = !empty($data['reminders']['reminderDelay'])
                ? (int) $data['reminders']['reminderDelay']
                : null;
            $envelope->reminder_frequency = !empty($data['reminders']['reminderFrequency'])
                ? (int) $data['reminders']['reminderFrequency']
                : null;
        }

        if (isset($data['expirations'])) {
            $envelope->expire_enabled = ($data['expirations']['expireEnabled'] ?? 'false') === 'true';
            $envelope->expire_after = !empty($data['expirations']['expireAfter'])
                ? (int) $data['expirations']['expireAfter']
                : null;
            $envelope->expire_warn = !empty($data['expirations']['expireWarn'])
                ? (int) $data['expirations']['expireWarn']
                : null;
        }

        $envelope->save();

        return $envelope->fresh();
    }

    /**
     * Get envelope email settings.
     *
     * @param  Envelope  $envelope
     * @return array
     */
    public function getEmailSettings(Envelope $envelope): array
    {
        return [
            'replyEmailAddressOverride' => $envelope->sender_email ?? '',
            'replyEmailNameOverride' => $envelope->sender_name ?? '',
            'bccEmailAddresses' => [], // Would come from a separate table
        ];
    }

    /**
     * Update envelope email settings.
     *
     * @param  Envelope  $envelope
     * @param  array  $data
     * @return Envelope
     */
    public function updateEmailSettings(Envelope $envelope, array $data): Envelope
    {
        // Update email-related settings
        // Most email settings would be in envelope metadata

        if (isset($data['replyEmailAddressOverride'])) {
            $envelope->sender_email = $data['replyEmailAddressOverride'];
        }

        if (isset($data['replyEmailNameOverride'])) {
            $envelope->sender_name = $data['replyEmailNameOverride'];
        }

        $envelope->save();

        return $envelope->fresh();
    }

    /**
     * Get envelope custom fields.
     *
     * @param  Envelope  $envelope
     * @return array
     */
    public function getCustomFields(Envelope $envelope): array
    {
        $textCustomFields = [];
        $listCustomFields = [];

        foreach ($envelope->customFields as $field) {
            $fieldData = [
                'fieldId' => (string) $field->id,
                'name' => $field->name,
                'value' => $field->value,
                'required' => $field->required ? 'true' : 'false',
                'show' => $field->show ? 'true' : 'false',
            ];

            if ($field->type === 'list') {
                $listCustomFields[] = $fieldData;
            } else {
                $textCustomFields[] = $fieldData;
            }
        }

        return [
            'textCustomFields' => $textCustomFields,
            'listCustomFields' => $listCustomFields,
        ];
    }

    /**
     * Update or create envelope custom fields.
     *
     * @param  Envelope  $envelope
     * @param  array  $data
     * @return Envelope
     */
    public function updateCustomFields(Envelope $envelope, array $data): Envelope
    {
        DB::beginTransaction();

        try {
            // Clear existing custom fields
            $envelope->customFields()->delete();

            // Add text custom fields
            if (isset($data['textCustomFields'])) {
                foreach ($data['textCustomFields'] as $field) {
                    $customField = new EnvelopeCustomField();
                    $customField->envelope_id = $envelope->id;
                    $customField->name = $field['name'];
                    $customField->value = $field['value'] ?? '';
                    $customField->type = 'text';
                    $customField->required = ($field['required'] ?? 'false') === 'true';
                    $customField->show = ($field['show'] ?? 'true') === 'true';
                    $customField->save();
                }
            }

            // Add list custom fields
            if (isset($data['listCustomFields'])) {
                foreach ($data['listCustomFields'] as $field) {
                    $customField = new EnvelopeCustomField();
                    $customField->envelope_id = $envelope->id;
                    $customField->name = $field['name'];
                    $customField->value = $field['value'] ?? '';
                    $customField->type = 'list';
                    $customField->required = ($field['required'] ?? 'false') === 'true';
                    $customField->show = ($field['show'] ?? 'true') === 'true';
                    $customField->save();
                }
            }

            DB::commit();

            return $envelope->fresh(['customFields']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete envelope custom fields.
     *
     * @param  Envelope  $envelope
     * @return bool
     */
    public function deleteCustomFields(Envelope $envelope): bool
    {
        return $envelope->customFields()->delete() > 0;
    }

    /**
     * Get envelope lock status.
     *
     * @param  Envelope  $envelope
     * @return array|null
     */
    public function getLock(Envelope $envelope): ?array
    {
        $lock = $envelope->lock;

        if (!$lock) {
            return null;
        }

        return [
            'lockToken' => $lock->lock_token,
            'lockDurationInSeconds' => (string) $lock->lock_duration_seconds,
            'lockedByUser' => [
                'userId' => (string) $lock->locked_by_user_id,
                'userName' => $lock->locked_by_user_name,
                'email' => $lock->locked_by_user_email,
            ],
            'lockedUntilDateTime' => $lock->locked_until->toIso8601String(),
            'createdDateTime' => $lock->created_at->toIso8601String(),
        ];
    }

    /**
     * Create envelope lock.
     *
     * @param  Envelope  $envelope
     * @param  User  $user
     * @param  int  $duration  Duration in seconds
     * @return array
     * @throws \Exception
     */
    public function createLock(Envelope $envelope, User $user, int $duration = 300): array
    {
        // Check if envelope is already locked
        if ($envelope->lock && $envelope->lock->locked_until > now()) {
            throw new \Exception('Envelope is already locked by another user');
        }

        // Delete existing lock if any
        if ($envelope->lock) {
            $envelope->lock->delete();
        }

        // Create new lock
        $lock = new \App\Models\EnvelopeLock();
        $lock->envelope_id = $envelope->id;
        $lock->locked_by_user_id = $user->id;
        $lock->locked_by_user_name = $user->first_name . ' ' . $user->last_name;
        $lock->locked_by_user_email = $user->email;
        $lock->lock_duration_seconds = $duration;
        $lock->locked_until = now()->addSeconds($duration);
        $lock->lock_token = 'lock_' . \Illuminate\Support\Str::uuid()->toString();
        $lock->save();

        return $this->getLock($envelope->fresh(['lock']));
    }

    /**
     * Update envelope lock.
     *
     * @param  Envelope  $envelope
     * @param  string  $lockToken
     * @param  int  $duration
     * @return array
     * @throws \Exception
     */
    public function updateLock(Envelope $envelope, string $lockToken, int $duration = 300): array
    {
        $lock = $envelope->lock;

        if (!$lock) {
            throw new \Exception('Envelope is not locked');
        }

        if ($lock->lock_token !== $lockToken) {
            throw new \Exception('Invalid lock token');
        }

        // Extend the lock
        $lock->lock_duration_seconds = $duration;
        $lock->locked_until = now()->addSeconds($duration);
        $lock->save();

        return $this->getLock($envelope->fresh(['lock']));
    }

    /**
     * Delete envelope lock.
     *
     * @param  Envelope  $envelope
     * @param  string|null  $lockToken
     * @return bool
     * @throws \Exception
     */
    public function deleteLock(Envelope $envelope, ?string $lockToken = null): bool
    {
        $lock = $envelope->lock;

        if (!$lock) {
            return true; // Already unlocked
        }

        // If lock token is provided, validate it
        if ($lockToken && $lock->lock_token !== $lockToken) {
            throw new \Exception('Invalid lock token');
        }

        return $lock->delete();
    }
}
