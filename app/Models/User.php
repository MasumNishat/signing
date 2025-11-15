<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'user_name',
        'email',
        'password',
        'first_name',
        'middle_name',
        'last_name',
        'suffix_name',
        'title',
        'job_title',
        'country_code',
        'user_status',
        'user_type',
        'login_status',
        'is_admin',
        'activation_access_code',
        'send_activation_email',
        'send_activation_on_invalid_login',
        'password_expiration',
        'last_login',
        'permission_profile_id',
        'enable_connect_for_user',
        'subscribe',
        'created_datetime',
        'user_profile_last_modified_date',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'activation_access_code',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'send_activation_email' => 'boolean',
            'send_activation_on_invalid_login' => 'boolean',
            'enable_connect_for_user' => 'boolean',
            'subscribe' => 'boolean',
            'password_expiration' => 'datetime',
            'last_login' => 'datetime',
            'created_datetime' => 'datetime',
            'user_profile_last_modified_date' => 'datetime',
        ];
    }

    /**
     * Get the account that owns the user.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the permission profile for the user.
     */
    public function permissionProfile()
    {
        return $this->belongsTo(PermissionProfile::class);
    }

    /**
     * Get the addresses for the user.
     */
    public function addresses()
    {
        return $this->hasMany(UserAddress::class);
    }

    /**
     * Get the API keys for the user.
     */
    public function apiKeys()
    {
        return $this->hasMany(ApiKey::class);
    }

    /**
     * Get the authorizations where this user is the principal.
     */
    public function grantedAuthorizations()
    {
        return $this->hasMany(UserAuthorization::class, 'principal_user_id');
    }

    /**
     * Get the authorizations where this user is the agent.
     */
    public function receivedAuthorizations()
    {
        return $this->hasMany(UserAuthorization::class, 'agent_user_id');
    }

    /**
     * Get the contacts for the user.
     */
    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * Get the custom settings for the user.
     */
    public function customSettings()
    {
        return $this->hasMany(UserCustomSetting::class);
    }

    /**
     * Get the profile for the user.
     */
    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * Get the settings for the user.
     */
    public function settings()
    {
        return $this->hasOne(UserSetting::class);
    }

    /**
     * Determine if the user is active.
     */
    public function isActive(): bool
    {
        return $this->user_status === 'active';
    }

    /**
     * Determine if the user is an administrator.
     */
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        $parts = array_filter([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
            $this->suffix_name,
        ]);

        return implode(' ', $parts) ?: $this->user_name;
    }
}
