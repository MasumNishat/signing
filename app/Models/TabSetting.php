<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TabSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'text_tabs_enabled',
        'radio_tabs_enabled',
        'checkbox_tabs_enabled',
        'list_tabs_enabled',
        'approve_decline_tabs_enabled',
        'note_tabs_enabled',
        'data_field_regex_enabled',
        'data_field_size_enabled',
        'tab_location_enabled',
        'tab_scale_enabled',
        'tab_locking_enabled',
        'saving_custom_tabs_enabled',
        'tab_text_formatting_enabled',
        'shared_custom_tabs_enabled',
        'sender_to_change_tab_assignments_enabled',
    ];

    protected $casts = [
        'text_tabs_enabled' => 'boolean',
        'radio_tabs_enabled' => 'boolean',
        'checkbox_tabs_enabled' => 'boolean',
        'list_tabs_enabled' => 'boolean',
        'approve_decline_tabs_enabled' => 'boolean',
        'note_tabs_enabled' => 'boolean',
        'data_field_regex_enabled' => 'boolean',
        'data_field_size_enabled' => 'boolean',
        'tab_location_enabled' => 'boolean',
        'tab_scale_enabled' => 'boolean',
        'tab_locking_enabled' => 'boolean',
        'saving_custom_tabs_enabled' => 'boolean',
        'tab_text_formatting_enabled' => 'boolean',
        'shared_custom_tabs_enabled' => 'boolean',
        'sender_to_change_tab_assignments_enabled' => 'boolean',
    ];

    /**
     * Get the account that owns the tab settings.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Check if a specific tab type is enabled.
     */
    public function isTabTypeEnabled(string $tabType): bool
    {
        return match ($tabType) {
            'text' => $this->text_tabs_enabled,
            'radio' => $this->radio_tabs_enabled,
            'checkbox' => $this->checkbox_tabs_enabled,
            'list' => $this->list_tabs_enabled,
            'approve_decline' => $this->approve_decline_tabs_enabled,
            'note' => $this->note_tabs_enabled,
            default => false,
        };
    }

    /**
     * Check if custom tabs can be saved.
     */
    public function canSaveCustomTabs(): bool
    {
        return $this->saving_custom_tabs_enabled;
    }

    /**
     * Check if custom tabs can be shared.
     */
    public function canShareCustomTabs(): bool
    {
        return $this->shared_custom_tabs_enabled;
    }

    /**
     * Check if sender can change tab assignments.
     */
    public function canSenderChangeTabAssignments(): bool
    {
        return $this->sender_to_change_tab_assignments_enabled;
    }

    /**
     * Check if tab locking is enabled.
     */
    public function isTabLockingEnabled(): bool
    {
        return $this->tab_locking_enabled;
    }

    /**
     * Get all enabled tab types.
     */
    public function getEnabledTabTypes(): array
    {
        $types = [];

        if ($this->text_tabs_enabled) $types[] = 'text';
        if ($this->radio_tabs_enabled) $types[] = 'radio';
        if ($this->checkbox_tabs_enabled) $types[] = 'checkbox';
        if ($this->list_tabs_enabled) $types[] = 'list';
        if ($this->approve_decline_tabs_enabled) $types[] = 'approve_decline';
        if ($this->note_tabs_enabled) $types[] = 'note';

        return $types;
    }
}
