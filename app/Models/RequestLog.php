<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * RequestLog Model
 *
 * Stores API request and response details for monitoring and debugging.
 *
 * @property int $id
 * @property int $account_id
 * @property int|null $user_id
 * @property string $request_log_id
 * @property \Carbon\Carbon $created_date_time
 * @property string|null $request_method
 * @property string|null $request_url
 * @property array|null $request_headers
 * @property string|null $request_body
 * @property int|null $response_status
 * @property array|null $response_headers
 * @property string|null $response_body
 * @property int|null $duration_ms
 * @property string|null $ip_address
 * @property \Carbon\Carbon $created_at
 *
 * @property-read Account $account
 * @property-read User|null $user
 */
class RequestLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'request_logs';

    /**
     * Indicates if the model should update timestamps.
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
        'user_id',
        'request_log_id',
        'created_date_time',
        'request_method',
        'request_url',
        'request_headers',
        'request_body',
        'response_status',
        'response_headers',
        'response_body',
        'duration_ms',
        'ip_address',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_date_time' => 'datetime',
        'request_headers' => 'array',
        'response_headers' => 'array',
        'duration_ms' => 'integer',
        'response_status' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Boot function from Laravel.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($log) {
            if (empty($log->request_log_id)) {
                $log->request_log_id = 'log-' . Str::uuid()->toString();
            }
            if (empty($log->created_date_time)) {
                $log->created_date_time = now();
            }
            if (empty($log->created_at)) {
                $log->created_at = now();
            }
        });
    }

    /**
     * Get the account that owns the log.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user who made the request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the request was successful (2xx status).
     */
    public function isSuccessful(): bool
    {
        return $this->response_status >= 200 && $this->response_status < 300;
    }

    /**
     * Check if the request failed (4xx or 5xx status).
     */
    public function isFailed(): bool
    {
        return $this->response_status >= 400;
    }

    /**
     * Get human-readable duration.
     */
    public function getDurationFormatted(): string
    {
        if ($this->duration_ms === null) {
            return 'Unknown';
        }

        if ($this->duration_ms < 1000) {
            return $this->duration_ms . ' ms';
        }

        return round($this->duration_ms / 1000, 2) . ' s';
    }

    /**
     * Scope a query to only include successful requests.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('response_status', '>=', 200)
                     ->where('response_status', '<', 300);
    }

    /**
     * Scope a query to only include failed requests.
     */
    public function scopeFailed($query)
    {
        return $query->where('response_status', '>=', 400);
    }

    /**
     * Scope a query to filter by account.
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to filter by method.
     */
    public function scopeWithMethod($query, string $method)
    {
        return $query->where('request_method', strtoupper($method));
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_date_time', [$startDate, $endDate]);
    }
}
