<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Template Model
 *
 * Represents a reusable envelope template with predefined documents,
 * recipients, and tabs that can be used to quickly create envelopes.
 *
 * @property int $id
 * @property int $account_id
 * @property string $template_id
 * @property string $template_name
 * @property string|null $description
 * @property int|null $owner_user_id
 * @property string $shared - private, shared
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Template extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'templates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'account_id',
        'template_id',
        'template_name',
        'description',
        'owner_user_id',
        'shared',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Sharing constants
     */
    public const SHARED_PRIVATE = 'private';
    public const SHARED_SHARED = 'shared';

    /**
     * Boot method to auto-generate template_id
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($template) {
            if (empty($template->template_id)) {
                $template->template_id = 'tpl-' . Str::uuid()->toString();
            }
        });
    }

    /**
     * Get the account that owns the template
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Get the owner user of the template
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * Get the documents associated with the template
     * Note: Templates reuse the envelope_documents table with a template_id column
     */
    public function documents(): HasMany
    {
        return $this->hasMany(EnvelopeDocument::class, 'template_id', 'id');
    }

    /**
     * Get the recipients associated with the template
     * Note: Templates reuse the envelope_recipients table with a template_id column
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(EnvelopeRecipient::class, 'template_id', 'id');
    }

    /**
     * Get the tabs associated with the template
     * Note: Templates reuse the envelope_tabs table with a template_id column
     */
    public function tabs(): HasMany
    {
        return $this->hasMany(EnvelopeTab::class, 'template_id', 'id');
    }

    /**
     * Get the custom fields associated with the template
     * Note: Templates reuse the envelope_custom_fields table with a template_id column
     */
    public function customFields(): HasMany
    {
        return $this->hasMany(EnvelopeCustomField::class, 'template_id', 'id');
    }

    /**
     * Get the users who have favorited this template
     */
    public function favoritedBy(): HasMany
    {
        return $this->hasMany(FavoriteTemplate::class, 'template_id', 'id');
    }

    /**
     * Get the shared access records for this template
     */
    public function sharedAccess(): HasMany
    {
        return $this->hasMany(SharedAccess::class, 'item_id', 'template_id')
            ->where('item_type', 'template');
    }

    /**
     * Check if template is shared
     */
    public function isShared(): bool
    {
        return $this->shared === self::SHARED_SHARED;
    }

    /**
     * Check if template is private
     */
    public function isPrivate(): bool
    {
        return $this->shared === self::SHARED_PRIVATE;
    }

    /**
     * Check if user owns the template
     */
    public function isOwnedBy(int $userId): bool
    {
        return $this->owner_user_id === $userId;
    }

    /**
     * Check if user can access the template
     */
    public function canBeAccessedBy(int $userId): bool
    {
        // Owner can always access
        if ($this->isOwnedBy($userId)) {
            return true;
        }

        // Check if shared with user
        if ($this->isShared()) {
            return $this->sharedAccess()
                ->where('shared_with_user_id', $userId)
                ->exists();
        }

        return false;
    }

    /**
     * Scope: Filter by account
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope: Filter by owner
     */
    public function scopeOwnedBy($query, int $userId)
    {
        return $query->where('owner_user_id', $userId);
    }

    /**
     * Scope: Filter by shared status
     */
    public function scopeShared($query)
    {
        return $query->where('shared', self::SHARED_SHARED);
    }

    /**
     * Scope: Filter by private status
     */
    public function scopePrivate($query)
    {
        return $query->where('shared', self::SHARED_PRIVATE);
    }

    /**
     * Scope: Search by name or description
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('template_name', 'ilike', "%{$term}%")
              ->orWhere('description', 'ilike', "%{$term}%");
        });
    }

    /**
     * Scope: Templates accessible by user (owned or shared with)
     */
    public function scopeAccessibleBy($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('owner_user_id', $userId)
              ->orWhere('shared', self::SHARED_SHARED);
        });
    }

    /**
     * Scope: With all relationships loaded
     */
    public function scopeWithFullDetails($query)
    {
        return $query->with([
            'account',
            'owner',
            'documents',
            'recipients.tabs',
            'customFields',
            'sharedAccess',
        ]);
    }
}
