<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BrandLogo Model
 *
 * Represents a logo file for a brand.
 * Supports multiple logo types: primary, secondary, email.
 *
 * @property int $id
 * @property int $brand_id
 * @property string $logo_type
 * @property string $file_path
 * @property string|null $file_name
 * @property string|null $mime_type
 * @property int|null $file_size
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Brand $brand
 */
class BrandLogo extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'brand_logos';

    /**
     * Logo type constants
     */
    public const LOGO_TYPE_PRIMARY = 'primary';
    public const LOGO_TYPE_SECONDARY = 'secondary';
    public const LOGO_TYPE_EMAIL = 'email';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'brand_id',
        'logo_type',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'file_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the brand that owns this logo
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * Get the logo file URL
     */
    public function getFileUrl(): string
    {
        return asset('storage/' . $this->file_path);
    }

    /**
     * Get logo file size in human-readable format
     */
    public function getFileSizeFormatted(): string
    {
        if (!$this->file_size) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Check if file exists
     */
    public function fileExists(): bool
    {
        return file_exists(storage_path('app/public/' . $this->file_path));
    }

    /**
     * Scope: Filter by logo type
     */
    public function scopeOfType($query, string $logoType)
    {
        return $query->where('logo_type', $logoType);
    }

    /**
     * Scope: Filter by brand
     */
    public function scopeForBrand($query, int $brandId)
    {
        return $query->where('brand_id', $brandId);
    }
}
