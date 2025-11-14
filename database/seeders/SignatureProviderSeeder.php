<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SignatureProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $providers = [
            [
                'provider_id' => 'provider_docusign',
                'provider_name' => 'DocuSign',
                'priority' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'provider_id' => 'provider_standard',
                'provider_name' => 'Standard Electronic',
                'priority' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'provider_id' => 'provider_universal',
                'provider_name' => 'Universal',
                'priority' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($providers as $provider) {
            \DB::table('signature_providers')->insert($provider);
        }
    }
}
