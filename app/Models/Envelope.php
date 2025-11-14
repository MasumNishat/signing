<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Envelope Model
 *
 * Represents a DocuSign envelope containing documents to be signed.
 *
 * @property int $id
 * @property int $account_id
 * @property string $envelope_id
 * @property string|null $email_subject
 * @property string|null $email_blurb
 * @property string $status
 * @property int|null $sender_user_id
 * @property string|null $sender_name
 * @property string|null $sender_email
 * @property \Carbon\Carbon $created_date_time
 * @property \Carbon\Carbon|null $sent_date_time
 * @property \Carbon\Carbon|null $delivered_date_time
 * @property \Carbon\Carbon|null $signed_date_time
 * @property \Carbon\Carbon|null $completed_date_time
 * @property \Carbon\Carbon|null $declined_date_time
 * @property \Carbon\Carbon|null $voided_date_time
 * @property string|null $voided_reason
 * @property bool $enable_wet_sign
 * @property bool $allow_markup
 * @property bool $allow_reassign
 * @property bool $allow_view_history
 * @property bool $enforce_signer_visibility
 * @property bool $is_signature_provider_envelope
 * @property bool $use_disclosure
 * @property bool $reminder_enabled
 * @property int|null $reminder_delay
 * @property int|null $reminder_frequency
 * @property bool $expire_enabled
 * @property int|null $expire_after
 * @property int|null $expire_warn
 * @property bool $is_dynamic_envelope
 * @property bool $enable_sequential_signing
 * @property string|null $custom_fields_uri
 * @property string|null $documents_uri
 * @property string|null $recipients_uri
 * @property string|null $envelope_uri
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read Account $account
 * @property-read User|null $sender
 * @property-read \Illuminate\Database\Eloquent\Collection|EnvelopeDocument[] $documents
 * @property-read \Illuminate\Database\Eloquent\Collection|EnvelopeRecipient[] $recipients
 * @property-read \Illuminate\Database\Eloquent\Collection|EnvelopeTab[] $tabs
 * @property-read \Illuminate\Database\Eloquent\Collection|EnvelopeCustomField[] $customFields
 * @property-read \Illuminate\Database\Eloquent\Collection|EnvelopeAttachment[] $attachments
 * @property-read \Illuminate\Database\Eloquent\Collection|EnvelopeAuditEvent[] $auditEvents
 * @property-read \Illuminate\Database\Eloquent\Collection|EnvelopeView[] $views
 * @property-read EnvelopeWorkflow|null $workflow
 * @property-read EnvelopeLock|null $lock
 */
