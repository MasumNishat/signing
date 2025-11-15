<?php

namespace App\Services;

use App\Models\Envelope;
use App\Models\EnvelopeDocument;
use App\Models\EnvelopeRecipient;
use App\Models\EnvelopeTab;
use App\Exceptions\Custom\BusinessLogicException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Tab Service
 *
 * Handles all business logic for envelope tab operations.
 * Manages tab CRUD, positioning, anchoring, and validation.
 */
class TabService
{
    /**
     * List all tabs for a recipient
     *
     * @param EnvelopeRecipient $recipient
     * @param array $options Filter options
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function listTabs(EnvelopeRecipient $recipient, array $options = [])
    {
        $query = $recipient->tabs();

        // Filter by document
        if (isset($options['document_id'])) {
            $query->where('document_id', $options['document_id']);
        }

        // Filter by type
        if (isset($options['type'])) {
            $query->where('type', $options['type']);
        }

        // Filter by page number
        if (isset($options['page_number'])) {
            $query->where('page_number', $options['page_number']);
        }

        // Filter by status
        if (isset($options['status'])) {
            $query->where('status', $options['status']);
        }

        // Filter required tabs only
        if (isset($options['required_only']) && $options['required_only']) {
            $query->where('required', true);
        }

        // Default sort by position
        $query->orderedByPosition();

        return $query->get();
    }

    /**
     * Add tabs to a recipient
     *
     * @param EnvelopeRecipient $recipient
     * @param array $tabs Array of tab data
     * @return array Created tabs
     * @throws BusinessLogicException
     */
    public function addTabs(EnvelopeRecipient $recipient, array $tabs): array
    {
        // Validate envelope is in draft or sent status
        if (!$recipient->envelope->isDraft() && !$recipient->envelope->isSent()) {
            throw new BusinessLogicException('Tabs can only be added to draft or sent envelopes');
        }

        // Validate recipient hasn't signed
        if ($recipient->hasSigned()) {
            throw new BusinessLogicException('Cannot add tabs to recipient who has already signed');
        }

        $createdTabs = [];

        DB::beginTransaction();

        try {
            foreach ($tabs as $tabData) {
                $tab = $this->addTab($recipient, $tabData);
                $createdTabs[] = $tab;
            }

            DB::commit();

            return $createdTabs;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to add tabs to recipient', [
                'recipient_id' => $recipient->recipient_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Add a single tab to a recipient
     *
     * @param EnvelopeRecipient $recipient
     * @param array $data Tab data
     * @return EnvelopeTab
     */
    protected function addTab(EnvelopeRecipient $recipient, array $data): EnvelopeTab
    {
        // Validate tab type
        if (!in_array($data['type'], EnvelopeTab::getSupportedTypes())) {
            throw new BusinessLogicException('Invalid tab type: ' . $data['type']);
        }

        // Validate positioning: either absolute position OR anchor string required
        if (empty($data['anchor_string'])) {
            if (!isset($data['page_number']) || !isset($data['x_position']) || !isset($data['y_position'])) {
                throw new BusinessLogicException('Tab requires either anchor_string or absolute positioning (page_number, x_position, y_position)');
            }
        }

        // Get document
        $document = null;
        if (isset($data['document_id'])) {
            $document = $recipient->envelope->documents()
                ->where('document_id', $data['document_id'])
                ->first();

            if (!$document) {
                throw new BusinessLogicException('Document not found in envelope');
            }
        }

        // Create tab
        $tab = $recipient->tabs()->create([
            'envelope_id' => $recipient->envelope_id,
            'document_id' => $document?->id,
            'tab_id' => $data['tab_id'] ?? null, // Auto-generated if null
            'type' => $data['type'],
            'tab_label' => $data['tab_label'] ?? null,
            'value' => $data['value'] ?? null,
            'required' => $data['required'] ?? false,
            'locked' => $data['locked'] ?? false,

            // Absolute positioning
            'page_number' => $data['page_number'] ?? null,
            'x_position' => $data['x_position'] ?? null,
            'y_position' => $data['y_position'] ?? null,
            'width' => $data['width'] ?? $this->getDefaultWidth($data['type']),
            'height' => $data['height'] ?? $this->getDefaultHeight($data['type']),

            // Anchor positioning
            'anchor_string' => $data['anchor_string'] ?? null,
            'anchor_x_offset' => $data['anchor_x_offset'] ?? 0,
            'anchor_y_offset' => $data['anchor_y_offset'] ?? 0,
            'anchor_units' => $data['anchor_units'] ?? 'pixels',
            'anchor_ignore_if_not_present' => $data['anchor_ignore_if_not_present'] ?? false,

            // Conditional logic
            'conditional_parent_label' => $data['conditional_parent_label'] ?? null,
            'conditional_parent_value' => $data['conditional_parent_value'] ?? null,

            // Formatting
            'font' => $data['font'] ?? 'helvetica',
            'font_size' => $data['font_size'] ?? 12,
            'font_color' => $data['font_color'] ?? 'black',
            'bold' => $data['bold'] ?? false,
            'italic' => $data['italic'] ?? false,
            'underline' => $data['underline'] ?? false,

            // List items (for dropdown lists)
            'list_items' => $data['list_items'] ?? null,

            // Validation
            'validation_pattern' => $data['validation_pattern'] ?? null,
            'validation_message' => $data['validation_message'] ?? null,

            // Additional properties
            'tooltip' => $data['tooltip'] ?? null,
            'status' => EnvelopeTab::STATUS_ACTIVE,
            'tab_group_label' => $data['tab_group_label'] ?? null,
            'scale_value' => $data['scale_value'] ?? null,
        ]);

        return $tab;
    }

    /**
     * Get a specific tab
     *
     * @param EnvelopeRecipient $recipient
     * @param string $tabId
     * @return EnvelopeTab
     * @throws BusinessLogicException
     */
    public function getTab(EnvelopeRecipient $recipient, string $tabId): EnvelopeTab
    {
        $tab = $recipient->tabs()
            ->where('tab_id', $tabId)
            ->first();

        if (!$tab) {
            throw new BusinessLogicException('Tab not found');
        }

        return $tab;
    }

    /**
     * Update a tab
     *
     * @param EnvelopeTab $tab
     * @param array $data Update data
     * @return EnvelopeTab
     * @throws BusinessLogicException
     */
    public function updateTab(EnvelopeTab $tab, array $data): EnvelopeTab
    {
        // Validate tab hasn't been completed
        if ($tab->isCompleted()) {
            throw new BusinessLogicException('Cannot update completed tab');
        }

        // Validate recipient hasn't signed
        if ($tab->recipient->hasSigned()) {
            throw new BusinessLogicException('Cannot update tab for recipient who has already signed');
        }

        DB::beginTransaction();

        try {
            // Update allowed fields
            if (isset($data['tab_label'])) {
                $tab->tab_label = $data['tab_label'];
            }

            if (isset($data['value'])) {
                $tab->value = $data['value'];
            }

            if (isset($data['required'])) {
                $tab->required = $data['required'];
            }

            if (isset($data['locked'])) {
                $tab->locked = $data['locked'];
            }

            // Update positioning
            if (isset($data['page_number'])) {
                $tab->page_number = $data['page_number'];
            }

            if (isset($data['x_position'])) {
                $tab->x_position = $data['x_position'];
            }

            if (isset($data['y_position'])) {
                $tab->y_position = $data['y_position'];
            }

            if (isset($data['width'])) {
                $tab->width = $data['width'];
            }

            if (isset($data['height'])) {
                $tab->height = $data['height'];
            }

            // Update anchor positioning
            if (isset($data['anchor_string'])) {
                $tab->anchor_string = $data['anchor_string'];
            }

            if (isset($data['anchor_x_offset'])) {
                $tab->anchor_x_offset = $data['anchor_x_offset'];
            }

            if (isset($data['anchor_y_offset'])) {
                $tab->anchor_y_offset = $data['anchor_y_offset'];
            }

            // Update formatting
            if (isset($data['font'])) {
                $tab->font = $data['font'];
            }

            if (isset($data['font_size'])) {
                $tab->font_size = $data['font_size'];
            }

            if (isset($data['font_color'])) {
                $tab->font_color = $data['font_color'];
            }

            if (isset($data['bold'])) {
                $tab->bold = $data['bold'];
            }

            if (isset($data['italic'])) {
                $tab->italic = $data['italic'];
            }

            if (isset($data['underline'])) {
                $tab->underline = $data['underline'];
            }

            // Update validation
            if (isset($data['validation_pattern'])) {
                $tab->validation_pattern = $data['validation_pattern'];
            }

            if (isset($data['validation_message'])) {
                $tab->validation_message = $data['validation_message'];
            }

            // Update tooltip
            if (isset($data['tooltip'])) {
                $tab->tooltip = $data['tooltip'];
            }

            $tab->save();

            DB::commit();

            return $tab->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a tab
     *
     * @param EnvelopeTab $tab
     * @return bool
     * @throws BusinessLogicException
     */
    public function deleteTab(EnvelopeTab $tab): bool
    {
        // Validate tab hasn't been completed
        if ($tab->isCompleted()) {
            throw new BusinessLogicException('Cannot delete completed tab');
        }

        // Validate recipient hasn't signed
        if ($tab->recipient->hasSigned()) {
            throw new BusinessLogicException('Cannot delete tab for recipient who has already signed');
        }

        DB::beginTransaction();

        try {
            $tab->delete();

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete tab', [
                'tab_id' => $tab->tab_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update tab value (for signing process)
     *
     * @param EnvelopeTab $tab
     * @param string $value
     * @return EnvelopeTab
     * @throws BusinessLogicException
     */
    public function updateTabValue(EnvelopeTab $tab, string $value): EnvelopeTab
    {
        // Validate tab is not locked
        if ($tab->locked) {
            throw new BusinessLogicException('Cannot update locked tab');
        }

        DB::beginTransaction();

        try {
            $tab->value = $value;
            $tab->status = EnvelopeTab::STATUS_COMPLETED;
            $tab->save();

            DB::commit();

            return $tab->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get tab metadata for API response
     *
     * @param EnvelopeTab $tab
     * @return array
     */
    public function getMetadata(EnvelopeTab $tab): array
    {
        $metadata = [
            'tab_id' => $tab->tab_id,
            'type' => $tab->type,
            'tab_label' => $tab->tab_label,
            'value' => $tab->value,
            'required' => $tab->required,
            'locked' => $tab->locked,
            'status' => $tab->status,
        ];

        // Add positioning if using absolute coordinates
        if ($tab->page_number !== null) {
            $metadata['position'] = [
                'page_number' => $tab->page_number,
                'x_position' => $tab->x_position,
                'y_position' => $tab->y_position,
                'width' => $tab->width,
                'height' => $tab->height,
            ];
        }

        // Add anchor positioning if used
        if ($tab->anchor_string !== null) {
            $metadata['anchor'] = [
                'anchor_string' => $tab->anchor_string,
                'anchor_x_offset' => $tab->anchor_x_offset,
                'anchor_y_offset' => $tab->anchor_y_offset,
                'anchor_units' => $tab->anchor_units,
                'anchor_ignore_if_not_present' => $tab->anchor_ignore_if_not_present,
            ];
        }

        // Add conditional logic if present
        if ($tab->conditional_parent_label !== null) {
            $metadata['conditional'] = [
                'parent_label' => $tab->conditional_parent_label,
                'parent_value' => $tab->conditional_parent_value,
            ];
        }

        // Add formatting
        $metadata['formatting'] = [
            'font' => $tab->font,
            'font_size' => $tab->font_size,
            'font_color' => $tab->font_color,
            'bold' => $tab->bold,
            'italic' => $tab->italic,
            'underline' => $tab->underline,
        ];

        // Add list items for dropdown
        if ($tab->list_items !== null) {
            $metadata['list_items'] = $tab->list_items;
        }

        // Add validation
        if ($tab->validation_pattern !== null) {
            $metadata['validation'] = [
                'pattern' => $tab->validation_pattern,
                'message' => $tab->validation_message,
            ];
        }

        // Add additional properties
        if ($tab->tooltip !== null) {
            $metadata['tooltip'] = $tab->tooltip;
        }

        if ($tab->tab_group_label !== null) {
            $metadata['tab_group_label'] = $tab->tab_group_label;
        }

        if ($tab->document_id !== null) {
            $metadata['document_id'] = $tab->document->document_id;
        }

        $metadata['created_at'] = $tab->created_at->toIso8601String();
        $metadata['updated_at'] = $tab->updated_at->toIso8601String();

        return $metadata;
    }

    /**
     * Get default width for tab type
     *
     * @param string $type
     * @return int
     */
    protected function getDefaultWidth(string $type): int
    {
        return match ($type) {
            EnvelopeTab::TYPE_SIGN_HERE => 100,
            EnvelopeTab::TYPE_INITIAL_HERE => 50,
            EnvelopeTab::TYPE_DATE_SIGNED, EnvelopeTab::TYPE_DATE => 100,
            EnvelopeTab::TYPE_TEXT, EnvelopeTab::TYPE_EMAIL => 200,
            EnvelopeTab::TYPE_CHECKBOX => 20,
            EnvelopeTab::TYPE_RADIO_GROUP => 20,
            EnvelopeTab::TYPE_NUMBER => 100,
            EnvelopeTab::TYPE_SSN => 120,
            EnvelopeTab::TYPE_ZIP => 80,
            default => 150,
        };
    }

    /**
     * Get default height for tab type
     *
     * @param string $type
     * @return int
     */
    protected function getDefaultHeight(string $type): int
    {
        return match ($type) {
            EnvelopeTab::TYPE_SIGN_HERE => 40,
            EnvelopeTab::TYPE_INITIAL_HERE => 30,
            EnvelopeTab::TYPE_CHECKBOX => 20,
            EnvelopeTab::TYPE_RADIO_GROUP => 20,
            default => 30,
        };
    }

    /**
     * Validate all required tabs are completed
     *
     * @param EnvelopeRecipient $recipient
     * @return bool
     */
    public function validateRequiredTabsCompleted(EnvelopeRecipient $recipient): bool
    {
        $requiredTabs = $recipient->tabs()->required()->get();

        foreach ($requiredTabs as $tab) {
            if (!$tab->isCompleted() || empty($tab->value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get incomplete required tabs for recipient
     *
     * @param EnvelopeRecipient $recipient
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getIncompleteRequiredTabs(EnvelopeRecipient $recipient)
    {
        return $recipient->tabs()
            ->required()
            ->where(function ($query) {
                $query->where('status', '!=', EnvelopeTab::STATUS_COMPLETED)
                    ->orWhereNull('value');
            })
            ->get();
    }
}
