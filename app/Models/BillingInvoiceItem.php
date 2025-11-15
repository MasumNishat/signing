<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BillingInvoiceItem Model
 *
 * Represents a line item on a billing invoice.
 *
 * @property int $id
 * @property int $invoice_id
 * @property string $charge_type
 * @property string $charge_name
 * @property float|null $unit_price
 * @property int $quantity
 * @property float|null $subtotal
 * @property float $tax
 * @property float|null $total
 * @property \Carbon\Carbon $created_at
 *
 * @property-read BillingInvoice $invoice
 */
class BillingInvoiceItem extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'billing_invoice_items';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'invoice_id',
        'charge_type',
        'charge_name',
        'unit_price',
        'quantity',
        'subtotal',
        'tax',
        'total',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity' => 'integer',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            if ($item->quantity === null) {
                $item->quantity = 0;
            }

            if ($item->tax === null) {
                $item->tax = 0;
            }

            // Auto-calculate subtotal and total if not provided
            if ($item->subtotal === null && $item->unit_price !== null) {
                $item->subtotal = $item->unit_price * $item->quantity;
            }

            if ($item->total === null && $item->subtotal !== null) {
                $item->total = $item->subtotal + $item->tax;
            }

            $item->created_at = now();
        });
    }

    /**
     * Get the invoice that owns the item
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(BillingInvoice::class, 'invoice_id');
    }

    /**
     * Calculate subtotal
     */
    public function calculateSubtotal(): float
    {
        if (!$this->unit_price) {
            return 0;
        }

        return round($this->unit_price * $this->quantity, 2);
    }

    /**
     * Calculate total (subtotal + tax)
     */
    public function calculateTotal(): float
    {
        $subtotal = $this->subtotal ?? $this->calculateSubtotal();
        return round($subtotal + $this->tax, 2);
    }

    /**
     * Scope: Filter by invoice
     */
    public function scopeForInvoice($query, int $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }

    /**
     * Scope: Filter by charge type
     */
    public function scopeOfType($query, string $chargeType)
    {
        return $query->where('charge_type', $chargeType);
    }
}
