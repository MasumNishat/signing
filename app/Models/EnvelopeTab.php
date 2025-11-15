<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EnvelopeTab extends Model
{
    use HasFactory;

    protected $table = 'envelope_tabs';

    // Tab Types (27 types from DocuSign API)
    public const TYPE_APPROVE = 'approve';
    public const TYPE_CHECKBOX = 'checkbox';
    public const TYPE_COMPANY = 'company';
    public const TYPE_DATE_SIGNED = 'date_signed';
    public const TYPE_DATE = 'date';
    public const TYPE_DECLINE = 'decline';
    public const TYPE_EMAIL_ADDRESS = 'email_address';
    public const TYPE_EMAIL = 'email';
    public const TYPE_ENVELOPE_ID = 'envelope_id';
    public const TYPE_FIRST_NAME = 'first_name';
    public const TYPE_FORMULA = 'formula';
    public const TYPE_FULL_NAME = 'full_name';
    public const TYPE_INITIAL_HERE = 'initial_here';
    public const TYPE_LAST_NAME = 'last_name';
    public const TYPE_LIST = 'list';
    public const TYPE_NOTARIZE = 'notarize';
    public const TYPE_NOTE = 'note';
    public const TYPE_NUMBER = 'number';
    public const TYPE_RADIO_GROUP = 'radio_group';
    public const TYPE_SIGN_HERE = 'sign_here';
    public const TYPE_SIGNER_ATTACHMENT = 'signer_attachment';
    public const TYPE_SMART_SECTION = 'smart_section';
    public const TYPE_SSN = 'ssn';
    public const TYPE_TEXT = 'text';
    public const TYPE_TITLE = 'title';
    public const TYPE_VIEW = 'view';
    public const TYPE_ZIP = 'zip';

    // Tab status
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_DECLINED = 'declined';

    protected $fillable = [
        'envelope_id', 'template_id', 'document_id', 'recipient_id', 'tab_id', 'type',
        'tab_label', 'value', 'required', 'locked', 'page_number',
        'x_position', 'y_position', 'width', 'height',
        'anchor_string', 'anchor_x_offset', 'anchor_y_offset',
        'anchor_units', 'anchor_ignore_if_not_present',
        'conditional_parent_label', 'conditional_parent_value',
        'font', 'font_size', 'font_color', 'bold', 'italic', 'underline',
        'list_items', 'validation_pattern', 'validation_message',
        'tooltip', 'status', 'tab_group_label', 'scale_value',
    ];

    protected $casts = [
        'required' => 'boolean',
        'locked' => 'boolean',
        'page_number' => 'integer',
        'x_position' => 'integer',
        'y_position' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'anchor_x_offset' => 'integer',
        'anchor_y_offset' => 'integer',
        'anchor_ignore_if_not_present' => 'boolean',
        'bold' => 'boolean',
        'italic' => 'boolean',
        'underline' => 'boolean',
        'list_items' => 'array',
        'scale_value' => 'decimal:2',
    ];

    /**
     * Boot method to auto-generate tab_id
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tab) {
            if (empty($tab->tab_id)) {
                $tab->tab_id = (string) Str::uuid();
            }
        });
    }

    /**
     * Relationships
     */
    public function envelope(): BelongsTo
    {
        return $this->belongsTo(Envelope::class, 'envelope_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class, 'template_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(EnvelopeDocument::class, 'document_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(EnvelopeRecipient::class, 'recipient_id');
    }

    /**
     * Helper Methods
     */

    /**
     * Check if tab is a signature-related tab
     */
    public function isSignatureTab(): bool
    {
        return in_array($this->type, [
            self::TYPE_SIGN_HERE,
            self::TYPE_INITIAL_HERE,
            self::TYPE_DATE_SIGNED,
        ]);
    }

    /**
     * Check if tab is an input tab (requires user input)
     */
    public function isInputTab(): bool
    {
        return in_array($this->type, [
            self::TYPE_TEXT,
            self::TYPE_DATE,
            self::TYPE_NUMBER,
            self::TYPE_EMAIL,
            self::TYPE_SSN,
            self::TYPE_ZIP,
            self::TYPE_CHECKBOX,
            self::TYPE_LIST,
            self::TYPE_RADIO_GROUP,
        ]);
    }

    /**
     * Check if tab is auto-filled
     */
    public function isAutoFilledTab(): bool
    {
        return in_array($this->type, [
            self::TYPE_FULL_NAME,
            self::TYPE_FIRST_NAME,
            self::TYPE_LAST_NAME,
            self::TYPE_EMAIL_ADDRESS,
            self::TYPE_COMPANY,
            self::TYPE_TITLE,
            self::TYPE_DATE_SIGNED,
            self::TYPE_ENVELOPE_ID,
        ]);
    }

    /**
     * Check if tab is action-based (approve/decline)
     */
    public function isActionTab(): bool
    {
        return in_array($this->type, [
            self::TYPE_APPROVE,
            self::TYPE_DECLINE,
        ]);
    }

    /**
     * Check if tab uses anchor positioning
     */
    public function usesAnchor(): bool
    {
        return !empty($this->anchor_string);
    }

    /**
     * Check if tab is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Mark tab as completed
     */
    public function markAsCompleted(string $value = null): void
    {
        $this->status = self::STATUS_COMPLETED;
        if ($value !== null) {
            $this->value = $value;
        }
        $this->save();
    }

    /**
     * Get all supported tab types
     */
    public static function getSupportedTypes(): array
    {
        return [
            self::TYPE_APPROVE,
            self::TYPE_CHECKBOX,
            self::TYPE_COMPANY,
            self::TYPE_DATE_SIGNED,
            self::TYPE_DATE,
            self::TYPE_DECLINE,
            self::TYPE_EMAIL_ADDRESS,
            self::TYPE_EMAIL,
            self::TYPE_ENVELOPE_ID,
            self::TYPE_FIRST_NAME,
            self::TYPE_FORMULA,
            self::TYPE_FULL_NAME,
            self::TYPE_INITIAL_HERE,
            self::TYPE_LAST_NAME,
            self::TYPE_LIST,
            self::TYPE_NOTARIZE,
            self::TYPE_NOTE,
            self::TYPE_NUMBER,
            self::TYPE_RADIO_GROUP,
            self::TYPE_SIGN_HERE,
            self::TYPE_SIGNER_ATTACHMENT,
            self::TYPE_SMART_SECTION,
            self::TYPE_SSN,
            self::TYPE_TEXT,
            self::TYPE_TITLE,
            self::TYPE_VIEW,
            self::TYPE_ZIP,
        ];
    }

    /**
     * Query Scopes
     */

    /**
     * Filter tabs by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Filter signature tabs only
     */
    public function scopeSignatureTabs($query)
    {
        return $query->whereIn('type', [
            self::TYPE_SIGN_HERE,
            self::TYPE_INITIAL_HERE,
            self::TYPE_DATE_SIGNED,
        ]);
    }

    /**
     * Filter required tabs only
     */
    public function scopeRequired($query)
    {
        return $query->where('required', true);
    }

    /**
     * Filter tabs by status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Filter completed tabs
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Filter tabs by page number
     */
    public function scopeOnPage($query, int $pageNumber)
    {
        return $query->where('page_number', $pageNumber);
    }

    /**
     * Order tabs by position (page, then y position, then x position)
     */
    public function scopeOrderedByPosition($query)
    {
        return $query->orderBy('page_number')
            ->orderBy('y_position')
            ->orderBy('x_position');
    }
}
