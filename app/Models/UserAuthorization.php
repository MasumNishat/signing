<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAuthorization extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'principal_user_id',
        'agent_user_id',
        'permissions',
        'start_date',
        'end_date',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the account that owns the authorization.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the principal user (the one granting permission).
     */
    public function principal()
    {
        return $this->belongsTo(User::class, 'principal_user_id');
    }

    /**
     * Get the agent user (the one receiving permission).
     */
    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_user_id');
    }

    /**
     * Determine if the authorization is currently valid.
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->start_date && $this->start_date->isFuture()) {
            return false;
        }

        if ($this->end_date && $this->end_date->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if the authorization has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        return in_array($permission, $this->permissions ?? []) || in_array('*', $this->permissions ?? []);
    }
}
