<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * BulkSendList Model
 *
 * Represents a reusable list of recipients for bulk sending.
 * Contains multiple recipients that can be used to send envelopes in bulk.
 *
 * @property int $id
 * @property int $account_id
 * @property string $list_id
 * @property string $list_name
 * @property int|null $created_by_user_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class BulkSendList extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bulk_send_lists';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'account_id',
        'list_id',
        'list_name',
        'created_by_user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot method to auto-generate list_id
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($list) {
            if (empty($list->list_id)) {
                $list->list_id = 'list-' . Str::uuid()->toString();
            }
        });
    }

    /**
     * Get the account that owns the list
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Get the user who created the list
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get recipients in this list
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(BulkSendRecipient::class, 'list_id', 'id');
    }

    /**
     * Get recipients count
     */
    public function getRecipientsCountAttribute(): int
    {
        return $this->recipients()->count();
    }

    /**
     * Scope: Filter by account
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope: Filter by creator
     */
    public function scopeCreatedBy($query, int $userId)
    {
        return $query->where('created_by_user_id', $userId);
    }

    /**
     * Scope: Search by name
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where('list_name', 'ilike', "%{$term}%");
    }

    /**
     * Scope: Recent lists first
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope: With recipients loaded
     */
    public function scopeWithRecipients($query)
    {
        return $query->with('recipients');
    }
}
