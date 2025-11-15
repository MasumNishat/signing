<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Exceptions\Custom\BusinessLogicException;
use App\Http\Controllers\Api\BaseController;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * SettingsController
 *
 * Handles account settings and configuration operations.
 *
 * Endpoints: 5 total
 * - Account Settings: 2 endpoints (get, update)
 * - Reference Data: 3 endpoints (languages, file types)
 */
class SettingsController extends BaseController
{
    protected SettingsService $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    // =========================================================================
    // ACCOUNT SETTINGS (2 endpoints)
    // =========================================================================

    /**
     * GET /accounts/{accountId}/settings
     * Get account settings
     */
    public function getSettings(int $accountId): JsonResponse
    {
        try {
            $settings = $this->settingsService->getAccountSettings($accountId);

            return $this->successResponse($settings->toSettingsArray(), 'Account settings retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to get account settings', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve account settings', 500);
        }
    }

    /**
     * PUT /accounts/{accountId}/settings
     * Update account settings
     */
    public function updateSettings(Request $request, int $accountId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'allow_signing_extensions' => 'sometimes|boolean',
                'allow_signature_stamps' => 'sometimes|boolean',
                'enable_signer_attachments' => 'sometimes|boolean',
                'enable_two_factor_authentication' => 'sometimes|boolean',
                'require_signing_captcha' => 'sometimes|boolean',
                'session_timeout_minutes' => 'sometimes|integer|min:5|max:480',
                'can_self_brand_send' => 'sometimes|boolean',
                'can_self_brand_sign' => 'sometimes|boolean',
                'enable_api_request_logging' => 'sometimes|boolean',
                'api_request_log_max_entries' => 'sometimes|integer|min:10|max:1000',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $settings = $this->settingsService->updateAccountSettings($accountId, $request->all());

            return $this->successResponse($settings->toSettingsArray(), 'Account settings updated successfully');

        } catch (BusinessLogicException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('Failed to update account settings', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to update account settings', 500);
        }
    }

    // =========================================================================
    // REFERENCE DATA (3 endpoints)
    // =========================================================================

    /**
     * GET /accounts/{accountId}/supported_languages
     * Get supported languages
     */
    public function getSupportedLanguages(int $accountId): JsonResponse
    {
        try {
            $languages = $this->settingsService->getSupportedLanguages();

            $formatted = $languages->map(function ($lang) {
                return [
                    'language_code' => $lang->language_code,
                    'language_name' => $lang->language_name,
                ];
            });

            return $this->successResponse($formatted, 'Supported languages retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to get supported languages', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve supported languages', 500);
        }
    }

    /**
     * GET /accounts/{accountId}/unsupported_file_types
     * Get unsupported file types
     */
    public function getUnsupportedFileTypes(int $accountId): JsonResponse
    {
        try {
            $fileTypes = $this->settingsService->getUnsupportedFileTypes();

            $formatted = $fileTypes->map(function ($type) {
                return [
                    'file_extension' => $type->file_extension,
                    'mime_type' => $type->mime_type,
                ];
            });

            return $this->successResponse($formatted, 'Unsupported file types retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to get unsupported file types', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve unsupported file types', 500);
        }
    }

    /**
     * GET /accounts/{accountId}/supported_file_types
     * Get supported file types (custom endpoint for completeness)
     */
    public function getSupportedFileTypes(int $accountId): JsonResponse
    {
        try {
            $fileTypes = $this->settingsService->getSupportedFileTypes();

            $formatted = $fileTypes->map(function ($type) {
                return [
                    'file_extension' => $type->file_extension,
                    'mime_type' => $type->mime_type,
                ];
            });

            return $this->successResponse($formatted, 'Supported file types retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to get supported file types', [
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve supported file types', 500);
        }
    }
}
