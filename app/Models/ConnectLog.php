<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * ConnectLog Model
 *
 * Represents a log entry for a webhook/connect delivery attempt.
 * Tracks both successful and failed webhook deliveries.
 *
 * @property int $id
 * @property int $account_id
 * @property int|null $connect_id
 * @property string $log_id
 * @property string|null $envelope_id
 * @property string|null $status - success, failed
 * @property \Carbon\Carbon $created_date_time
 * @property string|null $request_url
 * @property string|null $request_body
 * @property string|null $response_body
 * @property string|null $error
 */
class ConnectLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'connect_logs';

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
        'connect_id',
        'log_id',
        'envelope_id',
        'status',
        'created_date_time',
        'request_url',
        'request_body',
        'response_body',
        'error',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_date_time' => 'datetime',
    ];

    /**
     * Status constants
     */
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';

    /**
     * Boot method to auto-generate log_id
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($log) {
            if (empty($log->log_id)) {
                $log->log_id = 'log-' . Str::uuid()->toString();
            }
            if (empty($log->created_date_time)) {
                $log->created_date_time = now();
            }
        });
    }

    /**
     * Get the account that owns the log
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Get the connect configuration
     */
    public function connectConfiguration(): BelongsTo
    {
        return $this->belongsTo(ConnectConfiguration::class, 'connect_id');
    }

    /**
     * Get the envelope
     */
    public function envelope(): BelongsTo
    {
        return $this->belongsTo(Envelope::class, 'envelope_id', 'envelope_id');
    }

    /**
     * Check if log is successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * Check if log is failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Scope: Filter by account
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope: Filter by connect configuration
     */
    public function scopeForConnect($query, int $connectId)
    {
        return $query->where('connect_id', $connectId);
    }

    /**
     * Scope: Filter by envelope
     */
    public function scopeForEnvelope($query, string $envelopeId)
    {
        return $query->where('envelope_id', $envelopeId);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Success logs only
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    /**
     * Scope: Failed logs only
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope: Recent logs first
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_date_time', 'desc');
    }
}
