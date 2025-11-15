<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AuditLog Model
 *
 * Stores audit trail for user actions and system events.
 *
 * @property int $id
 * @property int $account_id
 * @property int|null $user_id
 * @property string $action
 * @property string|null $resource_type
 * @property string|null $resource_id
 * @property array|null $old_values
 * @property array|null $new_values
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon $created_at
 *
 * @property-read Account $account
 * @property-read User|null $user
 */
class AuditLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'audit_logs';

    /**
     * Indicates if the model should update timestamps.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Action constants
     */
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';
    const ACTION_VIEW = 'view';
    const ACTION_SEND = 'send';
    const ACTION_SIGN = 'sign';
    const ACTION_VOID = 'void';
    const ACTION_LOGIN = 'login';
    const ACTION_LOGOUT = 'logout';
    const ACTION_DOWNLOAD = 'download';
    const ACTION_UPLOAD = 'upload';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'account_id',
        'user_id',
        'action',
        'resource_type',
        'resource_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Boot function from Laravel.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($log) {
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
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the changes made (difference between old and new values).
     */
    public function getChanges(): array
    {
        if (empty($this->old_values) || empty($this->new_values)) {
            return [];
        }

        $changes = [];
        foreach ($this->new_values as $key => $newValue) {
            $oldValue = $this->old_values[$key] ?? null;
            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }

    /**
     * Check if this is a create action.
     */
    public function isCreate(): bool
    {
        return $this->action === self::ACTION_CREATE;
    }

    /**
     * Check if this is an update action.
     */
    public function isUpdate(): bool
    {
        return $this->action === self::ACTION_UPDATE;
    }

    /**
     * Check if this is a delete action.
     */
    public function isDelete(): bool
    {
        return $this->action === self::ACTION_DELETE;
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
     * Scope a query to filter by action.
     */
    public function scopeWithAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope a query to filter by resource type.
     */
    public function scopeForResourceType($query, string $resourceType)
    {
        return $query->where('resource_type', $resourceType);
    }

    /**
     * Scope a query to filter by resource ID.
     */
    public function scopeForResource($query, string $resourceType, string $resourceId)
    {
        return $query->where('resource_type', $resourceType)
                     ->where('resource_id', $resourceId);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
