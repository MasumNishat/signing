<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ConnectOAuthConfig Model
 *
 * Represents OAuth configuration for a Connect webhook.
 * One-to-one relationship with Account (each account has max 1 OAuth config).
 *
 * @property int $id
 * @property int $account_id
 * @property int|null $connect_id
 * @property string|null $oauth_client_id
 * @property string|null $oauth_token_endpoint
 * @property string|null $oauth_authorization_endpoint
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ConnectOAuthConfig extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'connect_oauth_config';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'account_id',
        'connect_id',
        'oauth_client_id',
        'oauth_token_endpoint',
        'oauth_authorization_endpoint',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the account that owns the OAuth config
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /**
     * Get the connect configuration
     */
    public function connectConfiguration(): BelongsTo
    {
        return $this->belongsTo(ConnectConfiguration::class, 'connect_id');
    }

    /**
     * Check if OAuth is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->oauth_client_id)
            && !empty($this->oauth_token_endpoint)
            && !empty($this->oauth_authorization_endpoint);
    }

    /**
     * Scope: Filter by account
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope: Only configured OAuth configs
     */
    public function scopeConfigured($query)
    {
        return $query->whereNotNull('oauth_client_id')
            ->whereNotNull('oauth_token_endpoint')
            ->whereNotNull('oauth_authorization_endpoint');
    }
}
