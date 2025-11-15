<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * BillingPayment Model
 *
 * Represents a payment made against an invoice or account.
 *
 * @property int $id
 * @property int $account_id
 * @property string $payment_id
 * @property int|null $invoice_id
 * @property \Carbon\Carbon $payment_date
 * @property float $payment_amount
 * @property string|null $payment_method
 * @property string $status
 * @property string|null $transaction_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Account $account
 * @property-read BillingInvoice|null $invoice
 */
class BillingPayment extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'billing_payments';

    /**
     * Payment method constants
     */
    public const METHOD_CREDIT_CARD = 'credit_card';
    public const METHOD_ACH = 'ach';
    public const METHOD_WIRE = 'wire';

    /**
     * Payment status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'account_id',
        'payment_id',
        'invoice_id',
        'payment_date',
        'payment_amount',
        'payment_method',
        'status',
        'transaction_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'payment_date' => 'date',
        'payment_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->payment_id)) {
                $payment->payment_id = 'pay-' . Str::uuid()->toString();
            }

            if ($payment->status === null) {
                $payment->status = self::STATUS_PENDING;
            }
        });

        static::updated(function ($payment) {
            // Recalculate invoice balance when payment status changes
            if ($payment->isDirty('status') && $payment->invoice_id) {
                $payment->invoice->recalculateBalance();
            }
        });
    }

    /**
     * Get the account that owns the payment
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Get the invoice this payment applies to
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(BillingInvoice::class, 'invoice_id');
    }

    /**
     * Check if payment is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if payment failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Mark payment as completed
     */
    public function markAsCompleted(): void
    {
        $this->update(['status' => self::STATUS_COMPLETED]);
    }

    /**
     * Mark payment as failed
     */
    public function markAsFailed(): void
    {
        $this->update(['status' => self::STATUS_FAILED]);
    }

    /**
     * Scope: Filter by account
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope: Filter by invoice
     */
    public function scopeForInvoice($query, int $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Completed payments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }
}
