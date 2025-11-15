<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * BillingPlan Model
 *
 * Represents a billing plan definition with pricing and features.
 *
 * @property int $id
 * @property string $plan_id
 * @property string $plan_name
 * @property string|null $plan_classification
 * @property string $currency_code
 * @property float|null $per_seat_price
 * @property float|null $support_incident_fee
 * @property float|null $support_plan_fee
 * @property int $included_seats
 * @property bool $enable_support
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class BillingPlan extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'billing_plans';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'plan_id',
        'plan_name',
        'plan_classification',
        'currency_code',
        'per_seat_price',
        'support_incident_fee',
        'support_plan_fee',
        'included_seats',
        'enable_support',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'per_seat_price' => 'decimal:2',
        'support_incident_fee' => 'decimal:2',
        'support_plan_fee' => 'decimal:2',
        'included_seats' => 'integer',
        'enable_support' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($plan) {
            if (empty($plan->plan_id)) {
                $plan->plan_id = 'plan-' . Str::uuid()->toString();
            }

            if ($plan->currency_code === null) {
                $plan->currency_code = 'USD';
            }

            if ($plan->included_seats === null) {
                $plan->included_seats = 0;
            }

            if ($plan->enable_support === null) {
                $plan->enable_support = true;
            }
        });
    }

    /**
     * Calculate total cost for given number of seats
     */
    public function calculateCost(int $seats): float
    {
        if (!$this->per_seat_price) {
            return 0;
        }

        $additionalSeats = max(0, $seats - $this->included_seats);
        $seatCost = $additionalSeats * $this->per_seat_price;
        $supportCost = $this->enable_support ? ($this->support_plan_fee ?? 0) : 0;

        return round($seatCost + $supportCost, 2);
    }

    /**
     * Check if plan includes support
     */
    public function hasSupport(): bool
    {
        return $this->enable_support;
    }

    /**
     * Scope: Search by name
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where('plan_name', 'ILIKE', "%{$search}%")
            ->orWhere('plan_classification', 'ILIKE', "%{$search}%");
    }
}
