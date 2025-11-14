<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'user_id',
        'key_hash',
        'name',
        'scopes',
        'last_used_at',
        'expires_at',
        'revoked',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'key_hash',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked' => 'boolean',
        ];
    }

    /**
     * Get the account that owns the API key.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user that owns the API key.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a new API key.
     */
    public static function generate(): string
    {
        return 'sk_' . Str::random(40);
    }

    /**
     * Hash an API key for storage.
     */
    public static function hashKey(string $key): string
    {
        return hash('sha256', $key);
    }

    /**
     * Determine if the API key is valid.
     */
    public function isValid(): bool
    {
        if ($this->revoked) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the API key has a specific scope.
     */
    public function hasScope(string $scope): bool
    {
        if (empty($this->scopes)) {
            return true; // No scopes means full access
        }

        return in_array($scope, $this->scopes) || in_array('*', $this->scopes);
    }

    /**
     * Record that the API key was used.
     */
    public function recordUsage(): void
    {
        $this->update(['last_used_at' => now()]);
    }
}
