<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * WorkspaceFile Model
 *
 * Represents a file stored within a workspace folder.
 * Files belong to a specific folder and can be used for document management.
 *
 * @property int $id
 * @property int $folder_id
 * @property string $file_id
 * @property string $file_name
 * @property string|null $file_uri
 * @property int|null $file_size
 * @property string|null $content_type
 * @property int|null $created_by_user_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read WorkspaceFolder $folder
 * @property-read User|null $createdBy
 */
class WorkspaceFile extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'workspace_files';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'folder_id',
        'file_id',
        'file_name',
        'file_uri',
        'file_size',
        'content_type',
        'created_by_user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'file_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot function from Laravel.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($file) {
            if (empty($file->file_id)) {
                $file->file_id = 'file-' . Str::uuid()->toString();
            }
        });
    }

    /**
     * Get the folder that owns the file.
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(WorkspaceFolder::class, 'folder_id');
    }

    /**
     * Get the workspace (through folder).
     */
    public function workspace()
    {
        return $this->hasOneThrough(
            Workspace::class,
            WorkspaceFolder::class,
            'id', // Foreign key on workspace_folders table
            'id', // Foreign key on workspaces table
            'folder_id', // Local key on workspace_files table
            'workspace_id' // Local key on workspace_folders table
        );
    }

    /**
     * Get the user who created the file.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the file extension.
     */
    public function getExtension(): string
    {
        return pathinfo($this->file_name, PATHINFO_EXTENSION);
    }

    /**
     * Get the file name without extension.
     */
    public function getBaseName(): string
    {
        return pathinfo($this->file_name, PATHINFO_FILENAME);
    }

    /**
     * Get human-readable file size.
     */
    public function getFileSizeFormatted(): string
    {
        if ($this->file_size === null) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Get the file URL.
     */
    public function getFileUrl(): ?string
    {
        if (!$this->file_uri) {
            return null;
        }

        // If URI is already a full URL, return it
        if (filter_var($this->file_uri, FILTER_VALIDATE_URL)) {
            return $this->file_uri;
        }

        // Otherwise, assume it's a storage path
        return asset('storage/' . $this->file_uri);
    }

    /**
     * Check if file is an image.
     */
    public function isImage(): bool
    {
        if (!$this->content_type) {
            return false;
        }

        return str_starts_with($this->content_type, 'image/');
    }

    /**
     * Check if file is a PDF.
     */
    public function isPdf(): bool
    {
        return $this->content_type === 'application/pdf';
    }

    /**
     * Check if file is a document.
     */
    public function isDocument(): bool
    {
        if (!$this->content_type) {
            return false;
        }

        $documentTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
        ];

        return in_array($this->content_type, $documentTypes);
    }

    /**
     * Scope a query to filter by folder.
     */
    public function scopeInFolder($query, int $folderId)
    {
        return $query->where('folder_id', $folderId);
    }

    /**
     * Scope a query to search by file name.
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where('file_name', 'like', "%{$term}%");
    }

    /**
     * Scope a query to filter by content type.
     */
    public function scopeOfType($query, string $contentType)
    {
        return $query->where('content_type', $contentType);
    }

    /**
     * Scope a query to only include images.
     */
    public function scopeImages($query)
    {
        return $query->where('content_type', 'like', 'image/%');
    }

    /**
     * Scope a query to only include PDFs.
     */
    public function scopePdfs($query)
    {
        return $query->where('content_type', 'application/pdf');
    }
}
