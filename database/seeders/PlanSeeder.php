<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'plan_id' => 'plan_free',
                'plan_name' => 'Free',
                'is_free' => true,
                'envelope_allowance' => 5,
                'price_per_envelope' => 0.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'plan_id' => 'plan_personal',
                'plan_name' => 'Personal',
                'is_free' => false,
                'envelope_allowance' => 100,
                'price_per_envelope' => 0.50,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'plan_id' => 'plan_business',
                'plan_name' => 'Business',
                'is_free' => false,
                'envelope_allowance' => 500,
                'price_per_envelope' => 0.30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'plan_id' => 'plan_enterprise',
                'plan_name' => 'Enterprise',
                'is_free' => false,
                'envelope_allowance' => null, // Unlimited
                'price_per_envelope' => 0.10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($plans as $plan) {
            \DB::table('plans')->insert($plan);
        }
    }
}
