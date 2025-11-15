<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * PowerForm Model
 *
 * Represents a public-facing form that allows envelope creation without login.
 * PowerForms are based on templates and can be embedded on websites or shared via links.
 *
 * @property int $id
 * @property int $account_id
 * @property string $powerform_id
 * @property int $template_id
 * @property string $name
 * @property string|null $description
 * @property string $status
 * @property bool $is_active
 * @property string|null $email_subject
 * @property string|null $email_message
 * @property bool $send_email_to_sender
 * @property string|null $sender_email
 * @property string|null $sender_name
 * @property int|null $max_uses
 * @property int $times_used
 * @property \Carbon\Carbon|null $expiration_date
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read Account $account
 * @property-read Template $template
 * @property-read \Illuminate\Database\Eloquent\Collection|PowerFormSubmission[] $submissions
 */
class PowerForm extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'powerforms';

    /**
     * Status constants
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_DISABLED = 'disabled';
    public const STATUS_EXPIRED = 'expired';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'account_id',
        'powerform_id',
        'template_id',
        'name',
        'description',
        'status',
        'is_active',
        'email_subject',
        'email_message',
        'send_email_to_sender',
        'sender_email',
        'sender_name',
        'max_uses',
        'times_used',
        'expiration_date',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'send_email_to_sender' => 'boolean',
        'max_uses' => 'integer',
        'times_used' => 'integer',
        'expiration_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($powerform) {
            if (empty($powerform->powerform_id)) {
                $powerform->powerform_id = 'pf-' . Str::uuid()->toString();
            }

            if ($powerform->is_active === null) {
                $powerform->is_active = true;
            }

            if ($powerform->times_used === null) {
                $powerform->times_used = 0;
            }

            if ($powerform->status === null) {
                $powerform->status = self::STATUS_ACTIVE;
            }
        });
    }

    /**
     * Get the account that owns the PowerForm
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Get the template for this PowerForm
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class, 'template_id');
    }

    /**
     * Get all submissions for this PowerForm
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(PowerFormSubmission::class, 'powerform_id', 'id');
    }

    /**
     * Check if PowerForm is active
     */
    public function isActive(): bool
    {
        return $this->is_active && $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if PowerForm is expired
     */
    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        if ($this->expiration_date && $this->expiration_date->isPast()) {
            return true;
        }

        return false;
    }

    /**
     * Check if PowerForm has reached max uses
     */
    public function hasReachedMaxUses(): bool
    {
        if ($this->max_uses === null) {
            return false;
        }

        return $this->times_used >= $this->max_uses;
    }

    /**
     * Check if PowerForm can accept submissions
     */
    public function canAcceptSubmissions(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        if ($this->hasReachedMaxUses()) {
            return false;
        }

        return true;
    }

    /**
     * Increment the times_used counter
     */
    public function incrementUsageCount(): void
    {
        $this->increment('times_used');

        // Check if we've reached max uses
        if ($this->hasReachedMaxUses()) {
            $this->update(['status' => self::STATUS_DISABLED]);
        }
    }

    /**
     * Mark PowerForm as expired
     */
    public function markAsExpired(): void
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
            'is_active' => false,
        ]);
    }

    /**
     * Mark PowerForm as disabled
     */
    public function markAsDisabled(): void
    {
        $this->update([
            'status' => self::STATUS_DISABLED,
            'is_active' => false,
        ]);
    }

    /**
     * Activate PowerForm
     */
    public function activate(): void
    {
        if ($this->isExpired()) {
            throw new \Exception('Cannot activate expired PowerForm');
        }

        $this->update([
            'status' => self::STATUS_ACTIVE,
            'is_active' => true,
        ]);
    }

    /**
     * Get PowerForm public URL
     */
    public function getPublicUrl(): string
    {
        return config('app.url') . '/powerforms/' . $this->powerform_id;
    }

    /**
     * Scope: Filter by account
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Only active PowerForms
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope: Search by name
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where('name', 'ILIKE', "%{$search}%")
            ->orWhere('description', 'ILIKE', "%{$search}%");
    }
}
