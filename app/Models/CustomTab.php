<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * CustomTab Model
 *
 * Represents reusable field templates at the organization level.
 * Custom tabs allow accounts to create standardized form fields
 * that can be reused across multiple envelopes and templates.
 *
 * Examples: Employee ID, Department, Manager Name, Project Code
 *
 * @property int $id
 * @property int $account_id
 * @property string $custom_tab_id UUID identifier for API
 * @property string $name Template name
 * @property string $type Tab type (text, checkbox, date, etc.)
 * @property string|null $label Field label
 * @property bool $required Whether field is required
 * @property string|null $value Default value
 * @property string|null $font Font name
 * @property int|null $font_size Font size in pixels
 * @property string|null $font_color Hex color code
 * @property bool $bold Bold formatting
 * @property bool $italic Italic formatting
 * @property bool $underline Underline formatting
 * @property int|null $width Width in pixels
 * @property int|null $height Height in pixels
 * @property string|null $validation_type Validation type (email, phone, etc.)
 * @property string|null $validation_pattern Regex validation pattern
 * @property string|null $validation_message Custom validation error message
 * @property string|null $tooltip Help text tooltip
 * @property array|null $list_items Items for list/dropdown types
 * @property bool $shared Shared across entire account
 * @property int|null $created_by User ID who created this template
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @property-read Account $account
 * @property-read User|null $creator
 */
class CustomTab extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'account_id',
        'custom_tab_id',
        'name',
        'type',
        'label',
        'required',
        'value',
        'font',
        'font_size',
        'font_color',
        'bold',
        'italic',
        'underline',
        'width',
        'height',
        'validation_type',
        'validation_pattern',
        'validation_message',
        'tooltip',
        'list_items',
        'shared',
        'created_by',
    ];

    protected $casts = [
        'account_id' => 'integer',
        'required' => 'boolean',
        'bold' => 'boolean',
        'italic' => 'boolean',
        'underline' => 'boolean',
        'font_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'shared' => 'boolean',
        'list_items' => 'array',
        'created_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Valid tab types
     */
    public const TAB_TYPES = [
        'text', 'checkbox', 'date_signed', 'date', 'email', 'number',
        'ssn', 'zip', 'phone', 'list', 'radio_group', 'dropdown',
        'text_area', 'url', 'company', 'title', 'full_name',
        'first_name', 'last_name', 'initial_here', 'note',
    ];

    /**
     * Boot method - auto-generate custom_tab_id
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customTab) {
            if (empty($customTab->custom_tab_id)) {
                $customTab->custom_tab_id = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the account that owns this custom tab
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user who created this custom tab
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if this is a valid tab type
     */
    public function isValidType(): bool
    {
        return in_array($this->type, self::TAB_TYPES);
    }

    /**
     * Check if this tab type supports list items
     */
    public function supportsListItems(): bool
    {
        return in_array($this->type, ['list', 'radio_group', 'dropdown']);
    }

    /**
     * Check if this tab is shared across the account
     */
    public function isShared(): bool
    {
        return $this->shared === true;
    }

    /**
     * Scope: Filter by account
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope: Filter by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Only shared tabs
     */
    public function scopeShared($query)
    {
        return $query->where('shared', true);
    }

    /**
     * Scope: Only personal (non-shared) tabs
     */
    public function scopePersonal($query, int $userId)
    {
        return $query->where('shared', false)->where('created_by', $userId);
    }

    /**
     * Scope: Search by name
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where('name', 'like', "%{$search}%");
    }
}
