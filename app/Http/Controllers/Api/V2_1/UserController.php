<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    // ==================== User CRUD ====================

    /**
     * Get list of users for the account.
     *
     * GET /v2.1/accounts/{accountId}/users
     */
    public function index(Request $request, string $accountId): JsonResponse
    {
        try {
            $filters = $request->only(['status', 'user_type', 'search']);

            $users = $this->userService->getUsers((int) $accountId, $filters);

            return $this->success([
                'users' => $users->map(function ($user) {
                    return $this->formatUserResponse($user);
                })->toArray(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get a specific user.
     *
     * GET /v2.1/accounts/{accountId}/users/{userId}
     */
    public function show(Request $request, string $accountId, string $userId): JsonResponse
    {
        try {
            $user = $this->userService->getUser((int) $accountId, (int) $userId);

            if (!$user) {
                return $this->notFound('User not found');
            }

            return $this->success($this->formatUserResponse($user));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Create a new user.
     *
     * POST /v2.1/accounts/{accountId}/users
     */
    public function store(Request $request, string $accountId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'password' => 'nullable|string|min:8',
            'user_type' => 'nullable|string|in:user,admin,company_user',
            'permission_profile_id' => 'nullable|integer|exists:permission_profiles,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $user = $this->userService->createUser((int) $accountId, $request->all());

            return $this->created($this->formatUserResponse($user));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Update a user.
     *
     * PUT /v2.1/accounts/{accountId}/users/{userId}
     */
    public function update(Request $request, string $accountId, string $userId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email',
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'password' => 'nullable|string|min:8',
            'user_status' => 'nullable|string|in:active,inactive,closed',
            'user_type' => 'nullable|string|in:user,admin,company_user',
            'permission_profile_id' => 'nullable|integer|exists:permission_profiles,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $user = $this->userService->updateUser((int) $accountId, (int) $userId, $request->all());

            return $this->success($this->formatUserResponse($user));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Bulk update users.
     *
     * PUT /v2.1/accounts/{accountId}/users
     */
    public function bulkUpdate(Request $request, string $accountId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'users' => 'required|array',
            'users.*.id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $users = $this->userService->bulkUpdateUsers((int) $accountId, $request->input('users'));

            return $this->success([
                'users' => $users->map(function ($user) {
                    return $this->formatUserResponse($user);
                })->toArray(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Delete users.
     *
     * DELETE /v2.1/accounts/{accountId}/users
     */
    public function destroy(Request $request, string $accountId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array',
            'user_ids.*' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $deletedCount = $this->userService->deleteUsers((int) $accountId, $request->input('user_ids'));

            return $this->success(['deleted_count' => $deletedCount]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    // ==================== Contacts ====================

    /**
     * Get all contacts for a user.
     *
     * GET /v2.1/accounts/{accountId}/contacts (for authenticated user)
     */
    public function getContacts(Request $request, string $accountId): JsonResponse
    {
        try {
            $userId = auth()->id();
            $contacts = $this->userService->getContacts((int) $accountId, $userId);

            return $this->success([
                'contacts' => $contacts->map(function ($contact) {
                    return $this->formatContactResponse($contact);
                })->toArray(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get a specific contact.
     *
     * GET /v2.1/accounts/{accountId}/contacts/{contactId}
     */
    public function getContact(Request $request, string $accountId, string $contactId): JsonResponse
    {
        try {
            $userId = auth()->id();
            $contact = $this->userService->getContact((int) $accountId, $userId, (int) $contactId);

            if (!$contact) {
                return $this->notFound('Contact not found');
            }

            return $this->success($this->formatContactResponse($contact));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Import contacts (bulk create).
     *
     * POST /v2.1/accounts/{accountId}/contacts
     */
    public function importContacts(Request $request, string $accountId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'contacts' => 'required|array',
            'contacts.*.email' => 'required|email',
            'contacts.*.name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $userId = auth()->id();
            $contacts = $this->userService->importContacts((int) $accountId, $userId, $request->input('contacts'));

            return $this->created([
                'contacts' => $contacts->map(function ($contact) {
                    return $this->formatContactResponse($contact);
                })->toArray(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Replace all contacts.
     *
     * PUT /v2.1/accounts/{accountId}/contacts
     */
    public function replaceContacts(Request $request, string $accountId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'contacts' => 'required|array',
            'contacts.*.email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $userId = auth()->id();
            $contacts = $this->userService->replaceContacts((int) $accountId, $userId, $request->input('contacts'));

            return $this->success([
                'contacts' => $contacts->map(function ($contact) {
                    return $this->formatContactResponse($contact);
                })->toArray(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Delete all contacts.
     *
     * DELETE /v2.1/accounts/{accountId}/contacts
     */
    public function deleteAllContacts(Request $request, string $accountId): JsonResponse
    {
        try {
            $userId = auth()->id();
            $deletedCount = $this->userService->deleteAllContacts((int) $accountId, $userId);

            return $this->success(['deleted_count' => $deletedCount]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Delete a specific contact.
     *
     * DELETE /v2.1/accounts/{accountId}/contacts/{contactId}
     */
    public function deleteContact(Request $request, string $accountId, string $contactId): JsonResponse
    {
        try {
            $userId = auth()->id();
            $deleted = $this->userService->deleteContact((int) $accountId, $userId, (int) $contactId);

            if (!$deleted) {
                return $this->notFound('Contact not found');
            }

            return $this->noContent();
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    // ==================== Custom Settings ====================

    /**
     * Get custom settings for a user.
     *
     * GET /v2.1/accounts/{accountId}/users/{userId}/custom_settings
     */
    public function getCustomSettings(Request $request, string $accountId, string $userId): JsonResponse
    {
        try {
            $settings = $this->userService->getCustomSettings((int) $userId);

            return $this->success(['custom_settings' => $settings]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Update custom settings for a user.
     *
     * PUT /v2.1/accounts/{accountId}/users/{userId}/custom_settings
     */
    public function updateCustomSettings(Request $request, string $accountId, string $userId): JsonResponse
    {
        try {
            $settings = $this->userService->updateCustomSettings((int) $accountId, (int) $userId, $request->all());

            return $this->success(['custom_settings' => $settings]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Delete custom settings for a user.
     *
     * DELETE /v2.1/accounts/{accountId}/users/{userId}/custom_settings
     */
    public function deleteCustomSettings(Request $request, string $accountId, string $userId): JsonResponse
    {
        try {
            $deletedCount = $this->userService->deleteCustomSettings((int) $userId);

            return $this->success(['deleted_count' => $deletedCount]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    // ==================== Profile ====================

    /**
     * Get user profile.
     *
     * GET /v2.1/accounts/{accountId}/users/{userId}/profile
     */
    public function getProfile(Request $request, string $accountId, string $userId): JsonResponse
    {
        try {
            $profile = $this->userService->getProfile((int) $userId);

            if (!$profile) {
                return $this->notFound('Profile not found');
            }

            return $this->success($this->formatProfileResponse($profile));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Update user profile.
     *
     * PUT /v2.1/accounts/{accountId}/users/{userId}/profile
     */
    public function updateProfile(Request $request, string $accountId, string $userId): JsonResponse
    {
        try {
            $profile = $this->userService->updateProfile((int) $userId, $request->all());

            return $this->success($this->formatProfileResponse($profile));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get profile image.
     *
     * GET /v2.1/accounts/{accountId}/users/{userId}/profile/image
     */
    public function getProfileImage(Request $request, string $accountId, string $userId): JsonResponse
    {
        try {
            $imageUri = $this->userService->getProfileImage((int) $userId);

            if (!$imageUri) {
                return $this->notFound('Profile image not found');
            }

            return $this->success(['image_uri' => $imageUri]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Upload profile image.
     *
     * PUT /v2.1/accounts/{accountId}/users/{userId}/profile/image
     */
    public function uploadProfileImage(Request $request, string $accountId, string $userId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $imageUri = $this->userService->uploadProfileImage((int) $userId, $request->file('image'));

            return $this->success(['image_uri' => $imageUri]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Delete profile image.
     *
     * DELETE /v2.1/accounts/{accountId}/users/{userId}/profile/image
     */
    public function deleteProfileImage(Request $request, string $accountId, string $userId): JsonResponse
    {
        try {
            $deleted = $this->userService->deleteProfileImage((int) $userId);

            if (!$deleted) {
                return $this->notFound('Profile image not found');
            }

            return $this->noContent();
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    // ==================== Settings ====================

    /**
     * Get user settings.
     *
     * GET /v2.1/accounts/{accountId}/users/{userId}/settings
     */
    public function getSettings(Request $request, string $accountId, string $userId): JsonResponse
    {
        try {
            $settings = $this->userService->getSettings((int) $userId);

            if (!$settings) {
                return $this->notFound('Settings not found');
            }

            return $this->success($this->formatSettingsResponse($settings));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Update user settings.
     *
     * PUT /v2.1/accounts/{accountId}/users/{userId}/settings
     */
    public function updateSettings(Request $request, string $accountId, string $userId): JsonResponse
    {
        try {
            $settings = $this->userService->updateSettings((int) $userId, $request->all());

            return $this->success($this->formatSettingsResponse($settings));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    // ==================== Response Formatters ====================

    private function formatUserResponse($user): array
    {
        return [
            'user_id' => $user->id,
            'user_name' => $user->user_name,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'middle_name' => $user->middle_name,
            'last_name' => $user->last_name,
            'suffix_name' => $user->suffix_name,
            'title' => $user->title,
            'job_title' => $user->job_title,
            'country_code' => $user->country_code,
            'user_status' => $user->user_status,
            'user_type' => $user->user_type,
            'is_admin' => $user->is_admin,
            'permission_profile_id' => $user->permission_profile_id,
            'last_login' => $user->last_login?->toIso8601String(),
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }

    private function formatContactResponse($contact): array
    {
        return [
            'contact_id' => $contact->id,
            'email' => $contact->email,
            'name' => $contact->name,
            'first_name' => $contact->first_name,
            'last_name' => $contact->last_name,
            'company_name' => $contact->company_name,
            'phone_number' => $contact->phone_number,
            'mobile_phone' => $contact->mobile_phone,
        ];
    }

    private function formatProfileResponse($profile): array
    {
        return [
            'user_id' => $profile->user_id,
            'display_name' => $profile->display_name,
            'profile_image_uri' => $profile->profile_image_uri,
            'biography' => $profile->biography,
            'company' => $profile->company,
            'department' => $profile->department,
            'office_location' => $profile->office_location,
            'work_phone' => $profile->work_phone,
            'mobile_phone' => $profile->mobile_phone,
            'address' => [
                'address_line_1' => $profile->address_line_1,
                'address_line_2' => $profile->address_line_2,
                'city' => $profile->city,
                'state_province' => $profile->state_province,
                'postal_code' => $profile->postal_code,
                'country' => $profile->country,
            ],
        ];
    }

    private function formatSettingsResponse($settings): array
    {
        return [
            'user_id' => $settings->user_id,
            'email_notifications' => $settings->email_notifications,
            'envelope_complete_notifications' => $settings->envelope_complete_notifications,
            'default_language' => $settings->default_language,
            'default_timezone' => $settings->default_timezone,
            'envelope_expiration_days' => $settings->envelope_expiration_days,
            'reminder_enabled' => $settings->reminder_enabled,
            'api_access_enabled' => $settings->api_access_enabled,
        ];
    }
}
