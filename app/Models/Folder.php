<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Folder extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'folders';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'account_id',
        'folder_id',
        'folder_name',
        'folder_type',
        'owner_user_id',
        'parent_folder_id',
        'filter',
        'uri',
        'item_count',
        'sub_folder_count',
        'has_sub_folders',
        'error_details',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'item_count' => 'integer',
        'sub_folder_count' => 'integer',
        'has_sub_folders' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Folder type constants.
     */
    const TYPE_NORMAL = 'normal';
    const TYPE_INBOX = 'inbox';
    const TYPE_SENT_ITEMS = 'sentitems';
    const TYPE_DRAFT = 'draft';
    const TYPE_TRASH = 'trash';
    const TYPE_RECYCLE_BIN = 'recyclebin';
    const TYPE_CUSTOM = 'custom';

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($folder) {
            if (empty($folder->folder_id)) {
                $folder->folder_id = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the account that owns the folder.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Get the owner user of the folder.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * Get the parent folder.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'parent_folder_id');
    }

    /**
     * Get the child folders.
     */
    public function children()
    {
        return $this->hasMany(Folder::class, 'parent_folder_id');
    }

    /**
     * Get all envelopes in this folder.
     */
    public function envelopes(): BelongsToMany
    {
        return $this->belongsToMany(Envelope::class, 'envelope_folders', 'folder_id', 'envelope_id')
            ->withTimestamps();
    }

    /**
     * Check if folder is a system folder.
     */
    public function isSystemFolder(): bool
    {
        return in_array($this->folder_type, [
            self::TYPE_INBOX,
            self::TYPE_SENT_ITEMS,
            self::TYPE_DRAFT,
            self::TYPE_TRASH,
            self::TYPE_RECYCLE_BIN,
        ]);
    }

    /**
     * Check if folder is custom.
     */
    public function isCustomFolder(): bool
    {
        return $this->folder_type === self::TYPE_CUSTOM;
    }

    /**
     * Check if folder is normal.
     */
    public function isNormalFolder(): bool
    {
        return $this->folder_type === self::TYPE_NORMAL || $this->folder_type === self::TYPE_CUSTOM;
    }

    /**
     * Update item count.
     */
    public function updateItemCount(): void
    {
        $this->item_count = $this->envelopes()->count();
        $this->save();
    }

    /**
     * Update subfolder count.
     */
    public function updateSubFolderCount(): void
    {
        $this->sub_folder_count = $this->children()->count();
        $this->has_sub_folders = $this->sub_folder_count > 0;
        $this->save();
    }

    /**
     * Scope a query to only include folders of a given type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('folder_type', $type);
    }

    /**
     * Scope a query to only include system folders.
     */
    public function scopeSystemFolders($query)
    {
        return $query->whereIn('folder_type', [
            self::TYPE_INBOX,
            self::TYPE_SENT_ITEMS,
            self::TYPE_DRAFT,
            self::TYPE_TRASH,
            self::TYPE_RECYCLE_BIN,
        ]);
    }

    /**
     * Scope a query to only include custom folders.
     */
    public function scopeCustomFolders($query)
    {
        return $query->whereIn('folder_type', [self::TYPE_NORMAL, self::TYPE_CUSTOM]);
    }

    /**
     * Scope a query to only include folders for a specific account.
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope a query to only include folders for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('owner_user_id', $userId);
    }

    /**
     * Scope a query to only include root folders (no parent).
     */
    public function scopeRootFolders($query)
    {
        return $query->whereNull('parent_folder_id');
    }

    /**
     * Scope a query to only include subfolders of a specific folder.
     */
    public function scopeSubFoldersOf($query, int $parentFolderId)
    {
        return $query->where('parent_folder_id', $parentFolderId);
    }
}
