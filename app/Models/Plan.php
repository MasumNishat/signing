<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'plan_id',
        'plan_name',
        'plan_feature_sets',
        'is_free',
        'envelope_allowance',
        'price_per_envelope',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'plan_feature_sets' => 'array',
            'is_free' => 'boolean',
            'envelope_allowance' => 'integer',
            'price_per_envelope' => 'decimal:2',
        ];
    }

    /**
     * Get the accounts with this plan.
     */
    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    /**
     * Determine if this is a free plan.
     */
    public function isFree(): bool
    {
        return $this->is_free;
    }

    /**
     * Determine if the plan has unlimited envelopes.
     */
    public function hasUnlimitedEnvelopes(): bool
    {
        return $this->envelope_allowance === null;
    }
}
