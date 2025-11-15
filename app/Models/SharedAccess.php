<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SharedAccess Model
 *
 * Represents shared access to envelopes and templates with other users or groups
 *
 * @property int $id
 * @property int $account_id
 * @property int $user_id
 * @property string $item_type - envelope, template
 * @property string $item_id
 * @property int|null $shared_with_user_id
 * @property int|null $shared_with_group_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class SharedAccess extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'shared_access';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'account_id',
        'user_id',
        'item_type',
        'item_id',
        'shared_with_user_id',
        'shared_with_group_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Item type constants
     */
    public const ITEM_TYPE_ENVELOPE = 'envelope';
    public const ITEM_TYPE_TEMPLATE = 'template';

    /**
     * Get the account that owns the shared access
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Get the user who created the share
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the user with whom the item is shared
     */
    public function sharedWithUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_with_user_id');
    }

    /**
     * Get the item (envelope or template) polymorphically
     * Note: This requires the item to exist in the appropriate table
     */
    public function item()
    {
        if ($this->item_type === self::ITEM_TYPE_ENVELOPE) {
            return $this->belongsTo(Envelope::class, 'item_id', 'envelope_id');
        } elseif ($this->item_type === self::ITEM_TYPE_TEMPLATE) {
            return $this->belongsTo(Template::class, 'item_id', 'template_id');
        }

        return null;
    }

    /**
     * Check if shared with a user
     */
    public function isSharedWithUser(): bool
    {
        return $this->shared_with_user_id !== null;
    }

    /**
     * Check if shared with a group
     */
    public function isSharedWithGroup(): bool
    {
        return $this->shared_with_group_id !== null;
    }

    /**
     * Check if item is an envelope
     */
    public function isEnvelope(): bool
    {
        return $this->item_type === self::ITEM_TYPE_ENVELOPE;
    }

    /**
     * Check if item is a template
     */
    public function isTemplate(): bool
    {
        return $this->item_type === self::ITEM_TYPE_TEMPLATE;
    }

    /**
     * Scope: Filter by account
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope: Filter by item type
     */
    public function scopeForItemType($query, string $itemType)
    {
        return $query->where('item_type', $itemType);
    }

    /**
     * Scope: Filter by shared with user
     */
    public function scopeSharedWithUser($query, int $userId)
    {
        return $query->where('shared_with_user_id', $userId);
    }

    /**
     * Scope: Filter by templates
     */
    public function scopeTemplates($query)
    {
        return $query->where('item_type', self::ITEM_TYPE_TEMPLATE);
    }

    /**
     * Scope: Filter by envelopes
     */
    public function scopeEnvelopes($query)
    {
        return $query->where('item_type', self::ITEM_TYPE_ENVELOPE);
    }
}
