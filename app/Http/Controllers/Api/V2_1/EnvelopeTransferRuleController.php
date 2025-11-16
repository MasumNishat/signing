<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Models\Account;
use App\Models\EnvelopeTransferRule;
use App\Services\EnvelopeTransferRuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * EnvelopeTransferRuleController
 *
 * Handles envelope transfer rule operations.
 * Supports 5 endpoints for managing automatic envelope transfers.
 */
class EnvelopeTransferRuleController extends BaseController
{
    protected EnvelopeTransferRuleService $transferRuleService;

    public function __construct(EnvelopeTransferRuleService $transferRuleService)
    {
        $this->transferRuleService = $transferRuleService;
    }

    /**
     * GET /accounts/{accountId}/envelopes/transfer_rules
     *
     * Get all transfer rules for an account
     */
    public function index(Request $request, string $accountId): JsonResponse
    {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();

            $filters = [
                'enabled' => $request->query('enabled'),
                'from_user_id' => $request->query('from_user_id'),
                'to_user_id' => $request->query('to_user_id'),
            ];

            $rules = $this->transferRuleService->getTransferRules($account, $filters);

            return $this->successResponse([
                'transfer_rules' => $rules->map(function ($rule) {
                    return $this->formatTransferRule($rule);
                }),
                'result_set_size' => $rules->count(),
            ], 'Transfer rules retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /accounts/{accountId}/envelopes/transfer_rules
     *
     * Create a new transfer rule
     */
    public function store(Request $request, string $accountId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'rule_id' => 'sometimes|string|max:100',
                'rule_name' => 'sometimes|string|max:255',
                'enabled' => 'sometimes|boolean',
                'from_user_id' => 'required_without:from_group_id|integer|exists:users,id',
                'to_user_id' => 'required_without:to_group_id|integer|exists:users,id',
                'from_group_id' => 'required_without:from_user_id|integer',
                'to_group_id' => 'required_without:to_user_id|integer',
                'modified_start_date' => 'sometimes|date',
                'modified_end_date' => 'sometimes|date|after_or_equal:modified_start_date',
                'envelope_types' => 'sometimes|array',
                'envelope_types.*' => 'string|max:50',
            ]);

            $account = Account::where('account_id', $accountId)->firstOrFail();

            $rule = $this->transferRuleService->createTransferRule($account, $validated);

            return $this->createdResponse(
                $this->formatTransferRule($rule),
                'Transfer rule created successfully'
            );
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * PUT /accounts/{accountId}/envelopes/transfer_rules
     *
     * Bulk update transfer rules
     */
    public function bulkUpdate(Request $request, string $accountId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'transfer_rules' => 'required|array|min:1',
                'transfer_rules.*.rule_id' => 'required|string|max:100',
                'transfer_rules.*.rule_name' => 'sometimes|string|max:255',
                'transfer_rules.*.enabled' => 'sometimes|boolean',
                'transfer_rules.*.from_user_id' => 'sometimes|integer|exists:users,id',
                'transfer_rules.*.to_user_id' => 'sometimes|integer|exists:users,id',
                'transfer_rules.*.from_group_id' => 'sometimes|integer',
                'transfer_rules.*.to_group_id' => 'sometimes|integer',
                'transfer_rules.*.modified_start_date' => 'sometimes|date',
                'transfer_rules.*.modified_end_date' => 'sometimes|date',
                'transfer_rules.*.envelope_types' => 'sometimes|array',
            ]);

            $account = Account::where('account_id', $accountId)->firstOrFail();

            $rules = $this->transferRuleService->bulkUpdateTransferRules($account, $validated['transfer_rules']);

            return $this->successResponse([
                'transfer_rules' => $rules->map(function ($rule) {
                    return $this->formatTransferRule($rule);
                }),
                'result_set_size' => $rules->count(),
            ], 'Transfer rules updated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * PUT /accounts/{accountId}/envelopes/transfer_rules/{ruleId}
     *
     * Update a specific transfer rule
     */
    public function update(Request $request, string $accountId, string $ruleId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'rule_name' => 'sometimes|string|max:255',
                'enabled' => 'sometimes|boolean',
                'from_user_id' => 'sometimes|integer|exists:users,id',
                'to_user_id' => 'sometimes|integer|exists:users,id',
                'from_group_id' => 'sometimes|integer',
                'to_group_id' => 'sometimes|integer',
                'modified_start_date' => 'sometimes|date',
                'modified_end_date' => 'sometimes|date|after_or_equal:modified_start_date',
                'envelope_types' => 'sometimes|array',
                'envelope_types.*' => 'string|max:50',
            ]);

            $account = Account::where('account_id', $accountId)->firstOrFail();

            $rule = $this->transferRuleService->getTransferRule($account, $ruleId);
            $rule = $this->transferRuleService->updateTransferRule($rule, $validated);

            return $this->successResponse(
                $this->formatTransferRule($rule),
                'Transfer rule updated successfully'
            );
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * DELETE /accounts/{accountId}/envelopes/transfer_rules/{ruleId}
     *
     * Delete a specific transfer rule
     */
    public function destroy(string $accountId, string $ruleId): JsonResponse
    {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();

            $rule = $this->transferRuleService->getTransferRule($account, $ruleId);
            $this->transferRuleService->deleteTransferRule($rule);

            return $this->noContent();
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Format transfer rule for response
     *
     * @param EnvelopeTransferRule $rule
     * @return array
     */
    protected function formatTransferRule(EnvelopeTransferRule $rule): array
    {
        return [
            'rule_id' => $rule->rule_id,
            'rule_name' => $rule->rule_name,
            'enabled' => $rule->enabled,
            'from_user' => $rule->fromUser ? [
                'user_id' => $rule->fromUser->user_id,
                'name' => $rule->fromUser->first_name . ' ' . $rule->fromUser->last_name,
                'email' => $rule->fromUser->email,
            ] : null,
            'to_user' => $rule->toUser ? [
                'user_id' => $rule->toUser->user_id,
                'name' => $rule->toUser->first_name . ' ' . $rule->toUser->last_name,
                'email' => $rule->toUser->email,
            ] : null,
            'from_group_id' => $rule->from_group_id,
            'to_group_id' => $rule->to_group_id,
            'modified_start_date' => $rule->modified_start_date?->toIso8601String(),
            'modified_end_date' => $rule->modified_end_date?->toIso8601String(),
            'envelope_types' => $rule->envelope_types,
            'is_active' => $rule->isActive(),
            'created_at' => $rule->created_at?->toIso8601String(),
            'updated_at' => $rule->updated_at?->toIso8601String(),
        ];
    }
}
