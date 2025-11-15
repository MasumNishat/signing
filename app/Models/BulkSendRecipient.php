<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BulkSendRecipient Model
 *
 * Represents a recipient in a bulk send list.
 * Contains recipient information and custom field values.
 *
 * @property int $id
 * @property int $list_id
 * @property string|null $recipient_name
 * @property string|null $recipient_email
 * @property array|null $custom_fields
 * @property \Carbon\Carbon $created_at
 */
class BulkSendRecipient extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bulk_send_recipients';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'list_id',
        'recipient_name',
        'recipient_email',
        'custom_fields',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'custom_fields' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the bulk send list this recipient belongs to
     */
    public function bulkSendList(): BelongsTo
    {
        return $this->belongsTo(BulkSendList::class, 'list_id');
    }

    /**
     * Get a custom field value by key
     */
    public function getCustomField(string $key): mixed
    {
        return $this->custom_fields[$key] ?? null;
    }

    /**
     * Set a custom field value
     */
    public function setCustomField(string $key, mixed $value): void
    {
        $fields = $this->custom_fields ?? [];
        $fields[$key] = $value;
        $this->custom_fields = $fields;
    }

    /**
     * Check if recipient has valid email
     */
    public function hasValidEmail(): bool
    {
        return !empty($this->recipient_email) && filter_var($this->recipient_email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Scope: Filter by list
     */
    public function scopeForList($query, int $listId)
    {
        return $query->where('list_id', $listId);
    }

    /**
     * Scope: With valid emails only
     */
    public function scopeWithValidEmail($query)
    {
        return $query->whereNotNull('recipient_email')
            ->where('recipient_email', '!=', '');
    }
}
