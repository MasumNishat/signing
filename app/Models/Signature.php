<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Signature extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'signatures';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'account_id',
        'user_id',
        'signature_id',
        'signature_type',
        'signature_name',
        'status',
        'font_style',
        'phone_number',
        'stamp_type',
        'stamp_size_mm',
        'adopted_date_time',
        'created_date_time',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'adopted_date_time' => 'datetime',
        'created_date_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Signature type constants.
     */
    const TYPE_SIGNATURE = 'signature';
    const TYPE_INITIALS = 'initials';
    const TYPE_STAMP = 'stamp';

    /**
     * Status constants.
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_CLOSED = 'closed';

    /**
     * Font style constants.
     */
    const FONT_LUCIDA_CONSOLE = 'lucida_console';
    const FONT_LUCIDA_HANDWRITING = 'lucida_handwriting';
    const FONT_BRAVURA = 'bravura';
    const FONT_RAGE_ITALIC = 'rage_italic';
    const FONT_MONOTYPE_CORSIVA = 'monotype_corsiva';
    const FONT_SEGOE_SCRIPT = 'segoe_script';

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($signature) {
            if (empty($signature->signature_id)) {
                $signature->signature_id = (string) Str::uuid();
            }
            if (empty($signature->created_date_time)) {
                $signature->created_date_time = now();
            }
        });
    }

    /**
     * Get the account that owns the signature.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Get the user that owns the signature.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the signature images.
     */
    public function images(): HasMany
    {
        return $this->hasMany(SignatureImage::class, 'signature_id');
    }

    /**
     * Get the signature image.
     */
    public function signatureImage()
    {
        return $this->hasOne(SignatureImage::class, 'signature_id')
            ->where('image_type', SignatureImage::TYPE_SIGNATURE);
    }

    /**
     * Get the initials image.
     */
    public function initialsImage()
    {
        return $this->hasOne(SignatureImage::class, 'signature_id')
            ->where('image_type', SignatureImage::TYPE_INITIALS);
    }

    /**
     * Get the stamp image.
     */
    public function stampImage()
    {
        return $this->hasOne(SignatureImage::class, 'signature_id')
            ->where('image_type', SignatureImage::TYPE_STAMP);
    }

    /**
     * Check if signature is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if signature is closed.
     */
    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    /**
     * Check if signature is adopted.
     */
    public function isAdopted(): bool
    {
        return $this->adopted_date_time !== null;
    }

    /**
     * Mark signature as adopted.
     */
    public function markAsAdopted(): void
    {
        $this->adopted_date_time = now();
        $this->save();
    }

    /**
     * Close the signature.
     */
    public function close(): void
    {
        $this->status = self::STATUS_CLOSED;
        $this->save();
    }

    /**
     * Scope a query to only include signatures of a given type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('signature_type', $type);
    }

    /**
     * Scope a query to only include active signatures.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope a query to only include closed signatures.
     */
    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    /**
     * Scope a query to only include adopted signatures.
     */
    public function scopeAdopted($query)
    {
        return $query->whereNotNull('adopted_date_time');
    }

    /**
     * Scope a query to only include signatures for a specific account.
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope a query to only include signatures for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
