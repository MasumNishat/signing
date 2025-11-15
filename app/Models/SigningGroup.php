<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class SigningGroup extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'signing_groups';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'account_id',
        'signing_group_id',
        'group_name',
        'group_email',
        'group_type',
        'created_by',
        'modified_by',
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
     * Group type constants.
     */
    const TYPE_PUBLIC = 'public';
    const TYPE_PRIVATE = 'private';
    const TYPE_SHARED = 'shared';

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($group) {
            if (empty($group->signing_group_id)) {
                $group->signing_group_id = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the account that owns the group.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Get the user who created the group.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last modified the group.
     */
    public function modifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'modified_by');
    }

    /**
     * Get the users in this signing group.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'signing_group_users', 'signing_group_id', 'user_id')
            ->withPivot('email', 'user_name')
            ->withTimestamps();
    }

    /**
     * Scope a query to only include groups of a given type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('group_type', $type);
    }

    /**
     * Scope a query to only include groups for a specific account.
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }
}
