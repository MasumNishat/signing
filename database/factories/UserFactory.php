<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password = null;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'user_name' => fake()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'first_name' => fake()->firstName(),
            'middle_name' => null,
            'last_name' => fake()->lastName(),
            'suffix_name' => null,
            'title' => fake()->jobTitle(),
            'job_title' => fake()->jobTitle(),
            'country_code' => 'US',
            'user_status' => 'active',
            'user_type' => 'user',
            'login_status' => 'not_logged_in',
            'is_admin' => false,
            'activation_access_code' => Str::random(32),
            'send_activation_email' => true,
            'send_activation_on_invalid_login' => false,
            'password_expiration' => now()->addMonths(3),
            'last_login' => null,
            'permission_profile_id' => null,
            'enable_connect_for_user' => false,
            'subscribe' => false,
            'created_datetime' => now(),
            'user_profile_last_modified_date' => null,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the user is an administrator.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_admin' => true,
            'user_type' => 'admin',
        ]);
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the user's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'activation_access_code' => Str::random(32),
        ]);
    }
}

