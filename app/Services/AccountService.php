<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountCustomField;
use App\Models\ConsumerDisclosure;
use App\Models\WatermarkConfiguration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AccountService
{
    // ==================== Account CRUD ====================

    /**
     * Get account by ID.
     */
    public function getAccount(int $accountId): ?Account
    {
        return Account::with(['plan', 'users', 'permissionProfiles'])->find($accountId);
    }

    /**
     * Create a new account.
     */
    public function createAccount(array $data): Account
    {
        DB::beginTransaction();
        try {
            $account = Account::create($data);

            // Create default configurations
            $this->createDefaultConfigurations($account->id);

            DB::commit();
            return $account->load(['plan']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update account.
     */
    public function updateAccount(int $accountId, array $data): Account
    {
        $account = Account::findOrFail($accountId);
        $account->update($data);

        return $account->load(['plan']);
    }

    /**
     * Delete account.
     */
    public function deleteAccount(int $accountId): bool
    {
        $account = Account::findOrFail($accountId);
        return $account->delete();
    }

    /**
     * Get account provisioning information.
     */
    public function getProvisioning(int $accountId): array
    {
        $account = Account::with(['plan', 'users'])->findOrFail($accountId);

        return [
            'account_id' => $account->id,
            'account_name' => $account->account_name,
            'plan_name' => $account->plan->plan_name ?? null,
            'plan_id' => $account->plan_id,
            'billing_period_start_date' => $account->billing_period_start_date,
            'billing_period_end_date' => $account->billing_period_end_date,
            'envelopes_sent' => $account->billing_period_envelopes_sent,
            'envelopes_allowed' => $account->billing_period_envelopes_allowed,
            'users_count' => $account->users()->count(),
            'can_upgrade' => $account->can_upgrade,
            'suspension_status' => $account->suspension_status,
        ];
    }

    // ==================== Custom Fields ====================

    /**
     * Get all custom fields for an account.
     */
    public function getCustomFields(int $accountId): Collection
    {
        return AccountCustomField::forAccount($accountId)
            ->ordered()
            ->get();
    }

    /**
     * Create a custom field.
     */
    public function createCustomField(int $accountId, array $data): AccountCustomField
    {
        return AccountCustomField::create(array_merge($data, [
            'account_id' => $accountId,
        ]));
    }

    /**
     * Update a custom field.
     */
    public function updateCustomField(int $accountId, string $fieldId, array $data): AccountCustomField
    {
        $field = AccountCustomField::forAccount($accountId)
            ->where('field_id', $fieldId)
            ->firstOrFail();

        $field->update($data);

        return $field;
    }

    /**
     * Delete a custom field.
     */
    public function deleteCustomField(int $accountId, string $fieldId): bool
    {
        return AccountCustomField::forAccount($accountId)
            ->where('field_id', $fieldId)
            ->delete() > 0;
    }

    // ==================== Consumer Disclosure ====================

    /**
     * Get consumer disclosure for account (default language).
     */
    public function getConsumerDisclosure(int $accountId, ?string $langCode = null): ?ConsumerDisclosure
    {
        $query = ConsumerDisclosure::forAccount($accountId);

        if ($langCode) {
            $query->byLanguage($langCode);
        } else {
            $query->byLanguage('en'); // Default to English
        }

        return $query->first();
    }

    /**
     * Update consumer disclosure.
     */
    public function updateConsumerDisclosure(int $accountId, string $langCode, array $data): ConsumerDisclosure
    {
        $disclosure = ConsumerDisclosure::updateOrCreate(
            [
                'account_id' => $accountId,
                'language_code' => $langCode,
            ],
            $data
        );

        return $disclosure;
    }

    // ==================== Watermark ====================

    /**
     * Get watermark configuration.
     */
    public function getWatermark(int $accountId): ?WatermarkConfiguration
    {
        return WatermarkConfiguration::where('account_id', $accountId)->first();
    }

    /**
     * Update watermark configuration.
     */
    public function updateWatermark(int $accountId, array $data): WatermarkConfiguration
    {
        $watermark = WatermarkConfiguration::updateOrCreate(
            ['account_id' => $accountId],
            $data
        );

        return $watermark;
    }

    /**
     * Get watermark preview.
     */
    public function getWatermarkPreview(int $accountId): array
    {
        $watermark = $this->getWatermark($accountId);

        if (!$watermark || !$watermark->enabled) {
            return [
                'enabled' => false,
                'preview_url' => null,
            ];
        }

        // In production, this would generate an actual preview image
        return [
            'enabled' => true,
            'watermark_text' => $watermark->watermark_text,
            'font' => $watermark->watermark_font,
            'font_size' => $watermark->watermark_font_size,
            'font_color' => $watermark->watermark_font_color,
            'transparency' => $watermark->watermark_transparency,
            'horizontal_alignment' => $watermark->horizontal_alignment,
            'vertical_alignment' => $watermark->vertical_alignment,
            'angle' => $watermark->display_angle ? $watermark->angle : 0,
            'preview_url' => '/api/v2.1/accounts/' . $accountId . '/watermark/preview',
        ];
    }

    // ==================== Helper Methods ====================

    /**
     * Create default configurations for a new account.
     */
    protected function createDefaultConfigurations(int $accountId): void
    {
        // Create default watermark configuration
        WatermarkConfiguration::create([
            'account_id' => $accountId,
            'enabled' => false,
        ]);

        // Create default consumer disclosure (English)
        ConsumerDisclosure::create([
            'account_id' => $accountId,
            'language_code' => 'en',
            'enable_esign' => true,
        ]);
    }

    /**
     * Get recipient names by email.
     */
    public function getRecipientNames(int $accountId, string $email): Collection
    {
        // Search in users and contacts for matching email
        $users = \App\Models\User::where('account_id', $accountId)
            ->where('email', 'like', "%{$email}%")
            ->select('email', 'first_name', 'last_name', 'user_name as name')
            ->get();

        $contacts = \App\Models\Contact::where('account_id', $accountId)
            ->where('email', 'like', "%{$email}%")
            ->select('email', 'first_name', 'last_name', 'name')
            ->get();

        return $users->concat($contacts)->unique('email');
    }

    // ==================== eNote Configuration ====================

    /**
     * Get eNote configuration.
     */
    public function getEnoteConfiguration(int $accountId): ?\App\Models\EnoteConfiguration
    {
        return \App\Models\EnoteConfiguration::where('account_id', $accountId)->first();
    }

    /**
     * Update eNote configuration.
     */
    public function updateEnoteConfiguration(int $accountId, array $data): \App\Models\EnoteConfiguration
    {
        $enote = \App\Models\EnoteConfiguration::updateOrCreate(
            ['account_id' => $accountId],
            $data
        );

        return $enote;
    }

    /**
     * Delete eNote configuration.
     */
    public function deleteEnoteConfiguration(int $accountId): bool
    {
        return \App\Models\EnoteConfiguration::where('account_id', $accountId)->delete() > 0;
    }

    // ==================== Envelope Purge Configuration ====================

    /**
     * Get envelope purge configuration.
     */
    public function getEnvelopePurgeConfiguration(int $accountId): ?\App\Models\EnvelopePurgeConfiguration
    {
        return \App\Models\EnvelopePurgeConfiguration::where('account_id', $accountId)->first();
    }

    /**
     * Update envelope purge configuration.
     */
    public function updateEnvelopePurgeConfiguration(int $accountId, array $data): \App\Models\EnvelopePurgeConfiguration
    {
        $purge = \App\Models\EnvelopePurgeConfiguration::updateOrCreate(
            ['account_id' => $accountId],
            $data
        );

        return $purge;
    }

    // ==================== Notification Defaults ====================

    /**
     * Get notification defaults.
     */
    public function getNotificationDefaults(int $accountId): ?\App\Models\NotificationDefault
    {
        return \App\Models\NotificationDefault::where('account_id', $accountId)->first();
    }

    /**
     * Update notification defaults.
     */
    public function updateNotificationDefaults(int $accountId, array $data): \App\Models\NotificationDefault
    {
        $notification = \App\Models\NotificationDefault::updateOrCreate(
            ['account_id' => $accountId],
            $data
        );

        return $notification;
    }

    // ==================== Password Rules ====================

    /**
     * Get password rules for account.
     */
    public function getPasswordRules(int $accountId): ?\App\Models\PasswordRule
    {
        return \App\Models\PasswordRule::where('account_id', $accountId)->first();
    }

    /**
     * Update password rules.
     */
    public function updatePasswordRules(int $accountId, array $data): \App\Models\PasswordRule
    {
        $rules = \App\Models\PasswordRule::updateOrCreate(
            ['account_id' => $accountId],
            $data
        );

        return $rules;
    }

    /**
     * Get password rules for current user's account.
     */
    public function getCurrentUserPasswordRules(): ?\App\Models\PasswordRule
    {
        $accountId = auth()->user()->account_id;
        return $this->getPasswordRules($accountId);
    }

    // ==================== Tab Settings ====================

    /**
     * Get tab settings.
     */
    public function getTabSettings(int $accountId): ?\App\Models\TabSetting
    {
        return \App\Models\TabSetting::where('account_id', $accountId)->first();
    }

    /**
     * Update tab settings.
     */
    public function updateTabSettings(int $accountId, array $data): \App\Models\TabSetting
    {
        $settings = \App\Models\TabSetting::updateOrCreate(
            ['account_id' => $accountId],
            $data
        );

        return $settings;
    }
}
