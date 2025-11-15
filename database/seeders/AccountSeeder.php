<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get plan_id from the plans table
        $businessPlanId = \DB::table('plans')->where('plan_id', 'plan_business')->value('id');

        $accounts = [
            [
                'plan_id' => $businessPlanId,
                'account_id' => 'acc_demo_' . \Str::random(16),
                'account_name' => 'Demo Account',
                'status' => 'active',
                'is_suspended' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'plan_id' => $businessPlanId,
                'account_id' => 'acc_test_' . \Str::random(16),
                'account_name' => 'Test Account',
                'status' => 'active',
                'is_suspended' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($accounts as $account) {
            \DB::table('accounts')->insert($account);
        }
    }
}
