<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first account
        $demoAccount = \DB::table('accounts')->where('account_name', 'Demo Account')->first();

        if (!$demoAccount) {
            $this->command->warn('No demo account found. Skipping user seeding.');
            return;
        }

        $users = [
            [
                'account_id' => $demoAccount->id,
                'user_id' => 'user_admin_' . \Str::random(16),
                'email' => 'admin@demo.test',
                'user_name' => 'Admin User',
                'first_name' => 'Admin',
                'last_name' => 'User',
                'password' => \Hash::make('password'),
                'user_status' => 'active',
                'activation_access_code' => \Str::random(32),
                'created_date_time' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'account_id' => $demoAccount->id,
                'user_id' => 'user_sender_' . \Str::random(16),
                'email' => 'sender@demo.test',
                'user_name' => 'John Sender',
                'first_name' => 'John',
                'last_name' => 'Sender',
                'password' => \Hash::make('password'),
                'user_status' => 'active',
                'activation_access_code' => \Str::random(32),
                'created_date_time' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'account_id' => $demoAccount->id,
                'user_id' => 'user_signer_' . \Str::random(16),
                'email' => 'signer@demo.test',
                'user_name' => 'Jane Signer',
                'first_name' => 'Jane',
                'last_name' => 'Signer',
                'password' => \Hash::make('password'),
                'user_status' => 'active',
                'activation_access_code' => \Str::random(32),
                'created_date_time' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($users as $user) {
            \DB::table('users')->insert($user);
        }

        $this->command->info('Created ' . count($users) . ' demo users.');
    }
}
