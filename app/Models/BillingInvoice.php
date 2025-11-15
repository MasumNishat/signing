<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * BillingInvoice Model
 *
 * Represents a billing invoice for an account.
 *
 * @property int $id
 * @property int $account_id
 * @property string $invoice_id
 * @property string $invoice_number
 * @property \Carbon\Carbon $invoice_date
 * @property \Carbon\Carbon|null $due_date
 * @property float $balance
 * @property float $amount
 * @property float $tax_exempt_amount
 * @property float $non_tax_exempt_amount
 * @property string $currency_code
 * @property bool $pdf_available
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Account $account
 * @property-read \Illuminate\Database\Eloquent\Collection|BillingInvoiceItem[] $items
 * @property-read \Illuminate\Database\Eloquent\Collection|BillingPayment[] $payments
 */
class BillingInvoice extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'billing_invoices';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'account_id',
        'invoice_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'balance',
        'amount',
        'tax_exempt_amount',
        'non_tax_exempt_amount',
        'currency_code',
        'pdf_available',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'balance' => 'decimal:2',
        'amount' => 'decimal:2',
        'tax_exempt_amount' => 'decimal:2',
        'non_tax_exempt_amount' => 'decimal:2',
        'pdf_available' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_id)) {
                $invoice->invoice_id = 'inv-' . Str::uuid()->toString();
            }

            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = 'INV-' . strtoupper(Str::random(10));
            }

            if ($invoice->balance === null) {
                $invoice->balance = 0;
            }

            if ($invoice->tax_exempt_amount === null) {
                $invoice->tax_exempt_amount = 0;
            }

            if ($invoice->non_tax_exempt_amount === null) {
                $invoice->non_tax_exempt_amount = 0;
            }

            if ($invoice->currency_code === null) {
                $invoice->currency_code = 'USD';
            }

            if ($invoice->pdf_available === null) {
                $invoice->pdf_available = false;
            }
        });
    }

    /**
     * Get the account that owns the invoice
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Get all invoice items
     */
    public function items(): HasMany
    {
        return $this->hasMany(BillingInvoiceItem::class, 'invoice_id');
    }

    /**
     * Get all payments for this invoice
     */
    public function payments(): HasMany
    {
        return $this->hasMany(BillingPayment::class, 'invoice_id');
    }

    /**
     * Check if invoice is paid
     */
    public function isPaid(): bool
    {
        return $this->balance <= 0;
    }

    /**
     * Check if invoice is overdue
     */
    public function isOverdue(): bool
    {
        if (!$this->due_date || $this->isPaid()) {
            return false;
        }

        return $this->due_date->isPast();
    }

    /**
     * Calculate total paid amount
     */
    public function getTotalPaid(): float
    {
        return $this->payments()
            ->where('status', BillingPayment::STATUS_COMPLETED)
            ->sum('payment_amount');
    }

    /**
     * Update balance based on payments
     */
    public function recalculateBalance(): void
    {
        $totalPaid = $this->getTotalPaid();
        $this->balance = max(0, $this->amount - $totalPaid);
        $this->save();
    }

    /**
     * Scope: Filter by account
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope: Unpaid invoices
     */
    public function scopeUnpaid($query)
    {
        return $query->where('balance', '>', 0);
    }

    /**
     * Scope: Overdue invoices
     */
    public function scopeOverdue($query)
    {
        return $query->where('balance', '>', 0)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now());
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('invoice_date', [$startDate, $endDate]);
    }
}
