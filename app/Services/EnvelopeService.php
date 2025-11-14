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
}
