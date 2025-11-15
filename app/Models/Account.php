<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'plan_id',
        'account_id',
        'account_name',
        'organization',
        'is_downgrade',
        'billing_period_start_date',
        'billing_period_end_date',
        'billing_period_envelopes_sent',
        'billing_period_envelopes_allowed',
        'can_upgrade',
        'current_plan_id',
        'distributor_code',
        'account_id_guid',
        'currency_code',
        'seat_discounts',
        'plan_start_date',
        'plan_end_date',
        'suspension_status',
        'created_date',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_downgrade' => 'boolean',
            'can_upgrade' => 'boolean',
            'billing_period_start_date' => 'date',
            'billing_period_end_date' => 'date',
            'billing_period_envelopes_sent' => 'integer',
            'billing_period_envelopes_allowed' => 'integer',
            'seat_discounts' => 'array',
            'plan_start_date' => 'date',
            'plan_end_date' => 'date',
            'created_date' => 'datetime',
        ];
    }

    /**
     * Get the plan that the account belongs to.
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the users for the account.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the permission profiles for the account.
     */
    public function permissionProfiles()
    {
        return $this->hasMany(PermissionProfile::class);
    }

    /**
     * Get the envelopes for the account.
     */
    public function envelopes()
    {
        return $this->hasMany(Envelope::class);
    }

    /**
     * Get the templates for the account.
     */
    public function templates()
    {
        return $this->hasMany(Template::class);
    }

    /**
     * Get the brands for the account.
     */
    public function brands()
    {
        return $this->hasMany(Brand::class);
    }

    /**
     * Get the account settings.
     */
    public function settings()
    {
        return $this->hasOne(AccountSetting::class);
    }

    /**
     * Determine if the account is active.
     */
    public function isActive(): bool
    {
        return $this->suspension_status === null || $this->suspension_status === 'active';
    }

    /**
     * Determine if the account can send more envelopes.
     */
    public function canSendEnvelope(): bool
    {
        if ($this->billing_period_envelopes_allowed === null) {
            return true; // Unlimited
        }

        return $this->billing_period_envelopes_sent < $this->billing_period_envelopes_allowed;
    }

    /**
     * Get remaining envelopes for the billing period.
     */
    public function getRemainingEnvelopesAttribute(): ?int
    {
        if ($this->billing_period_envelopes_allowed === null) {
            return null; // Unlimited
        }

        return max(0, $this->billing_period_envelopes_allowed - $this->billing_period_envelopes_sent);
    }
}
