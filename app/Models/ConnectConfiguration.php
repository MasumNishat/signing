<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * ConnectConfiguration Model
 *
 * Represents a webhook/connect configuration for publishing envelope
 * and recipient events to external systems.
 *
 * @property int $id
 * @property int $account_id
 * @property string $connect_id
 * @property string|null $name
 * @property string $url_to_publish_to
 * @property array|null $envelope_events
 * @property array|null $recipient_events
 * @property bool $all_users
 * @property bool $include_certificate_of_completion
 * @property bool $include_documents
 * @property bool $include_envelope_void_reason
 * @property bool $include_sender_account_as_custom_field
 * @property bool $include_time_zone_information
 * @property bool $use_soap_interface
 * @property bool $include_hmac
 * @property bool $sign_message_with_x509_certificate
 * @property bool $enabled
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ConnectConfiguration extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'connect_configurations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'account_id',
        'connect_id',
        'name',
        'url_to_publish_to',
        'envelope_events',
        'recipient_events',
        'all_users',
        'include_certificate_of_completion',
        'include_documents',
        'include_envelope_void_reason',
        'include_sender_account_as_custom_field',
        'include_time_zone_information',
        'use_soap_interface',
        'include_hmac',
        'sign_message_with_x509_certificate',
        'enabled',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'envelope_events' => 'array',
        'recipient_events' => 'array',
        'all_users' => 'boolean',
        'include_certificate_of_completion' => 'boolean',
        'include_documents' => 'boolean',
        'include_envelope_void_reason' => 'boolean',
        'include_sender_account_as_custom_field' => 'boolean',
        'include_time_zone_information' => 'boolean',
        'use_soap_interface' => 'boolean',
        'include_hmac' => 'boolean',
        'sign_message_with_x509_certificate' => 'boolean',
        'enabled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Common envelope event types
     */
    public const EVENT_ENVELOPE_SENT = 'envelope-sent';
    public const EVENT_ENVELOPE_DELIVERED = 'envelope-delivered';
    public const EVENT_ENVELOPE_COMPLETED = 'envelope-completed';
    public const EVENT_ENVELOPE_DECLINED = 'envelope-declined';
    public const EVENT_ENVELOPE_VOIDED = 'envelope-voided';

    /**
     * Common recipient event types
     */
    public const EVENT_RECIPIENT_SENT = 'recipient-sent';
    public const EVENT_RECIPIENT_DELIVERED = 'recipient-delivered';
    public const EVENT_RECIPIENT_SIGNED = 'recipient-signed';
    public const EVENT_RECIPIENT_DECLINED = 'recipient-declined';
    public const EVENT_RECIPIENT_COMPLETED = 'recipient-completed';

    /**
     * Boot method to auto-generate connect_id
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($config) {
            if (empty($config->connect_id)) {
                $config->connect_id = 'con-' . Str::uuid()->toString();
            }
        });
    }

    /**
     * Get the account that owns the configuration
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Get the logs for this configuration
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ConnectLog::class, 'connect_id', 'id');
    }

    /**
     * Get the OAuth configuration
     */
    public function oauthConfig(): HasOne
    {
        return $this->hasOne(ConnectOAuthConfig::class, 'connect_id', 'id');
    }

    /**
     * Check if configuration is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled === true;
    }

    /**
     * Check if configuration is disabled
     */
    public function isDisabled(): bool
    {
        return !$this->isEnabled();
    }

    /**
     * Enable the configuration
     */
    public function enable(): void
    {
        $this->update(['enabled' => true]);
    }

    /**
     * Disable the configuration
     */
    public function disable(): void
    {
        $this->update(['enabled' => false]);
    }

    /**
     * Check if should publish envelope event
     */
    public function shouldPublishEnvelopeEvent(string $event): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        if (empty($this->envelope_events)) {
            return false;
        }

        return in_array($event, $this->envelope_events);
    }

    /**
     * Check if should publish recipient event
     */
    public function shouldPublishRecipientEvent(string $event): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        if (empty($this->recipient_events)) {
            return false;
        }

        return in_array($event, $this->recipient_events);
    }

    /**
     * Scope: Filter by account
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope: Filter by enabled status
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope: Filter by disabled status
     */
    public function scopeDisabled($query)
    {
        return $query->where('enabled', false);
    }
}