class Envelope extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'envelopes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'account_id',
        'envelope_id',
        'email_subject',
        'email_blurb',
        'status',
        'sender_user_id',
        'sender_name',
        'sender_email',
        'created_date_time',
        'sent_date_time',
        'delivered_date_time',
        'signed_date_time',
        'completed_date_time',
        'declined_date_time',
        'voided_date_time',
        'voided_reason',
        'enable_wet_sign',
        'allow_markup',
        'allow_reassign',
        'allow_view_history',
        'enforce_signer_visibility',
        'is_signature_provider_envelope',
        'use_disclosure',
        'reminder_enabled',
        'reminder_delay',
        'reminder_frequency',
        'expire_enabled',
        'expire_after',
        'expire_warn',
        'is_dynamic_envelope',
        'enable_sequential_signing',
        'custom_fields_uri',
        'documents_uri',
        'recipients_uri',
        'envelope_uri',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_date_time' => 'datetime',
        'sent_date_time' => 'datetime',
        'delivered_date_time' => 'datetime',
        'signed_date_time' => 'datetime',
        'completed_date_time' => 'datetime',
        'declined_date_time' => 'datetime',
        'voided_date_time' => 'datetime',
        'enable_wet_sign' => 'boolean',
        'allow_markup' => 'boolean',
        'allow_reassign' => 'boolean',
        'allow_view_history' => 'boolean',
        'enforce_signer_visibility' => 'boolean',
        'is_signature_provider_envelope' => 'boolean',
        'use_disclosure' => 'boolean',
        'reminder_enabled' => 'boolean',
        'reminder_delay' => 'integer',
        'reminder_frequency' => 'integer',
        'expire_enabled' => 'boolean',
        'expire_after' => 'integer',
        'expire_warn' => 'integer',
        'is_dynamic_envelope' => 'boolean',
        'enable_sequential_signing' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'deleted_at',
    ];

    /**
     * Envelope status constants.
     */
    const STATUS_CREATED = 'created';
    const STATUS_SENT = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_SIGNED = 'signed';
    const STATUS_COMPLETED = 'completed';
    const STATUS_DECLINED = 'declined';
    const STATUS_VOIDED = 'voided';

    /**
     * Valid envelope statuses.
     *
     * @var array<string>
     */
    public static array $validStatuses = [
        self::STATUS_CREATED,
        self::STATUS_SENT,
        self::STATUS_DELIVERED,
        self::STATUS_SIGNED,
        self::STATUS_COMPLETED,
        self::STATUS_DECLINED,
        self::STATUS_VOIDED,
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Envelope $envelope) {
            if (empty($envelope->envelope_id)) {
                $envelope->envelope_id = self::generateEnvelopeId();
            }

            if (empty($envelope->created_date_time)) {
                $envelope->created_date_time = now();
            }
        });
    }

    /**
     * Generate a unique envelope ID.
     *
     * @return string
     */
    public static function generateEnvelopeId(): string
    {
        return 'env_' . Str::uuid()->toString();
    }

    /**
     * Get the account that owns the envelope.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Get the sender user.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    /**
     * Get the documents for the envelope.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(EnvelopeDocument::class, 'envelope_id');
    }

    /**
     * Get the recipients for the envelope.
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(EnvelopeRecipient::class, 'envelope_id');
    }

    /**
     * Get the tabs for the envelope.
     */
    public function tabs(): HasMany
    {
        return $this->hasMany(EnvelopeTab::class, 'envelope_id');
    }

    /**
     * Get the custom fields for the envelope.
     */
    public function customFields(): HasMany
    {
        return $this->hasMany(EnvelopeCustomField::class, 'envelope_id');
    }

    /**
     * Get the attachments for the envelope.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(EnvelopeAttachment::class, 'envelope_id');
    }

    /**
     * Get the audit events for the envelope.
     */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(EnvelopeAuditEvent::class, 'envelope_id');
    }

    /**
     * Get the views for the envelope.
     */
    public function views(): HasMany
    {
        return $this->hasMany(EnvelopeView::class, 'envelope_id');
    }

    /**
     * Get the workflow for the envelope.
     */
    public function workflow(): HasOne
    {
        return $this->hasOne(EnvelopeWorkflow::class, 'envelope_id');
    }

    /**
     * Get the lock for the envelope.
     */
    public function lock(): HasOne
    {
        return $this->hasOne(EnvelopeLock::class, 'envelope_id');
    }

    /**
     * Scope a query to only include envelopes with a specific status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include sent envelopes.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSent($query)
    {
        return $query->whereNotNull('sent_date_time');
    }

    /**
     * Scope a query to only include completed envelopes.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope a query to only include voided envelopes.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVoided($query)
    {
        return $query->where('status', self::STATUS_VOIDED);
    }

    /**
     * Scope a query to only include envelopes for a specific account.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $accountId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope a query to only include envelopes sent by a specific user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSentBy($query, int $userId)
    {
        return $query->where('sender_user_id', $userId);
    }

    /**
     * Scope a query to only include envelopes created within a date range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $from
     * @param  string  $to
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCreatedBetween($query, string $from, string $to)
    {
        return $query->whereBetween('created_date_time', [$from, $to]);
    }

    /**
     * Check if the envelope is in draft status.
     *
     * @return bool
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_CREATED;
    }

    /**
     * Check if the envelope has been sent.
     *
     * @return bool
     */
    public function isSent(): bool
    {
        return !is_null($this->sent_date_time) && $this->status !== self::STATUS_CREATED;
    }

    /**
     * Check if the envelope is completed.
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if the envelope is voided.
     *
     * @return bool
     */
    public function isVoided(): bool
    {
        return $this->status === self::STATUS_VOIDED;
    }

    /**
     * Check if the envelope is declined.
     *
     * @return bool
     */
    public function isDeclined(): bool
    {
        return $this->status === self::STATUS_DECLINED;
    }

    /**
     * Check if the envelope can be modified.
     *
     * @return bool
     */
    public function canBeModified(): bool
    {
        return in_array($this->status, [self::STATUS_CREATED, self::STATUS_SENT]);
    }

    /**
     * Check if the envelope can be voided.
     *
     * @return bool
     */
    public function canBeVoided(): bool
    {
        return in_array($this->status, [
            self::STATUS_SENT,
            self::STATUS_DELIVERED,
            self::STATUS_SIGNED,
        ]);
    }

    /**
     * Check if the envelope has expired.
     *
     * @return bool
     */
    public function hasExpired(): bool
    {
        if (!$this->expire_enabled || !$this->sent_date_time) {
            return false;
        }

        $expirationDate = $this->sent_date_time->addDays($this->expire_after);
        return now()->isAfter($expirationDate);
    }

    /**
     * Get the envelope's current completion percentage.
     *
     * @return float
     */
    public function getCompletionPercentage(): float
    {
        $totalRecipients = $this->recipients()->count();

        if ($totalRecipients === 0) {
            return 0.0;
        }

        $completedRecipients = $this->recipients()
            ->whereIn('status', ['completed', 'signed'])
            ->count();

        return ($completedRecipients / $totalRecipients) * 100;
    }

    /**
     * Mark the envelope as sent.
     *
     * @return bool
     */
    public function markAsSent(): bool
    {
        $this->status = self::STATUS_SENT;
        $this->sent_date_time = now();

        return $this->save();
    }

    /**
     * Mark the envelope as delivered.
     *
     * @return bool
     */
    public function markAsDelivered(): bool
    {
        $this->status = self::STATUS_DELIVERED;
        $this->delivered_date_time = now();

        return $this->save();
    }

    /**
     * Mark the envelope as completed.
     *
     * @return bool
     */
    public function markAsCompleted(): bool
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_date_time = now();

        return $this->save();
    }

    /**
     * Mark the envelope as voided.
     *
     * @param  string  $reason
     * @return bool
     */
    public function markAsVoided(string $reason): bool
    {
        $this->status = self::STATUS_VOIDED;
        $this->voided_date_time = now();
        $this->voided_reason = $reason;

        return $this->save();
    }

    /**
     * Mark the envelope as declined.
     *
     * @return bool
     */
    public function markAsDeclined(): bool
    {
        $this->status = self::STATUS_DECLINED;
        $this->declined_date_time = now();

        return $this->save();
    }
}
