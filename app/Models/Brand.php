<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Brand Model
 *
 * Represents a brand profile for white-labeling and customization.
 * Brands allow accounts to customize the look and feel of emails and signing experiences.
 *
 * @property int $id
 * @property int $account_id
 * @property string $brand_id
 * @property string $brand_name
 * @property string|null $brand_company
 * @property bool $is_sending_default
 * @property bool $is_signing_default
 * @property bool $is_overriding_company_name
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read Account $account
 * @property-read \Illuminate\Database\Eloquent\Collection|BrandLogo[] $logos
 * @property-read \Illuminate\Database\Eloquent\Collection|BrandResource[] $resources
 * @property-read \Illuminate\Database\Eloquent\Collection|BrandEmailContent[] $emailContents
 */
class Brand extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'brands';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'account_id',
        'brand_id',
        'brand_name',
        'brand_company',
        'is_sending_default',
        'is_signing_default',
        'is_overriding_company_name',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_sending_default' => 'boolean',
        'is_signing_default' => 'boolean',
        'is_overriding_company_name' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($brand) {
            if (empty($brand->brand_id)) {
                $brand->brand_id = 'brand-' . Str::uuid()->toString();
            }

            if ($brand->is_sending_default === null) {
                $brand->is_sending_default = false;
            }

            if ($brand->is_signing_default === null) {
                $brand->is_signing_default = false;
            }

            if ($brand->is_overriding_company_name === null) {
                $brand->is_overriding_company_name = false;
            }
        });
    }

    /**
     * Get the account that owns the brand
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Get all logos for this brand
     */
    public function logos(): HasMany
    {
        return $this->hasMany(BrandLogo::class, 'brand_id');
    }

    /**
     * Get all resources for this brand
     */
    public function resources(): HasMany
    {
        return $this->hasMany(BrandResource::class, 'brand_id');
    }

    /**
     * Get all email contents for this brand
     */
    public function emailContents(): HasMany
    {
        return $this->hasMany(BrandEmailContent::class, 'brand_id');
    }

    /**
     * Get logo by type
     */
    public function getLogoByType(string $logoType): ?BrandLogo
    {
        return $this->logos()->where('logo_type', $logoType)->first();
    }

    /**
     * Get resource by content type
     */
    public function getResourceByType(string $contentType): ?BrandResource
    {
        return $this->resources()->where('resource_content_type', $contentType)->first();
    }

    /**
     * Get email content by type
     */
    public function getEmailContentByType(string $contentType): ?BrandEmailContent
    {
        return $this->emailContents()->where('email_content_type', $contentType)->first();
    }

    /**
     * Check if this brand is the sending default
     */
    public function isSendingDefault(): bool
    {
        return $this->is_sending_default;
    }

    /**
     * Check if this brand is the signing default
     */
    public function isSigningDefault(): bool
    {
        return $this->is_signing_default;
    }

    /**
     * Set as sending default (unset others)
     */
    public function setAsSendingDefault(): void
    {
        // Unset other sending defaults for this account
        self::where('account_id', $this->account_id)
            ->where('id', '!=', $this->id)
            ->update(['is_sending_default' => false]);

        $this->update(['is_sending_default' => true]);
    }

    /**
     * Set as signing default (unset others)
     */
    public function setAsSigningDefault(): void
    {
        // Unset other signing defaults for this account
        self::where('account_id', $this->account_id)
            ->where('id', '!=', $this->id)
            ->update(['is_signing_default' => false]);

        $this->update(['is_signing_default' => true]);
    }

    /**
     * Scope: Filter by account
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope: Get sending default brand
     */
    public function scopeSendingDefault($query, int $accountId)
    {
        return $query->where('account_id', $accountId)
            ->where('is_sending_default', true);
    }

    /**
     * Scope: Get signing default brand
     */
    public function scopeSigningDefault($query, int $accountId)
    {
        return $query->where('account_id', $accountId)
            ->where('is_signing_default', true);
    }

    /**
     * Scope: Search by name or company
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('brand_name', 'ILIKE', "%{$search}%")
              ->orWhere('brand_company', 'ILIKE', "%{$search}%");
        });
    }
}
