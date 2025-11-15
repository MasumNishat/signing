<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Workspace Model
 *
 * Represents a collaborative workspace for document management.
 * Workspaces provide organized storage for files and folders.
 *
 * @property int $id
 * @property int $account_id
 * @property string $workspace_id
 * @property string $workspace_name
 * @property string|null $workspace_description
 * @property string|null $workspace_uri
 * @property int|null $created_by_user_id
 * @property string $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Account $account
 * @property-read User|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Collection|WorkspaceFolder[] $folders
 */
class Workspace extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'workspaces';

    /**
     * Status constants
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_ARCHIVED = 'archived';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'account_id',
        'workspace_id',
        'workspace_name',
        'workspace_description',
        'workspace_uri',
        'created_by_user_id',
        'status',
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
     * Boot function from Laravel.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($workspace) {
            if (empty($workspace->workspace_id)) {
                $workspace->workspace_id = 'ws-' . Str::uuid()->toString();
            }
            if (empty($workspace->status)) {
                $workspace->status = self::STATUS_ACTIVE;
            }
        });
    }

    /**
     * Get the account that owns the workspace.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user who created the workspace.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the folders in this workspace.
     */
    public function folders(): HasMany
    {
        return $this->hasMany(WorkspaceFolder::class);
    }

    /**
     * Get root-level folders (folders with no parent).
     */
    public function rootFolders(): HasMany
    {
        return $this->hasMany(WorkspaceFolder::class)->whereNull('parent_folder_id');
    }

    /**
     * Check if workspace is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if workspace is archived.
     */
    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    /**
     * Archive the workspace.
     */
    public function archive(): void
    {
        $this->update(['status' => self::STATUS_ARCHIVED]);
    }

    /**
     * Activate the workspace.
     */
    public function activate(): void
    {
        $this->update(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * Scope a query to only include active workspaces.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope a query to only include archived workspaces.
     */
    public function scopeArchived($query)
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }

    /**
     * Scope a query to filter by account.
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope a query to search by name or description.
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('workspace_name', 'like', "%{$term}%")
              ->orWhere('workspace_description', 'like', "%{$term}%");
        });
    }
}
