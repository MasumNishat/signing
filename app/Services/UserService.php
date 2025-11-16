<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\User;
use App\Models\UserCustomSetting;
use App\Models\UserProfile;
use App\Models\UserSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserService
{
    /**
     * Get all users for an account with optional filtering.
     */
    public function getUsers(int $accountId, array $filters = []): Collection
    {
        $query = User::where('account_id', $accountId);

        if (!empty($filters['status'])) {
            $query->where('user_status', $filters['status']);
        }

        if (!empty($filters['user_type'])) {
            $query->where('user_type', $filters['user_type']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        return $query->with(['permissionProfile', 'profile', 'settings'])->get();
    }

    /**
     * Get a specific user by ID.
     */
    public function getUser(int $accountId, int $userId): ?User
    {
        return User::where('account_id', $accountId)
            ->where('id', $userId)
            ->with(['permissionProfile', 'profile', 'settings', 'addresses'])
            ->first();
    }

    /**
     * Create a new user.
     */
    public function createUser(int $accountId, array $data): User
    {
        DB::beginTransaction();
        try {
            $userData = array_merge($data, [
                'account_id' => $accountId,
                'password' => isset($data['password']) ? $data['password'] : Hash::make(bin2hex(random_bytes(16))),
                'created_datetime' => now(),
            ]);

            $user = User::create($userData);

            // Create default profile
            UserProfile::create(['user_id' => $user->id]);

            // Create default settings
            UserSetting::create(['user_id' => $user->id]);

            DB::commit();
            return $user->load(['permissionProfile', 'profile', 'settings']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update a user.
     */
    public function updateUser(int $accountId, int $userId, array $data): User
    {
        $user = User::where('account_id', $accountId)->findOrFail($userId);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $data['user_profile_last_modified_date'] = now();

        $user->update($data);

        return $user->load(['permissionProfile', 'profile', 'settings']);
    }

    /**
     * Bulk update users.
     */
    public function bulkUpdateUsers(int $accountId, array $users): Collection
    {
        DB::beginTransaction();
        try {
            $updated = collect();

            foreach ($users as $userData) {
                $userId = $userData['id'] ?? $userData['user_id'] ?? null;
                if ($userId) {
                    $user = $this->updateUser($accountId, $userId, $userData);
                    $updated->push($user);
                }
            }

            DB::commit();
            return $updated;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete users (soft delete).
     */
    public function deleteUsers(int $accountId, array $userIds): int
    {
        return User::where('account_id', $accountId)
            ->whereIn('id', $userIds)
            ->update(['user_status' => 'closed', 'deleted_at' => now()]);
    }

    // ==================== Contacts ====================

    /**
     * Get contacts for a user.
     */
    public function getContacts(int $accountId, int $userId): Collection
    {
        return Contact::where('account_id', $accountId)
            ->where('user_id', $userId)
            ->get();
    }

    /**
     * Get a specific contact.
     */
    public function getContact(int $accountId, int $userId, int $contactId): ?Contact
    {
        return Contact::where('account_id', $accountId)
            ->where('user_id', $userId)
            ->find($contactId);
    }

    /**
     * Import contacts (bulk create).
     */
    public function importContacts(int $accountId, int $userId, array $contacts): Collection
    {
        DB::beginTransaction();
        try {
            $created = collect();

            foreach ($contacts as $contactData) {
                $contact = Contact::create(array_merge($contactData, [
                    'account_id' => $accountId,
                    'user_id' => $userId,
                ]));
                $created->push($contact);
            }

            DB::commit();
            return $created;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Replace all contacts for a user.
     */
    public function replaceContacts(int $accountId, int $userId, array $contacts): Collection
    {
        DB::beginTransaction();
        try {
            // Delete existing contacts
            Contact::where('account_id', $accountId)
                ->where('user_id', $userId)
                ->delete();

            // Import new contacts
            $imported = $this->importContacts($accountId, $userId, $contacts);

            DB::commit();
            return $imported;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete all contacts for a user.
     */
    public function deleteAllContacts(int $accountId, int $userId): int
    {
        return Contact::where('account_id', $accountId)
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * Delete a specific contact.
     */
    public function deleteContact(int $accountId, int $userId, int $contactId): bool
    {
        return Contact::where('account_id', $accountId)
            ->where('user_id', $userId)
            ->where('id', $contactId)
            ->delete() > 0;
    }

    // ==================== Custom Settings ====================

    /**
     * Get custom settings for a user.
     */
    public function getCustomSettings(int $userId): array
    {
        $settings = UserCustomSetting::forUser($userId)->get();

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->setting_key] = $setting->setting_value;
        }

        return $result;
    }

    /**
     * Update custom settings for a user.
     */
    public function updateCustomSettings(int $accountId, int $userId, array $settings): array
    {
        DB::beginTransaction();
        try {
            foreach ($settings as $key => $value) {
                UserCustomSetting::updateOrCreate(
                    [
                        'account_id' => $accountId,
                        'user_id' => $userId,
                        'setting_key' => $key,
                    ],
                    [
                        'setting_value' => $value,
                    ]
                );
            }

            DB::commit();
            return $this->getCustomSettings($userId);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete custom settings for a user.
     */
    public function deleteCustomSettings(int $userId): int
    {
        return UserCustomSetting::forUser($userId)->delete();
    }

    // ==================== Profile ====================

    /**
     * Get user profile.
     */
    public function getProfile(int $userId): ?UserProfile
    {
        return UserProfile::where('user_id', $userId)->first();
    }

    /**
     * Update user profile.
     */
    public function updateProfile(int $userId, array $data): UserProfile
    {
        $profile = UserProfile::firstOrCreate(['user_id' => $userId]);

        $data['profile_last_modified'] = now();

        $profile->update($data);

        return $profile;
    }

    /**
     * Get profile image URI.
     */
    public function getProfileImage(int $userId): ?string
    {
        $profile = UserProfile::where('user_id', $userId)->first();

        return $profile?->profile_image_uri;
    }

    /**
     * Upload profile image.
     */
    public function uploadProfileImage(int $userId, $image): string
    {
        $profile = UserProfile::firstOrCreate(['user_id' => $userId]);

        // Delete old image if exists
        if ($profile->profile_image_uri) {
            Storage::disk('private')->delete($profile->profile_image_uri);
        }

        // Store new image
        $path = $image->store('profile-images', 'private');

        $profile->update([
            'profile_image_uri' => $path,
            'profile_last_modified' => now(),
        ]);

        return $path;
    }

    /**
     * Delete profile image.
     */
    public function deleteProfileImage(int $userId): bool
    {
        $profile = UserProfile::where('user_id', $userId)->first();

        if (!$profile || !$profile->profile_image_uri) {
            return false;
        }

        // Delete the file
        Storage::disk('private')->delete($profile->profile_image_uri);

        // Clear the URI
        $profile->update([
            'profile_image_uri' => null,
            'profile_last_modified' => now(),
        ]);

        return true;
    }

    // ==================== Settings ====================

    /**
     * Get user settings.
     */
    public function getSettings(int $userId): ?UserSetting
    {
        return UserSetting::where('user_id', $userId)->first();
    }

    /**
     * Update user settings.
     */
    public function updateSettings(int $userId, array $data): UserSetting
    {
        $settings = UserSetting::firstOrCreate(['user_id' => $userId]);

        $settings->update($data);

        return $settings;
    }
}
