<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BrandEmailContent Model
 *
 * Represents email content customization for a brand.
 * Allows customizing email templates with branded content and links.
 *
 * @property int $id
 * @property int $brand_id
 * @property string $email_content_type
 * @property string|null $content
 * @property string|null $email_to_link
 * @property string|null $link_text
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Brand $brand
 */
class BrandEmailContent extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'brand_email_contents';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'brand_id',
        'email_content_type',
        'content',
        'email_to_link',
        'link_text',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the brand that owns this email content
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * Check if has custom content
     */
    public function hasContent(): bool
    {
        return !empty($this->content);
    }

    /**
     * Check if has link
     */
    public function hasLink(): bool
    {
        return !empty($this->email_to_link);
    }

    /**
     * Scope: Filter by content type
     */
    public function scopeOfType($query, string $contentType)
    {
        return $query->where('email_content_type', $contentType);
    }

    /**
     * Scope: Filter by brand
     */
    public function scopeForBrand($query, int $brandId)
    {
        return $query->where('brand_id', $brandId);
    }
}
