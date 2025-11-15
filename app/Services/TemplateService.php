<?php

namespace App\Services;

use App\Exceptions\Custom\BusinessLogicException;
use App\Exceptions\Custom\ResourceNotFoundException;
use App\Models\Account;
use App\Models\Envelope;
use App\Models\EnvelopeCustomField;
use App\Models\EnvelopeDocument;
use App\Models\EnvelopeRecipient;
use App\Models\EnvelopeTab;
use App\Models\FavoriteTemplate;
use App\Models\SharedAccess;
use App\Models\Template;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * TemplateService
 *
 * Business logic for template management, including CRUD operations,
 * sharing, and creating envelopes from templates.
 */
class TemplateService
{
    /**
     * Create a new template
     *
     * @param Account $account
     * @param array $data
     * @return Template
     * @throws BusinessLogicException
     */
    public function createTemplate(Account $account, array $data): Template
    {
        DB::beginTransaction();

        try {
            // Create template
            $template = Template::create([
                'account_id' => $account->id,
                'template_name' => $data['template_name'],
                'description' => $data['description'] ?? null,
                'owner_user_id' => $data['owner_user_id'] ?? null,
                'shared' => $data['shared'] ?? Template::SHARED_PRIVATE,
            ]);

            // Add documents if provided
            if (!empty($data['documents'])) {
                $this->addDocuments($template, $data['documents']);
            }

            // Add recipients if provided
            if (!empty($data['recipients'])) {
                $this->addRecipients($template, $data['recipients']);
            }

            // Add tabs if provided
            if (!empty($data['tabs'])) {
                $this->addTabs($template, $data['tabs']);
            }

            // Add custom fields if provided
            if (!empty($data['custom_fields'])) {
                $this->addCustomFields($template, $data['custom_fields']);
            }

            DB::commit();

            // Reload with relationships
            return $template->fresh(['documents', 'recipients.tabs', 'customFields']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Template creation failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to create template: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing template
     *
     * @param Template $template
     * @param array $data
     * @return Template
     * @throws BusinessLogicException
     */
    public function updateTemplate(Template $template, array $data): Template
    {
        DB::beginTransaction();

        try {
            // Update basic template info
            $template->update([
                'template_name' => $data['template_name'] ?? $template->template_name,
                'description' => $data['description'] ?? $template->description,
                'shared' => $data['shared'] ?? $template->shared,
            ]);

            DB::commit();

            return $template->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Template update failed', [
                'template_id' => $template->template_id,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to update template: ' . $e->getMessage());
        }
    }

    /**
     * Delete a template
     *
     * @param Template $template
     * @return bool
     * @throws BusinessLogicException
     */
    public function deleteTemplate(Template $template): bool
    {
        try {
            return $template->delete();
        } catch (\Exception $e) {
            Log::error('Template deletion failed', [
                'template_id' => $template->template_id,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to delete template: ' . $e->getMessage());
        }
    }

    /**
     * Get a specific template with relationships
     *
     * @param Account $account
     * @param string $templateId
     * @return Template
     * @throws ResourceNotFoundException
     */
    public function getTemplate(Account $account, string $templateId): Template
    {
        $template = Template::where('account_id', $account->id)
            ->where('template_id', $templateId)
            ->with(['documents', 'recipients.tabs', 'customFields', 'owner'])
            ->first();

        if (!$template) {
            throw new ResourceNotFoundException('Template not found');
        }

        return $template;
    }

    /**
     * List templates with filters and pagination
     *
     * @param Account $account
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function listTemplates(Account $account, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Template::where('account_id', $account->id);

        // Filter by owner
        if (!empty($filters['owner_user_id'])) {
            $query->where('owner_user_id', $filters['owner_user_id']);
        }

        // Filter by shared status
        if (!empty($filters['shared'])) {
            if ($filters['shared'] === 'true' || $filters['shared'] === true) {
                $query->shared();
            } else {
                $query->private();
            }
        }

        // Filter by accessibility for a specific user
        if (!empty($filters['accessible_by_user_id'])) {
            $query->accessibleBy($filters['accessible_by_user_id']);
        }

        // Search by name or description
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Create an envelope from a template
     *
     * @param Template $template
     * @param array $data - Additional envelope data and recipient overrides
     * @return Envelope
     * @throws BusinessLogicException
     */
    public function createEnvelopeFromTemplate(Template $template, array $data): Envelope
    {
        DB::beginTransaction();

        try {
            // Create envelope
            $envelope = Envelope::create([
                'account_id' => $template->account_id,
                'sender_user_id' => $data['sender_user_id'],
                'status' => Envelope::STATUS_DRAFT,
                'email_subject' => $data['email_subject'] ?? $template->template_name,
                'email_message' => $data['email_message'] ?? null,
            ]);

            // Copy documents from template
            $this->copyDocumentsToEnvelope($template, $envelope);

            // Copy recipients from template (with optional overrides)
            $this->copyRecipientsToEnvelope($template, $envelope, $data['recipients'] ?? []);

            // Copy tabs from template
            $this->copyTabsToEnvelope($template, $envelope);

            // Copy custom fields from template
            $this->copyCustomFieldsToEnvelope($template, $envelope);

            DB::commit();

            return $envelope->fresh(['documents', 'recipients.tabs', 'customFields']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create envelope from template', [
                'template_id' => $template->template_id,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to create envelope from template: ' . $e->getMessage());
        }
    }

    /**
     * Share template with a user
     *
     * @param Template $template
     * @param int $sharedWithUserId
     * @return SharedAccess
     * @throws BusinessLogicException
     */
    public function shareTemplate(Template $template, int $sharedWithUserId): SharedAccess
    {
        try {
            // Check if already shared
            $existing = SharedAccess::where('item_type', SharedAccess::ITEM_TYPE_TEMPLATE)
                ->where('item_id', $template->template_id)
                ->where('shared_with_user_id', $sharedWithUserId)
                ->first();

            if ($existing) {
                return $existing;
            }

            // Create shared access record
            return SharedAccess::create([
                'account_id' => $template->account_id,
                'user_id' => $template->owner_user_id,
                'item_type' => SharedAccess::ITEM_TYPE_TEMPLATE,
                'item_id' => $template->template_id,
                'shared_with_user_id' => $sharedWithUserId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to share template', [
                'template_id' => $template->template_id,
                'shared_with_user_id' => $sharedWithUserId,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to share template: ' . $e->getMessage());
        }
    }

    /**
     * Unshare template from a user
     *
     * @param Template $template
     * @param int $sharedWithUserId
     * @return bool
     * @throws BusinessLogicException
     */
    public function unshareTemplate(Template $template, int $sharedWithUserId): bool
    {
        try {
            return SharedAccess::where('item_type', SharedAccess::ITEM_TYPE_TEMPLATE)
                ->where('item_id', $template->template_id)
                ->where('shared_with_user_id', $sharedWithUserId)
                ->delete() > 0;
        } catch (\Exception $e) {
            Log::error('Failed to unshare template', [
                'template_id' => $template->template_id,
                'shared_with_user_id' => $sharedWithUserId,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to unshare template: ' . $e->getMessage());
        }
    }

    /**
     * Add a template to favorites
     *
     * @param Template $template
     * @param int $userId
     * @return FavoriteTemplate
     * @throws BusinessLogicException
     */
    public function addToFavorites(Template $template, int $userId): FavoriteTemplate
    {
        try {
            // Check if already favorited
            $existing = FavoriteTemplate::where('template_id', $template->id)
                ->where('user_id', $userId)
                ->first();

            if ($existing) {
                return $existing;
            }

            return FavoriteTemplate::create([
                'account_id' => $template->account_id,
                'user_id' => $userId,
                'template_id' => $template->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to add template to favorites', [
                'template_id' => $template->template_id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to add to favorites: ' . $e->getMessage());
        }
    }

    /**
     * Remove a template from favorites
     *
     * @param Template $template
     * @param int $userId
     * @return bool
     * @throws BusinessLogicException
     */
    public function removeFromFavorites(Template $template, int $userId): bool
    {
        try {
            return FavoriteTemplate::where('template_id', $template->id)
                ->where('user_id', $userId)
                ->delete() > 0;
        } catch (\Exception $e) {
            Log::error('Failed to remove template from favorites', [
                'template_id' => $template->template_id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to remove from favorites: ' . $e->getMessage());
        }
    }

    /**
     * Helper: Add documents to template
     */
    protected function addDocuments(Template $template, array $documents): void
    {
        foreach ($documents as $docData) {
            EnvelopeDocument::create([
                'template_id' => $template->id,
                'name' => $docData['name'],
                'document_base64' => $docData['document_base64'] ?? null,
                'file_extension' => $docData['file_extension'] ?? null,
                'order_number' => $docData['order'] ?? 1,
            ]);
        }
    }

    /**
     * Helper: Add recipients to template
     */
    protected function addRecipients(Template $template, array $recipients): void
    {
        foreach ($recipients as $recipientData) {
            EnvelopeRecipient::create([
                'template_id' => $template->id,
                'recipient_type' => $recipientData['recipient_type'],
                'role_name' => $recipientData['role_name'] ?? null,
                'name' => $recipientData['name'],
                'email' => $recipientData['email'],
                'routing_order' => $recipientData['routing_order'] ?? 1,
                'status' => EnvelopeRecipient::STATUS_CREATED,
            ]);
        }
    }

    /**
     * Helper: Add tabs to template
     */
    protected function addTabs(Template $template, array $tabs): void
    {
        foreach ($tabs as $tabData) {
            EnvelopeTab::create([
                'template_id' => $template->id,
                'recipient_id' => $tabData['recipient_id'],
                'document_id' => $tabData['document_id'],
                'type' => $tabData['type'],
                'tab_label' => $tabData['tab_label'],
                'page_number' => $tabData['page_number'] ?? 1,
                'x_position' => $tabData['x_position'] ?? null,
                'y_position' => $tabData['y_position'] ?? null,
                'required' => $tabData['required'] ?? false,
            ]);
        }
    }

    /**
     * Helper: Add custom fields to template
     */
    protected function addCustomFields(Template $template, array $fields): void
    {
        foreach ($fields as $fieldData) {
            EnvelopeCustomField::create([
                'template_id' => $template->id,
                'name' => $fieldData['name'],
                'value' => $fieldData['value'] ?? null,
                'type' => $fieldData['type'] ?? 'text',
                'required' => $fieldData['required'] ?? false,
            ]);
        }
    }

    /**
     * Helper: Copy documents from template to envelope
     */
    protected function copyDocumentsToEnvelope(Template $template, Envelope $envelope): void
    {
        $templateDocs = EnvelopeDocument::where('template_id', $template->id)->get();

        foreach ($templateDocs as $templateDoc) {
            EnvelopeDocument::create([
                'envelope_id' => $envelope->id,
                'name' => $templateDoc->name,
                'document_base64' => $templateDoc->document_base64,
                'file_extension' => $templateDoc->file_extension,
                'order_number' => $templateDoc->order_number,
                'display' => $templateDoc->display,
                'include_in_download' => $templateDoc->include_in_download,
                'signable' => $templateDoc->signable,
            ]);
        }
    }

    /**
     * Helper: Copy recipients from template to envelope (with overrides)
     */
    protected function copyRecipientsToEnvelope(Template $template, Envelope $envelope, array $recipientOverrides = []): void
    {
        $templateRecipients = EnvelopeRecipient::where('template_id', $template->id)->get();

        foreach ($templateRecipients as $templateRecipient) {
            // Find override by role_name if provided
            $override = null;
            if (!empty($recipientOverrides)) {
                foreach ($recipientOverrides as $overrideData) {
                    if (($overrideData['role_name'] ?? null) === $templateRecipient->role_name) {
                        $override = $overrideData;
                        break;
                    }
                }
            }

            EnvelopeRecipient::create([
                'envelope_id' => $envelope->id,
                'recipient_type' => $templateRecipient->recipient_type,
                'role_name' => $templateRecipient->role_name,
                'name' => $override['name'] ?? $templateRecipient->name,
                'email' => $override['email'] ?? $templateRecipient->email,
                'routing_order' => $templateRecipient->routing_order,
                'status' => EnvelopeRecipient::STATUS_CREATED,
            ]);
        }
    }

    /**
     * Helper: Copy tabs from template to envelope
     */
    protected function copyTabsToEnvelope(Template $template, Envelope $envelope): void
    {
        $templateTabs = EnvelopeTab::where('template_id', $template->id)->get();

        foreach ($templateTabs as $templateTab) {
            EnvelopeTab::create([
                'envelope_id' => $envelope->id,
                'recipient_id' => $templateTab->recipient_id,
                'document_id' => $templateTab->document_id,
                'type' => $templateTab->type,
                'tab_label' => $templateTab->tab_label,
                'page_number' => $templateTab->page_number,
                'x_position' => $templateTab->x_position,
                'y_position' => $templateTab->y_position,
                'width' => $templateTab->width,
                'height' => $templateTab->height,
                'required' => $templateTab->required,
                'locked' => $templateTab->locked,
                'anchor_string' => $templateTab->anchor_string,
            ]);
        }
    }

    /**
     * Helper: Copy custom fields from template to envelope
     */
    protected function copyCustomFieldsToEnvelope(Template $template, Envelope $envelope): void
    {
        $templateFields = EnvelopeCustomField::where('template_id', $template->id)->get();

        foreach ($templateFields as $templateField) {
            EnvelopeCustomField::create([
                'envelope_id' => $envelope->id,
                'name' => $templateField->name,
                'value' => $templateField->value,
                'type' => $templateField->type,
                'required' => $templateField->required,
            ]);
        }
    }
}
