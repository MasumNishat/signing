<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCustomSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'user_id',
        'setting_key',
        'setting_value',
    ];

    /**
     * Get the account that owns the custom setting.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user that owns the custom setting.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include settings for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to find a specific setting by key.
     */
    public function scopeByKey($query, string $key)
    {
        return $query->where('setting_key', $key);
    }
}
