<?php

namespace App\Services;

use App\Exceptions\Custom\BusinessLogicException;
use App\Exceptions\Custom\ResourceNotFoundException;
use App\Models\AccountSettings;
use App\Models\FileType;
use App\Models\SupportedLanguage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * SettingsService
 *
 * Handles business logic for account settings operations.
 */
class SettingsService
{
    /**
     * Get account settings.
     */
    public function getAccountSettings(int $accountId): AccountSettings
    {
        $settings = AccountSettings::where('account_id', $accountId)->first();

        if (!$settings) {
            // Create default settings if none exist
            $settings = AccountSettings::create([
                'account_id' => $accountId,
            ]);
        }

        return $settings;
    }

    /**
     * Update account settings.
     */
    public function updateAccountSettings(int $accountId, array $data): AccountSettings
    {
        $settings = $this->getAccountSettings($accountId);

        DB::beginTransaction();
        try {
            $settings->update(array_filter($data, fn($value) => $value !== null));

            DB::commit();
            return $settings->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw new BusinessLogicException('Failed to update account settings: ' . $e->getMessage());
        }
    }

    /**
     * Get supported languages.
     */
    public function getSupportedLanguages(): Collection
    {
        return SupportedLanguage::orderBy('language_name')->get();
    }

    /**
     * Get unsupported file types.
     */
    public function getUnsupportedFileTypes(): Collection
    {
        return FileType::where('is_supported', false)
            ->orderBy('file_extension')
            ->get();
    }

    /**
     * Get supported file types.
     */
    public function getSupportedFileTypes(): Collection
    {
        return FileType::where('is_supported', true)
            ->orderBy('file_extension')
            ->get();
    }
}
