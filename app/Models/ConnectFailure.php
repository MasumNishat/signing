<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * ConnectFailure Model
 *
 * Represents a failed webhook delivery that can be retried.
 * Stores failure information and retry attempts.
 *
 * @property int $id
 * @property int $account_id
 * @property string $failure_id
 * @property string|null $envelope_id
 * @property string|null $error
 * @property \Carbon\Carbon|null $failed_date_time
 * @property int $retry_count
 * @property \Carbon\Carbon $created_at
 */
class ConnectFailure extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'connect_failures';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'account_id',
        'failure_id',
        'envelope_id',
        'error',
        'failed_date_time',
        'retry_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'failed_date_time' => 'datetime',
        'created_at' => 'datetime',
        'retry_count' => 'integer',
    ];

    /**
     * Maximum retry attempts before giving up
     */
    public const MAX_RETRY_ATTEMPTS = 5;

    /**
     * Boot method to auto-generate failure_id
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($failure) {
            if (empty($failure->failure_id)) {
                $failure->failure_id = 'fail-' . Str::uuid()->toString();
            }
            if (empty($failure->failed_date_time)) {
                $failure->failed_date_time = now();
            }
        });
    }

    /**
     * Get the account that owns the failure
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Get the envelope
     */
    public function envelope(): BelongsTo
    {
        return $this->belongsTo(Envelope::class, 'envelope_id', 'envelope_id');
    }

    /**
     * Check if can retry
     */
    public function canRetry(): bool
    {
        return $this->retry_count < self::MAX_RETRY_ATTEMPTS;
    }

    /**
     * Check if max retries reached
     */
    public function maxRetriesReached(): bool
    {
        return $this->retry_count >= self::MAX_RETRY_ATTEMPTS;
    }

    /**
     * Increment retry count
     */
    public function incrementRetryCount(): void
    {
        $this->increment('retry_count');
    }

    /**
     * Scope: Filter by account
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope: Filter by envelope
     */
    public function scopeForEnvelope($query, string $envelopeId)
    {
        return $query->where('envelope_id', $envelopeId);
    }

    /**
     * Scope: Only failures that can be retried
     */
    public function scopeRetryable($query)
    {
        return $query->where('retry_count', '<', self::MAX_RETRY_ATTEMPTS);
    }

    /**
     * Scope: Recent failures first
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('failed_date_time', 'desc');
    }
}
