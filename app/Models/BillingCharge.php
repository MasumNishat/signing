<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BillingCharge Model
 *
 * Represents a billing charge for an account.
 * Charge types: seat, envelope, storage.
 *
 * @property int $id
 * @property int $account_id
 * @property string $charge_type
 * @property string $charge_name
 * @property float|null $unit_price
 * @property int $quantity
 * @property int $incremental_quantity
 * @property bool $blocked
 * @property array|null $chargeable_items
 * @property array|null $discount_information
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Account $account
 */
class BillingCharge extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'billing_charges';

    /**
     * Charge type constants
     */
    public const CHARGE_TYPE_SEAT = 'seat';
    public const CHARGE_TYPE_ENVELOPE = 'envelope';
    public const CHARGE_TYPE_STORAGE = 'storage';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'account_id',
        'charge_type',
        'charge_name',
        'unit_price',
        'quantity',
        'incremental_quantity',
        'blocked',
        'chargeable_items',
        'discount_information',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity' => 'integer',
        'incremental_quantity' => 'integer',
        'blocked' => 'boolean',
        'chargeable_items' => 'array',
        'discount_information' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($charge) {
            if ($charge->quantity === null) {
                $charge->quantity = 0;
            }

            if ($charge->incremental_quantity === null) {
                $charge->incremental_quantity = 0;
            }

            if ($charge->blocked === null) {
                $charge->blocked = false;
            }
        });
    }

    /**
     * Get the account that owns the charge
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Calculate total charge amount
     */
    public function calculateTotal(): float
    {
        if (!$this->unit_price || $this->blocked) {
            return 0;
        }

        $totalQuantity = $this->quantity + $this->incremental_quantity;
        $subtotal = $totalQuantity * $this->unit_price;

        // Apply discount if present
        if (!empty($this->discount_information) && isset($this->discount_information['percentage'])) {
            $discountPercent = $this->discount_information['percentage'];
            $subtotal = $subtotal * (1 - ($discountPercent / 100));
        }

        return round($subtotal, 2);
    }

    /**
     * Check if charge is blocked
     */
    public function isBlocked(): bool
    {
        return $this->blocked;
    }

    /**
     * Scope: Filter by account
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope: Filter by charge type
     */
    public function scopeOfType($query, string $chargeType)
    {
        return $query->where('charge_type', $chargeType);
    }

    /**
     * Scope: Not blocked
     */
    public function scopeActive($query)
    {
        return $query->where('blocked', false);
    }
}
