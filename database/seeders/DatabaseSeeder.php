<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding database...');

        // Seed reference/configuration tables (no dependencies)
        $this->command->info('📋 Seeding reference data...');
        $this->call([
            FileTypeSeeder::class,
            SupportedLanguageSeeder::class,
            SignatureProviderSeeder::class,
        ]);

        // Seed core business tables (with dependencies)
        $this->command->info('🏢 Seeding core business data...');
        $this->call([
            PlanSeeder::class,
            AccountSeeder::class,
            PermissionProfileSeeder::class,
            UserSeeder::class,
        ]);

        $this->command->info('✅ Database seeding completed!');
    }
}
