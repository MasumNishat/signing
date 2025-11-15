<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnoteConfiguration extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'account_id',
        'api_key',
        'connect_username',
        'connect_password',
        'connect_config_name',
        'org_id',
        'user_id',
    ];

    protected $hidden = [
        'api_key',
        'connect_password',
    ];

    /**
     * Get the account that owns the eNote configuration.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Check if configuration is complete.
     */
    public function isConfigured(): bool
    {
        return !empty($this->api_key) && !empty($this->org_id) && !empty($this->user_id);
    }
}
