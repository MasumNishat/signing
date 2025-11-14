<?php

namespace Database\Factories;

use App\Models\PermissionProfile;
use App\Models\Account;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PermissionProfile>
 */
class PermissionProfileFactory extends Factory
{
    protected $model = PermissionProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $role = UserRole::SENDER; // Default role

        return [
            'account_id' => Account::factory(),
            'permission_profile_id' => 'perm_' . $this->faker->unique()->bothify('??##??##??##??##'),
            'permission_profile_name' => $role->label(),
            'permissions' => $role->permissionsArray(),
        ];
    }

    /**
     * Create a profile for a specific role.
     */
    public function role(UserRole $role): static
    {
        return $this->state(fn (array $attributes) => [
            'permission_profile_name' => $role->label(),
            'permissions' => $role->permissionsArray(),
        ]);
    }

    /**
     * Create an admin profile.
     */
    public function admin(): static
    {
        return $this->role(UserRole::ACCOUNT_ADMIN);
    }

    /**
     * Create a sender profile.
     */
    public function sender(): static
    {
        return $this->role(UserRole::SENDER);
    }

    /**
     * Create a signer profile.
     */
    public function signer(): static
    {
        return $this->role(UserRole::SIGNER);
    }
}
