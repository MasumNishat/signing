<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EnvelopeTransferRule extends Model
{
    use HasFactory;

    protected $table = 'envelope_transfer_rules';

    protected $fillable = [
        'account_id',
        'rule_id',
        'rule_name',
        'enabled',
        'from_user_id',
        'to_user_id',
        'from_group_id',
        'to_group_id',
        'modified_start_date',
        'modified_end_date',
        'envelope_types',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'envelope_types' => 'array',
        'modified_start_date' => 'date',
        'modified_end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate rule_id if not provided
        static::creating(function ($model) {
            if (empty($model->rule_id)) {
                $model->rule_id = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the account that owns this transfer rule
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the from user
     */
    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /**
     * Get the to user
     */
    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    /**
     * Check if rule is currently active based on date range
     */
    public function isActive(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $now = now()->toDateString();

        // Check start date
        if ($this->modified_start_date && $now < $this->modified_start_date->toDateString()) {
            return false;
        }

        // Check end date
        if ($this->modified_end_date && $now > $this->modified_end_date->toDateString()) {
            return false;
        }

        return true;
    }

    /**
     * Check if this rule applies to a specific envelope type
     */
    public function appliesToEnvelopeType(string $type): bool
    {
        if (empty($this->envelope_types)) {
            return true; // Applies to all types
        }

        return in_array($type, $this->envelope_types);
    }

    /**
     * Scope: Only enabled rules
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope: Only active rules (enabled + within date range)
     */
    public function scopeActive($query)
    {
        $now = now()->toDateString();

        return $query->where('enabled', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('modified_start_date')
                    ->orWhere('modified_start_date', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('modified_end_date')
                    ->orWhere('modified_end_date', '>=', $now);
            });
    }

    /**
     * Scope: For a specific account
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope: From a specific user
     */
    public function scopeFromUser($query, int $userId)
    {
        return $query->where('from_user_id', $userId);
    }

    /**
     * Scope: To a specific user
     */
    public function scopeToUser($query, int $userId)
    {
        return $query->where('to_user_id', $userId);
    }
}
