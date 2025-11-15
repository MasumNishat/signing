<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvelopePurgeConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'enable_purge',
        'purge_interval_days',
        'retain_completed_envelopes_days',
        'retain_voided_envelopes_days',
    ];

    protected $casts = [
        'enable_purge' => 'boolean',
        'purge_interval_days' => 'integer',
        'retain_completed_envelopes_days' => 'integer',
        'retain_voided_envelopes_days' => 'integer',
    ];

    /**
     * Get the account that owns the purge configuration.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Check if purge is enabled.
     */
    public function isPurgeEnabled(): bool
    {
        return $this->enable_purge;
    }

    /**
     * Get the retention period for completed envelopes.
     */
    public function getCompletedRetentionDays(): int
    {
        return $this->retain_completed_envelopes_days;
    }

    /**
     * Get the retention period for voided envelopes.
     */
    public function getVoidedRetentionDays(): int
    {
        return $this->retain_voided_envelopes_days;
    }
}
