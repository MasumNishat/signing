<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class RenameMigrationsSequentially extends Command
{
    protected $signature = 'migrations:rename-sequential
                            {--start=1 : Starting number for sequencing}
                            {--dry-run : Show what would be renamed without actually doing it}
                            {--sort=name : Sort by "name", "date", or "created"}';

    protected $description = 'Rename database migrations with sequential numbering';

    public function handle()
    {
        $migrationsPath = database_path('migrations');
        $files = File::files($migrationsPath);

        $migrationFiles = collect($files)
            ->filter(fn($file) => $file->getExtension() === 'php')
            ->sortBy(function ($file) {
                return match($this->option('sort')) {
                    'date' => $this->extractTimestamp($file->getFilename()),
                    'created' => $file->getCTime(),
                    default => $file->getFilename(),
                };
            })
            ->values();

        if ($migrationFiles->isEmpty()) {
            $this->info('No migration files found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$migrationFiles->count()} migration files. Sorting by: " . $this->option('sort'));
        $this->line('');

        $startNumber = (int) $this->option('start');
        $dryRun = $this->option('dry-run');
        $changes = [];

        foreach ($migrationFiles as $index => $file) {
            $oldName = $file->getFilename();
            $newName = $this->generateSequentialName($oldName, $startNumber + $index);

            $changes[] = [
                '#' => $startNumber + $index,
                'current_name' => $oldName,
                'new_name' => $newName,
            ];
        }

        $this->table(['#', 'Current Name', 'New Name'], $changes);

        if ($dryRun) {
            $this->info('Dry run completed. No files were renamed.');
            return Command::SUCCESS;
        }

        if (!$this->confirm('Do you wish to proceed with renaming these files?')) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        $successCount = 0;
        foreach ($changes as $change) {
            $oldPath = $migrationsPath . '/' . $change['current_name'];
            $newPath = $migrationsPath . '/' . $change['new_name'];

            if (File::exists($oldPath) && !File::exists($newPath)) {
                if (File::move($oldPath, $newPath)) {
                    $this->line("✓ [{$change['#']}] {$change['current_name']} → {$change['new_name']}");
                    $successCount++;
                } else {
                    $this->error("✗ Failed to rename: {$change['current_name']}");
                }
            } else {
                $this->warn("⚠ Skipped: {$change['current_name']}");
            }
        }

        $this->info("\nSuccessfully renamed {$successCount} migration files.");

        return Command::SUCCESS;
    }

    private function generateSequentialName(string $currentName, int $sequence): string
    {
        if (preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_(.+\.php)$/', $currentName, $matches)) {
            $migrationBody = $matches[1];
        } else {
            $migrationBody = $currentName;
        }

        $sequenceFormatted = str_pad($sequence, 4, '0', STR_PAD_LEFT);

        return "{$sequenceFormatted}_00_00_000000_{$migrationBody}";
    }

    private function extractTimestamp(string $filename): string
    {
        if (preg_match('/^(\d{4}_\d{2}_\d{2}_\d{6})/', $filename, $matches)) {
            return $matches[1];
        }

        return $filename;
    }
}
