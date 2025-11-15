<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationDefault extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'api_email_notifications',
        'bulk_email_notifications',
        'reminder_email_notifications',
        'email_subject_template',
        'email_body_template',
    ];

    protected $casts = [
        'api_email_notifications' => 'boolean',
        'bulk_email_notifications' => 'boolean',
        'reminder_email_notifications' => 'boolean',
    ];

    /**
     * Get the account that owns the notification defaults.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Check if API email notifications are enabled.
     */
    public function hasApiEmailNotifications(): bool
    {
        return $this->api_email_notifications;
    }

    /**
     * Check if bulk email notifications are enabled.
     */
    public function hasBulkEmailNotifications(): bool
    {
        return $this->bulk_email_notifications;
    }

    /**
     * Check if reminder email notifications are enabled.
     */
    public function hasReminderEmailNotifications(): bool
    {
        return $this->reminder_email_notifications;
    }

    /**
     * Get the email subject template or default.
     */
    public function getEmailSubject(): string
    {
        return $this->email_subject_template ?? 'You have a document to sign';
    }

    /**
     * Get the email body template or default.
     */
    public function getEmailBody(): string
    {
        return $this->email_body_template ?? 'Please review and sign the attached document.';
    }
}
