<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Http\Controllers\Controller;
use App\Services\AccountService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AccountController extends Controller
{
    protected AccountService $accountService;

    public function __construct(AccountService $accountService)
    {
        $this->accountService = $accountService;
    }

    // ==================== Account CRUD ====================

    /**
     * Get account information.
     *
     * GET /v2.1/accounts/{accountId}
     */
    public function show(string $accountId): JsonResponse
    {
        try {
            $account = $this->accountService->getAccount((int) $accountId);

            if (!$account) {
                return $this->notFound('Account not found');
            }

            return $this->success($this->formatAccountResponse($account));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Create a new account.
     *
     * POST /v2.1/accounts
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'account_name' => 'required|string|max:255',
            'organization' => 'nullable|string|max:255',
            'plan_id' => 'required|integer|exists:plans,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $account = $this->accountService->createAccount($request->all());

            return $this->created($this->formatAccountResponse($account));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Delete an account.
     *
     * DELETE /v2.1/accounts/{accountId}
     */
    public function destroy(string $accountId): JsonResponse
    {
        try {
            $deleted = $this->accountService->deleteAccount((int) $accountId);

            if (!$deleted) {
                return $this->notFound('Account not found');
            }

            return $this->noContent();
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get account provisioning information.
     *
     * GET /v2.1/accounts/provisioning
     */
    public function provisioning(Request $request): JsonResponse
    {
        try {
            // Get account ID from authenticated user
            $accountId = auth()->user()->account_id;

            $provisioning = $this->accountService->getProvisioning($accountId);

            return $this->success($provisioning);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    // ==================== Custom Fields ====================

    /**
     * Get all custom fields for the account.
     *
     * GET /v2.1/accounts/{accountId}/custom_fields
     */
    public function getCustomFields(string $accountId): JsonResponse
    {
        try {
            $fields = $this->accountService->getCustomFields((int) $accountId);

            return $this->success([
                'custom_fields' => $fields->map(function ($field) {
                    return $this->formatCustomFieldResponse($field);
                })->toArray(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Create a custom field.
     *
     * POST /v2.1/accounts/{accountId}/custom_fields
     */
    public function createCustomField(Request $request, string $accountId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'field_type' => 'nullable|string|in:text,list',
            'list_items' => 'nullable|array',
            'required' => 'nullable|boolean',
            'show' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $field = $this->accountService->createCustomField((int) $accountId, $request->all());

            return $this->created($this->formatCustomFieldResponse($field));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Update a custom field.
     *
     * PUT /v2.1/accounts/{accountId}/custom_fields/{customFieldId}
     */
    public function updateCustomField(Request $request, string $accountId, string $customFieldId): JsonResponse
    {
        try {
            $field = $this->accountService->updateCustomField((int) $accountId, $customFieldId, $request->all());

            return $this->success($this->formatCustomFieldResponse($field));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Delete a custom field.
     *
     * DELETE /v2.1/accounts/{accountId}/custom_fields/{customFieldId}
     */
    public function deleteCustomField(string $accountId, string $customFieldId): JsonResponse
    {
        try {
            $deleted = $this->accountService->deleteCustomField((int) $accountId, $customFieldId);

            if (!$deleted) {
                return $this->notFound('Custom field not found');
            }

            return $this->noContent();
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    // ==================== Consumer Disclosure ====================

    /**
     * Get consumer disclosure (default language).
     *
     * GET /v2.1/accounts/{accountId}/consumer_disclosure
     */
    public function getConsumerDisclosure(string $accountId): JsonResponse
    {
        try {
            $disclosure = $this->accountService->getConsumerDisclosure((int) $accountId);

            if (!$disclosure) {
                return $this->notFound('Consumer disclosure not found');
            }

            return $this->success($this->formatConsumerDisclosureResponse($disclosure));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get consumer disclosure by language code.
     *
     * GET /v2.1/accounts/{accountId}/consumer_disclosure/{langCode}
     */
    public function getConsumerDisclosureByLanguage(string $accountId, string $langCode): JsonResponse
    {
        try {
            $disclosure = $this->accountService->getConsumerDisclosure((int) $accountId, $langCode);

            if (!$disclosure) {
                return $this->notFound('Consumer disclosure not found for language: ' . $langCode);
            }

            return $this->success($this->formatConsumerDisclosureResponse($disclosure));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Update consumer disclosure.
     *
     * PUT /v2.1/accounts/{accountId}/consumer_disclosure/{langCode}
     */
    public function updateConsumerDisclosure(Request $request, string $accountId, string $langCode): JsonResponse
    {
        try {
            $disclosure = $this->accountService->updateConsumerDisclosure((int) $accountId, $langCode, $request->all());

            return $this->success($this->formatConsumerDisclosureResponse($disclosure));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    // ==================== Watermark ====================

    /**
     * Get watermark information.
     *
     * GET /v2.1/accounts/{accountId}/watermark
     */
    public function getWatermark(string $accountId): JsonResponse
    {
        try {
            $watermark = $this->accountService->getWatermark((int) $accountId);

            if (!$watermark) {
                return $this->notFound('Watermark configuration not found');
            }

            return $this->success($this->formatWatermarkResponse($watermark));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Update watermark information.
     *
     * PUT /v2.1/accounts/{accountId}/watermark
     */
    public function updateWatermark(Request $request, string $accountId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'enabled' => 'nullable|boolean',
            'watermark_text' => 'nullable|string|max:255',
            'watermark_font' => 'nullable|string',
            'watermark_font_size' => 'nullable|integer|min:10|max:200',
            'watermark_transparency' => 'nullable|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $watermark = $this->accountService->updateWatermark((int) $accountId, $request->all());

            return $this->success($this->formatWatermarkResponse($watermark));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get watermark preview.
     *
     * PUT /v2.1/accounts/{accountId}/watermark/preview
     */
    public function watermarkPreview(Request $request, string $accountId): JsonResponse
    {
        try {
            $preview = $this->accountService->getWatermarkPreview((int) $accountId);

            return $this->success($preview);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    // ==================== Recipient Names ====================

    /**
     * Get recipient names by email.
     *
     * GET /v2.1/accounts/{accountId}/recipient_names?email={email}
     */
    public function getRecipientNames(Request $request, string $accountId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $recipients = $this->accountService->getRecipientNames((int) $accountId, $request->input('email'));

            return $this->success([
                'recipients' => $recipients->map(function ($recipient) {
                    return [
                        'email' => $recipient->email,
                        'name' => $recipient->name,
                        'first_name' => $recipient->first_name,
                        'last_name' => $recipient->last_name,
                    ];
                })->toArray(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    // ==================== Response Formatters ====================

    private function formatAccountResponse($account): array
    {
        return [
            'account_id' => $account->id,
            'account_id_guid' => $account->account_id_guid,
            'account_name' => $account->account_name,
            'organization' => $account->organization,
            'plan_id' => $account->plan_id,
            'plan_name' => $account->plan?->plan_name,
            'billing_period_start_date' => $account->billing_period_start_date?->toDateString(),
            'billing_period_end_date' => $account->billing_period_end_date?->toDateString(),
            'billing_period_envelopes_sent' => $account->billing_period_envelopes_sent,
            'billing_period_envelopes_allowed' => $account->billing_period_envelopes_allowed,
            'can_upgrade' => $account->can_upgrade,
            'suspension_status' => $account->suspension_status,
            'created_date' => $account->created_date?->toIso8601String(),
        ];
    }

    private function formatCustomFieldResponse($field): array
    {
        return [
            'field_id' => $field->field_id,
            'name' => $field->name,
            'display_name' => $field->display_name,
            'description' => $field->description,
            'field_type' => $field->field_type,
            'list_items' => $field->list_items,
            'required' => $field->required,
            'show' => $field->show,
            'max_length' => $field->max_length,
            'order' => $field->order,
        ];
    }

    private function formatConsumerDisclosureResponse($disclosure): array
    {
        return [
            'account_id' => $disclosure->account_id,
            'language_code' => $disclosure->language_code,
            'esign_text' => $disclosure->esign_text,
            'esign_agreement' => $disclosure->esign_agreement,
            'withdrawal_text' => $disclosure->withdrawal_text,
            'acceptance_text' => $disclosure->acceptance_text,
            'allow_cd_withdraw' => $disclosure->allow_cd_withdraw,
            'withdraw_address' => [
                'address_line_1' => $disclosure->withdraw_address_line_1,
                'address_line_2' => $disclosure->withdraw_address_line_2,
                'city' => $disclosure->withdraw_city,
                'state' => $disclosure->withdraw_state,
                'postal_code' => $disclosure->withdraw_postal_code,
                'country' => $disclosure->withdraw_country,
            ],
            'withdraw_email' => $disclosure->withdraw_email,
            'withdraw_phone' => $disclosure->withdraw_phone,
            'use_brand' => $disclosure->use_brand,
            'enable_esign' => $disclosure->enable_esign,
        ];
    }

    private function formatWatermarkResponse($watermark): array
    {
        return [
            'enabled' => $watermark->enabled,
            'watermark_text' => $watermark->watermark_text,
            'watermark_font' => $watermark->watermark_font,
            'watermark_font_size' => $watermark->watermark_font_size,
            'watermark_font_color' => $watermark->watermark_font_color,
            'watermark_transparency' => $watermark->watermark_transparency,
            'horizontal_alignment' => $watermark->horizontal_alignment,
            'vertical_alignment' => $watermark->vertical_alignment,
            'display_angle' => $watermark->display_angle,
            'angle' => $watermark->angle,
            'display_on_all_pages' => $watermark->display_on_all_pages,
        ];
    }
}
