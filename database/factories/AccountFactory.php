<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plan = Plan::where('is_free', true)->first();

        return [
            'plan_id' => $plan->id,
            'account_id' => 'acc_' . $this->faker->unique()->bothify('??##??##??##??##'),
            'account_name' => $this->faker->company(),
            'organization' => $this->faker->company(),
            'is_downgrade' => false,
            'billing_period_start_date' => now()->startOfMonth(),
            'billing_period_end_date' => now()->endOfMonth(),
            'billing_period_envelopes_sent' => $this->faker->numberBetween(0, 50),
            'billing_period_envelopes_allowed' => $plan->envelope_allowance,
            'can_upgrade' => true,
            'current_plan_id' => $plan->plan_id,
            'distributor_code' => null,
            'account_id_guid' => $this->faker->uuid(),
            'currency_code' => 'USD',
            'seat_discounts' => null,
            'plan_start_date' => now()->subMonths(3),
            'plan_end_date' => null,
            'suspension_status' => null,
            'created_date' => now(),
        ];
    }

    /**
     * Indicate that the account is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'suspension_status' => 'suspended',
        ]);
    }

    /**
     * Indicate that the account has unlimited envelopes.
     */
    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_period_envelopes_allowed' => null,
        ]);
    }
}
