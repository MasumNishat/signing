<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email_notifications',
        'envelope_complete_notifications',
        'envelope_declined_notifications',
        'envelope_voided_notifications',
        'comment_notifications',
        'default_language',
        'default_timezone',
        'date_format',
        'time_format',
        'attach_completed_envelope',
        'self_sign_documents',
        'default_signature_font',
        'envelope_expiration_days',
        'reminder_frequency_days',
        'reminder_enabled',
        'hide_from_directory',
        'allow_delegate_access',
        'api_access_enabled',
        'api_scopes',
    ];

    protected $casts = [
        'email_notifications' => 'boolean',
        'envelope_complete_notifications' => 'boolean',
        'envelope_declined_notifications' => 'boolean',
        'envelope_voided_notifications' => 'boolean',
        'comment_notifications' => 'boolean',
        'attach_completed_envelope' => 'boolean',
        'self_sign_documents' => 'boolean',
        'reminder_enabled' => 'boolean',
        'hide_from_directory' => 'boolean',
        'allow_delegate_access' => 'boolean',
        'api_access_enabled' => 'boolean',
        'api_scopes' => 'array',
        'envelope_expiration_days' => 'integer',
        'reminder_frequency_days' => 'integer',
    ];

    /**
     * Get the user that owns the settings.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if email notifications are enabled.
     */
    public function hasEmailNotifications(): bool
    {
        return $this->email_notifications;
    }

    /**
     * Check if API access is enabled.
     */
    public function hasApiAccess(): bool
    {
        return $this->api_access_enabled;
    }
}
