<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsumerDisclosure extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'language_code',
        'esign_text',
        'esign_agreement',
        'withdrawal_text',
        'acceptance_text',
        'allow_cd_withdraw',
        'withdraw_address_line_1',
        'withdraw_address_line_2',
        'withdraw_city',
        'withdraw_state',
        'withdraw_postal_code',
        'withdraw_country',
        'withdraw_email',
        'withdraw_phone',
        'withdraw_fax',
        'use_brand',
        'enable_esign',
        'pdf_id',
    ];

    protected $casts = [
        'allow_cd_withdraw' => 'boolean',
        'use_brand' => 'boolean',
        'enable_esign' => 'boolean',
    ];

    /**
     * Get the account that owns the consumer disclosure.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Scope a query to only include disclosures for a specific account.
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope a query to find disclosure by language code.
     */
    public function scopeByLanguage($query, string $langCode)
    {
        return $query->where('language_code', $langCode);
    }

    /**
     * Get the full withdrawal address.
     */
    public function getWithdrawAddressAttribute(): string
    {
        $parts = array_filter([
            $this->withdraw_address_line_1,
            $this->withdraw_address_line_2,
            $this->withdraw_city,
            $this->withdraw_state,
            $this->withdraw_postal_code,
            $this->withdraw_country,
        ]);

        return implode(', ', $parts);
    }
}
