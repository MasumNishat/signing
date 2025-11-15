<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first account
        $demoAccount = \DB::table('accounts')->where('account_name', 'Demo Account')->first();

        if (!$demoAccount) {
            $this->command->warn('No demo account found. Skipping permission profile seeding.');
            return;
        }

        $profiles = [
            [
                'account_id' => $demoAccount->id,
                'permission_profile_id' => 'perm_admin_' . \Str::random(16),
                'permission_profile_name' => 'Administrator',
                'permissions' => json_encode([
                    'can_manage_account' => true,
                    'can_manage_users' => true,
                    'can_send_envelopes' => true,
                    'can_sign_envelopes' => true,
                    'can_manage_templates' => true,
                    'can_manage_branding' => true,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'account_id' => $demoAccount->id,
                'permission_profile_id' => 'perm_sender_' . \Str::random(16),
                'permission_profile_name' => 'Sender',
                'permissions' => json_encode([
                    'can_manage_account' => false,
                    'can_manage_users' => false,
                    'can_send_envelopes' => true,
                    'can_sign_envelopes' => true,
                    'can_manage_templates' => false,
                    'can_manage_branding' => false,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'account_id' => $demoAccount->id,
                'permission_profile_id' => 'perm_viewer_' . \Str::random(16),
                'permission_profile_name' => 'Viewer',
                'permissions' => json_encode([
                    'can_manage_account' => false,
                    'can_manage_users' => false,
                    'can_send_envelopes' => false,
                    'can_sign_envelopes' => true,
                    'can_manage_templates' => false,
                    'can_manage_branding' => false,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($profiles as $profile) {
            \DB::table('permission_profiles')->insert($profile);
        }

        $this->command->info('Created ' . count($profiles) . ' permission profiles.');
    }
}
