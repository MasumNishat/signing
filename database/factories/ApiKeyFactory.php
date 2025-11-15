<?php

namespace Database\Factories;

use App\Models\ApiKey;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApiKey>
 */
class ApiKeyFactory extends Factory
{
    protected $model = ApiKey::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generate a test API key
        $apiKey = ApiKey::generate();
        $keyHash = ApiKey::hashKey($apiKey);

        return [
            'account_id' => Account::factory(),
            'user_id' => User::factory(),
            'key_hash' => $keyHash,
            'name' => $this->faker->words(3, true) . ' API Key',
            'scopes' => null, // null means full access
            'last_used_at' => null,
            'expires_at' => now()->addYear(),
            'revoked' => false,
        ];
    }

    /**
     * Indicate that the API key is revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked' => true,
        ]);
    }

    /**
     * Indicate that the API key is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }

    /**
     * Set specific scopes for the API key.
     */
    public function withScopes(array $scopes): static
    {
        return $this->state(fn (array $attributes) => [
            'scopes' => $scopes,
        ]);
    }
}
