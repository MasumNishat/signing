<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * WorkspaceFolder Model
 *
 * Represents a folder within a workspace.
 * Folders can be nested hierarchically using parent_folder_id.
 *
 * @property int $id
 * @property int $workspace_id
 * @property string $folder_id
 * @property int|null $parent_folder_id
 * @property string $folder_name
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Workspace $workspace
 * @property-read WorkspaceFolder|null $parentFolder
 * @property-read \Illuminate\Database\Eloquent\Collection|WorkspaceFolder[] $subfolders
 * @property-read \Illuminate\Database\Eloquent\Collection|WorkspaceFile[] $files
 */
class WorkspaceFolder extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'workspace_folders';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'workspace_id',
        'folder_id',
        'parent_folder_id',
        'folder_name',
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

        static::creating(function ($folder) {
            if (empty($folder->folder_id)) {
                $folder->folder_id = 'folder-' . Str::uuid()->toString();
            }
        });
    }

    /**
     * Get the workspace that owns the folder.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the parent folder.
     */
    public function parentFolder(): BelongsTo
    {
        return $this->belongsTo(WorkspaceFolder::class, 'parent_folder_id');
    }

    /**
     * Get the child folders (subfolders).
     */
    public function subfolders(): HasMany
    {
        return $this->hasMany(WorkspaceFolder::class, 'parent_folder_id');
    }

    /**
     * Get the files in this folder.
     */
    public function files(): HasMany
    {
        return $this->hasMany(WorkspaceFile::class, 'folder_id');
    }

    /**
     * Check if this is a root folder (no parent).
     */
    public function isRoot(): bool
    {
        return $this->parent_folder_id === null;
    }

    /**
     * Check if folder has subfolders.
     */
    public function hasSubfolders(): bool
    {
        return $this->subfolders()->exists();
    }

    /**
     * Check if folder has files.
     */
    public function hasFiles(): bool
    {
        return $this->files()->exists();
    }

    /**
     * Check if folder is empty (no files and no subfolders).
     */
    public function isEmpty(): bool
    {
        return !$this->hasFiles() && !$this->hasSubfolders();
    }

    /**
     * Get the full path of the folder (e.g., "Parent/Child/Grandchild").
     */
    public function getPath(): string
    {
        $path = [$this->folder_name];
        $folder = $this;

        while ($folder->parentFolder) {
            $folder = $folder->parentFolder;
            array_unshift($path, $folder->folder_name);
        }

        return implode('/', $path);
    }

    /**
     * Scope a query to only include root folders.
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_folder_id');
    }

    /**
     * Scope a query to filter by workspace.
     */
    public function scopeForWorkspace($query, int $workspaceId)
    {
        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Scope a query to filter by parent folder.
     */
    public function scopeInFolder($query, int $parentFolderId)
    {
        return $query->where('parent_folder_id', $parentFolderId);
    }

    /**
     * Scope a query to search by folder name.
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where('folder_name', 'like', "%{$term}%");
    }
}
