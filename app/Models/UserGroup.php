<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class UserGroup extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_groups';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'account_id',
        'group_id',
        'group_name',
        'group_type',
        'permission_profile_id',
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
    const TYPE_ADMIN_GROUP = 'admin_group';
    const TYPE_CUSTOM_GROUP = 'custom_group';
    const TYPE_EVERYONE_GROUP = 'everyone_group';

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($group) {
            if (empty($group->group_id)) {
                $group->group_id = (string) Str::uuid();
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
     * Get the permission profile for the group.
     */
    public function permissionProfile(): BelongsTo
    {
        return $this->belongsTo(PermissionProfile::class, 'permission_profile_id');
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
     * Get the users in this group.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_group_users', 'user_group_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Get the brands associated with this group.
     */
    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'user_group_brands', 'user_group_id', 'brand_id')
            ->withTimestamps();
    }

    /**
     * Check if group is admin group.
     */
    public function isAdminGroup(): bool
    {
        return $this->group_type === self::TYPE_ADMIN_GROUP;
    }

    /**
     * Check if group is everyone group.
     */
    public function isEveryoneGroup(): bool
    {
        return $this->group_type === self::TYPE_EVERYONE_GROUP;
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
