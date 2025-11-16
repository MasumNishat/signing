<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CaptiveRecipient Model
 *
 * Manages captive recipients (embedded signers) who are pre-configured
 * for specific accounts. Used for embedded signing workflows where
 * recipients are known in advance.
 *
 * @property int $id
 * @property int $account_id
 * @property string $recipient_part
 * @property string $email
 * @property string|null $user_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 * @property-read Account $account
 */
class CaptiveRecipient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'account_id',
        'recipient_part',
        'email',
        'user_name',
    ];

    protected $casts = [
        'account_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the account that owns the captive recipient
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Scope: Search by email
     */
    public function scopeByEmail($query, string $email)
    {
        return $query->where('email', 'like', "%{$email}%");
    }

    /**
     * Scope: Search by name
     */
    public function scopeByName($query, string $name)
    {
        return $query->where('user_name', 'like', "%{$name}%");
    }

    /**
     * Scope: For specific account
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }
}
