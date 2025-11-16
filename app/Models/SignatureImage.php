<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SignatureImage extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'signature_images';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'signature_id',
        'image_type',
        'file_path',
        'file_name',
        'mime_type',
        'include_chrome',
        'transparent_png',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'include_chrome' => 'boolean',
        'transparent_png' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Image type constants.
     */
    const TYPE_SIGNATURE = 'signature_image';
    const TYPE_INITIALS = 'initials_image';
    const TYPE_STAMP = 'stamp_image';

    /**
     * Get the signature that owns the image.
     */
    public function signature(): BelongsTo
    {
        return $this->belongsTo(Signature::class, 'signature_id');
    }

    /**
     * Get the full file URL.
     */
    public function getFileUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * Get the file content.
     */
    public function getFileContent()
    {
        return Storage::get($this->file_path);
    }

    /**
     * Check if file exists.
     */
    public function fileExists(): bool
    {
        return Storage::exists($this->file_path);
    }

    /**
     * Delete the file from storage.
     */
    public function deleteFile(): bool
    {
        if ($this->fileExists()) {
            return Storage::delete($this->file_path);
        }
        return true;
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Delete file when model is deleted
        static::deleting(function ($image) {
            $image->deleteFile();
        });
    }

    /**
     * Scope a query to only include images of a given type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('image_type', $type);
    }
}
