<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AccountSettings Model
 *
 * Stores configuration settings for an account including signing,
 * security, branding, and API settings.
 *
 * @property int $id
 * @property int $account_id
 * @property bool $allow_signing_extensions
 * @property bool $allow_signature_stamps
 * @property bool $enable_signer_attachments
 * @property bool $enable_two_factor_authentication
 * @property bool $require_signing_captcha
 * @property int $session_timeout_minutes
 * @property bool $can_self_brand_send
 * @property bool $can_self_brand_sign
 * @property bool $enable_api_request_logging
 * @property int $api_request_log_max_entries
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Account $account
 */
class AccountSettings extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'account_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'account_id',
        'allow_signing_extensions',
        'allow_signature_stamps',
        'enable_signer_attachments',
        'enable_two_factor_authentication',
        'require_signing_captcha',
        'session_timeout_minutes',
        'can_self_brand_send',
        'can_self_brand_sign',
        'enable_api_request_logging',
        'api_request_log_max_entries',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'allow_signing_extensions' => 'boolean',
        'allow_signature_stamps' => 'boolean',
        'enable_signer_attachments' => 'boolean',
        'enable_two_factor_authentication' => 'boolean',
        'require_signing_captcha' => 'boolean',
        'session_timeout_minutes' => 'integer',
        'can_self_brand_send' => 'boolean',
        'can_self_brand_sign' => 'boolean',
        'enable_api_request_logging' => 'boolean',
        'api_request_log_max_entries' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the account that owns the settings.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get all settings as an array.
     */
    public function toSettingsArray(): array
    {
        return [
            'signing' => [
                'allow_signing_extensions' => $this->allow_signing_extensions,
                'allow_signature_stamps' => $this->allow_signature_stamps,
                'enable_signer_attachments' => $this->enable_signer_attachments,
            ],
            'security' => [
                'enable_two_factor_authentication' => $this->enable_two_factor_authentication,
                'require_signing_captcha' => $this->require_signing_captcha,
                'session_timeout_minutes' => $this->session_timeout_minutes,
            ],
            'branding' => [
                'can_self_brand_send' => $this->can_self_brand_send,
                'can_self_brand_sign' => $this->can_self_brand_sign,
            ],
            'api' => [
                'enable_api_request_logging' => $this->enable_api_request_logging,
                'api_request_log_max_entries' => $this->api_request_log_max_entries,
            ],
        ];
    }
}
