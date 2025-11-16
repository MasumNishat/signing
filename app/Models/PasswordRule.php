<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasswordRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'password_strength_type',
        'minimum_password_length',
        'maximum_password_age_days',
        'minimum_password_age_days',
        'password_include_digit',
        'password_include_lower_case',
        'password_include_upper_case',
        'password_include_special_character',
        'password_include_digit_or_special_character',
        'lockout_duration_minutes',
        'lockout_duration_type',
        'failed_login_attempts',
        'questions_required',
    ];

    protected $casts = [
        'minimum_password_length' => 'integer',
        'maximum_password_age_days' => 'integer',
        'minimum_password_age_days' => 'integer',
        'password_include_digit' => 'boolean',
        'password_include_lower_case' => 'boolean',
        'password_include_upper_case' => 'boolean',
        'password_include_special_character' => 'boolean',
        'password_include_digit_or_special_character' => 'boolean',
        'lockout_duration_minutes' => 'integer',
        'failed_login_attempts' => 'integer',
        'questions_required' => 'integer',
    ];

    const STRENGTH_WEAK = 'weak';
    const STRENGTH_MEDIUM = 'medium';
    const STRENGTH_STRONG = 'strong';

    const LOCKOUT_MINUTES = 'minutes';
    const LOCKOUT_HOURS = 'hours';
    const LOCKOUT_DAYS = 'days';

    /**
     * Get the account that owns the password rules.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the password strength level.
     */
    public function getStrengthLevel(): string
    {
        return $this->password_strength_type;
    }

    /**
     * Check if password requires a digit.
     */
    public function requiresDigit(): bool
    {
        return $this->password_include_digit;
    }

    /**
     * Check if password requires a lowercase character.
     */
    public function requiresLowerCase(): bool
    {
        return $this->password_include_lower_case;
    }

    /**
     * Check if password requires an uppercase character.
     */
    public function requiresUpperCase(): bool
    {
        return $this->password_include_upper_case;
    }

    /**
     * Check if password requires a special character.
     */
    public function requiresSpecialCharacter(): bool
    {
        return $this->password_include_special_character;
    }

    /**
     * Get lockout duration in minutes.
     */
    public function getLockoutDurationInMinutes(): int
    {
        return match ($this->lockout_duration_type) {
            self::LOCKOUT_HOURS => $this->lockout_duration_minutes * 60,
            self::LOCKOUT_DAYS => $this->lockout_duration_minutes * 1440,
            default => $this->lockout_duration_minutes,
        };
    }

    /**
     * Validate password against rules.
     */
    public function validatePassword(string $password): array
    {
        $errors = [];

        if (strlen($password) < $this->minimum_password_length) {
            $errors[] = "Password must be at least {$this->minimum_password_length} characters long";
        }

        if ($this->password_include_digit && !preg_match('/\d/', $password)) {
            $errors[] = "Password must include at least one digit";
        }

        if ($this->password_include_lower_case && !preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must include at least one lowercase letter";
        }

        if ($this->password_include_upper_case && !preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must include at least one uppercase letter";
        }

        if ($this->password_include_special_character && !preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = "Password must include at least one special character";
        }

        if ($this->password_include_digit_or_special_character) {
            if (!preg_match('/\d/', $password) && !preg_match('/[^a-zA-Z0-9]/', $password)) {
                $errors[] = "Password must include at least one digit or special character";
            }
        }

        return $errors;
    }
}
