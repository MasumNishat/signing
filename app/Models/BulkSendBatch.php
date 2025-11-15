<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * BulkSendBatch Model
 *
 * Represents a batch of bulk-sent envelopes.
 * Tracks the status and progress of bulk envelope sending operations.
 *
 * @property int $id
 * @property int $account_id
 * @property string $batch_id
 * @property string|null $batch_name
 * @property string $status - queued, processing, sent, failed
 * @property int|null $batch_size
 * @property int $envelopes_sent
 * @property int $envelopes_failed
 * @property \Carbon\Carbon|null $submitted_date
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class BulkSendBatch extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bulk_send_batches';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'account_id',
        'batch_id',
        'batch_name',
        'status',
        'batch_size',
        'envelopes_sent',
        'envelopes_failed',
        'submitted_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'batch_size' => 'integer',
        'envelopes_sent' => 'integer',
        'envelopes_failed' => 'integer',
        'submitted_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    public const STATUS_QUEUED = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    /**
     * Bulk action constants
     */
    public const ACTION_RESEND = 'resend';
    public const ACTION_CANCEL = 'cancel';
    public const ACTION_PAUSE = 'pause';
    public const ACTION_RESUME = 'resume';

    /**
     * Boot method to auto-generate batch_id
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($batch) {
            if (empty($batch->batch_id)) {
                $batch->batch_id = 'bulk-' . Str::uuid()->toString();
            }
            if (empty($batch->submitted_date)) {
                $batch->submitted_date = now();
            }
        });
    }

    /**
     * Get the account that owns the batch
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Get envelopes associated with this batch
     * Note: Requires envelope_id to be tagged with batch_id in envelopes table
     * This is a simplified relationship - in production you'd have a pivot table
     */
    public function envelopes(): HasMany
    {
        return $this->hasMany(Envelope::class, 'batch_id', 'batch_id');
    }

    /**
     * Check if batch is queued
     */
    public function isQueued(): bool
    {
        return $this->status === self::STATUS_QUEUED;
    }

    /**
     * Check if batch is processing
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Check if batch is sent
     */
    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    /**
     * Check if batch has failed
     */
    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Mark batch as processing
     */
    public function markAsProcessing(): void
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
    }

    /**
     * Mark batch as sent
     */
    public function markAsSent(): void
    {
        $this->update(['status' => self::STATUS_SENT]);
    }

    /**
     * Mark batch as failed
     */
    public function markAsFailed(): void
    {
        $this->update(['status' => self::STATUS_FAILED]);
    }

    /**
     * Increment envelopes sent count
     */
    public function incrementSentCount(int $count = 1): void
    {
        $this->increment('envelopes_sent', $count);
    }

    /**
     * Increment envelopes failed count
     */
    public function incrementFailedCount(int $count = 1): void
    {
        $this->increment('envelopes_failed', $count);
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentage(): float
    {
        if ($this->batch_size === 0 || $this->batch_size === null) {
            return 0;
        }

        $total = $this->envelopes_sent + $this->envelopes_failed;
        return round(($total / $this->batch_size) * 100, 2);
    }

    /**
     * Check if batch is complete
     */
    public function isComplete(): bool
    {
        if ($this->batch_size === null) {
            return false;
        }

        return ($this->envelopes_sent + $this->envelopes_failed) >= $this->batch_size;
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
     * Scope: Queued batches
     */
    public function scopeQueued($query)
    {
        return $query->where('status', self::STATUS_QUEUED);
    }

    /**
     * Scope: Processing batches
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    /**
     * Scope: Recent batches first
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
