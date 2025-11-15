<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EnvelopeRecipient extends Model
{
    use HasFactory;

    protected $table = 'envelope_recipients';

    /**
     * Recipient types
     */
    public const TYPE_SIGNER = 'signer';
    public const TYPE_CARBON_COPY = 'carbon_copy';
    public const TYPE_CERTIFIED_DELIVERY = 'certified_delivery';
    public const TYPE_IN_PERSON_SIGNER = 'in_person_signer';
    public const TYPE_AGENT = 'agent';
    public const TYPE_EDITOR = 'editor';
    public const TYPE_INTERMEDIARY = 'intermediary';

    /**
     * Recipient statuses
     */
    public const STATUS_CREATED = 'created';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_SIGNED = 'signed';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAX_PENDING = 'fax_pending';
    public const STATUS_AUTO_RESPONDED = 'auto_responded';

    protected $fillable = [
        'envelope_id',
        'template_id',
        'recipient_id',
        'recipient_type',
        'role_name',
        'name',
        'email',
        'routing_order',
        'status',
        'signed_date_time',
        'delivered_date_time',
        'sent_date_time',
        'declined_date_time',
        'declined_reason',
        'access_code',
        'require_id_lookup',
        'id_check_configuration_name',
        'phone_authentication_country_code',
        'phone_authentication_number',
        'sms_authentication_country_code',
        'sms_authentication_number',
        'can_sign_offline',
        'require_signer_certificate',
        'require_sign_on_paper',
        'sign_in_each_location',
        'host_name',
        'host_email',
        'client_user_id',
        'embedded_recipient_start_url',
    ];

    protected $casts = [
        'routing_order' => 'integer',
        'signed_date_time' => 'datetime',
        'delivered_date_time' => 'datetime',
        'sent_date_time' => 'datetime',
        'declined_date_time' => 'datetime',
        'require_id_lookup' => 'boolean',
        'can_sign_offline' => 'boolean',
        'require_signer_certificate' => 'boolean',
        'require_sign_on_paper' => 'boolean',
        'sign_in_each_location' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (EnvelopeRecipient $recipient) {
            if (empty($recipient->recipient_id)) {
                $recipient->recipient_id = 'rec_' . Str::uuid()->toString();
            }
        });
    }

    /**
     * Get the envelope that owns the recipient
     */
    public function envelope(): BelongsTo
    {
        return $this->belongsTo(Envelope::class, 'envelope_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class, 'template_id');
    }

    /**
     * Get the tabs assigned to this recipient
     */
    public function tabs(): HasMany
    {
        return $this->hasMany(EnvelopeTab::class, 'recipient_id');
    }

    /**
     * Check if recipient is a signer
     */
    public function isSigner(): bool
    {
        return in_array($this->recipient_type, [
            self::TYPE_SIGNER,
            self::TYPE_IN_PERSON_SIGNER,
            self::TYPE_AGENT,
        ]);
    }

    /**
     * Check if recipient has signed
     */
    public function hasSigned(): bool
    {
        return in_array($this->status, [
            self::STATUS_SIGNED,
            self::STATUS_COMPLETED,
        ]);
    }

    /**
     * Check if recipient has declined
     */
    public function hasDeclined(): bool
    {
        return $this->status === self::STATUS_DECLINED;
    }

    /**
     * Check if recipient can modify envelope
     */
    public function canModify(): bool
    {
        return in_array($this->recipient_type, [
            self::TYPE_EDITOR,
            self::TYPE_AGENT,
        ]);
    }

    /**
     * Mark recipient as sent
     */
    public function markAsSent(): void
    {
        $this->status = self::STATUS_SENT;
        $this->sent_date_time = now();
        $this->save();
    }

    /**
     * Mark recipient as delivered
     */
    public function markAsDelivered(): void
    {
        $this->status = self::STATUS_DELIVERED;
        $this->delivered_date_time = now();
        $this->save();
    }

    /**
     * Mark recipient as signed
     */
    public function markAsSigned(): void
    {
        $this->status = self::STATUS_SIGNED;
        $this->signed_date_time = now();
        $this->save();
    }

    /**
     * Mark recipient as declined
     */
    public function markAsDeclined(string $reason = null): void
    {
        $this->status = self::STATUS_DECLINED;
        $this->declined_date_time = now();
        $this->declined_reason = $reason;
        $this->save();
    }

    /**
     * Scope to get recipients by routing order
     */
    public function scopeByRoutingOrder($query, int $order)
    {
        return $query->where('routing_order', $order);
    }

    /**
     * Scope to get signers only
     */
    public function scopeSignersOnly($query)
    {
        return $query->whereIn('recipient_type', [
            self::TYPE_SIGNER,
            self::TYPE_IN_PERSON_SIGNER,
            self::TYPE_AGENT,
        ]);
    }

    /**
     * Scope to get recipients by status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
