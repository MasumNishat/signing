<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BrandResource Model
 *
 * Represents a resource file for a brand.
 * Resource types: email, sending, signing, signing_captive.
 *
 * @property int $id
 * @property int $brand_id
 * @property string $resource_content_type
 * @property string $file_path
 * @property string|null $file_name
 * @property string|null $mime_type
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Brand $brand
 */
class BrandResource extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'brand_resources';

    /**
     * Resource content type constants
     */
    public const CONTENT_TYPE_EMAIL = 'email';
    public const CONTENT_TYPE_SENDING = 'sending';
    public const CONTENT_TYPE_SIGNING = 'signing';
    public const CONTENT_TYPE_SIGNING_CAPTIVE = 'signing_captive';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'brand_id',
        'resource_content_type',
        'file_path',
        'file_name',
        'mime_type',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the brand that owns this resource
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * Get the resource file URL
     */
    public function getFileUrl(): string
    {
        return asset('storage/' . $this->file_path);
    }

    /**
     * Check if file exists
     */
    public function fileExists(): bool
    {
        return file_exists(storage_path('app/public/' . $this->file_path));
    }

    /**
     * Scope: Filter by content type
     */
    public function scopeOfType($query, string $contentType)
    {
        return $query->where('resource_content_type', $contentType);
    }

    /**
     * Scope: Filter by brand
     */
    public function scopeForBrand($query, int $brandId)
    {
        return $query->where('brand_id', $brandId);
    }
}
