<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AccountCustomField extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'field_id',
        'name',
        'display_name',
        'description',
        'field_type',
        'list_items',
        'required',
        'show',
        'max_length',
        'order',
    ];

    protected $casts = [
        'list_items' => 'array',
        'required' => 'boolean',
        'show' => 'boolean',
        'max_length' => 'integer',
        'order' => 'integer',
    ];

    const TYPE_TEXT = 'text';
    const TYPE_LIST = 'list';

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($field) {
            if (empty($field->field_id)) {
                $field->field_id = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the account that owns the custom field.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Scope a query to only include fields for a specific account.
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope a query to only include visible fields.
     */
    public function scopeVisible($query)
    {
        return $query->where('show', true);
    }

    /**
     * Scope a query to order by the order field.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
