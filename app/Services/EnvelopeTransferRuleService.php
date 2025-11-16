<?php

namespace App\Services;

use App\Exceptions\Custom\BusinessLogicException;
use App\Exceptions\Custom\ResourceNotFoundException;
use App\Models\Account;
use App\Models\EnvelopeTransferRule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * EnvelopeTransferRuleService
 *
 * Business logic for managing envelope transfer rules.
 * Transfer rules automatically move envelopes from one user to another based on conditions.
 */
class EnvelopeTransferRuleService
{
    /**
     * Get all transfer rules for an account
     *
     * @param Account $account
     * @param array $filters
     * @return Collection
     */
    public function getTransferRules(Account $account, array $filters = []): Collection
    {
        $query = EnvelopeTransferRule::where('account_id', $account->id);

        // Filter by enabled status
        if (isset($filters['enabled'])) {
            $query->where('enabled', filter_var($filters['enabled'], FILTER_VALIDATE_BOOLEAN));
        }

        // Filter by from_user
        if (!empty($filters['from_user_id'])) {
            $query->where('from_user_id', $filters['from_user_id']);
        }

        // Filter by to_user
        if (!empty($filters['to_user_id'])) {
            $query->where('to_user_id', $filters['to_user_id']);
        }

        return $query->with(['fromUser', 'toUser'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get a specific transfer rule
     *
     * @param Account $account
     * @param string $ruleId
     * @return EnvelopeTransferRule
     * @throws ResourceNotFoundException
     */
    public function getTransferRule(Account $account, string $ruleId): EnvelopeTransferRule
    {
        $rule = EnvelopeTransferRule::where('account_id', $account->id)
            ->where('rule_id', $ruleId)
            ->with(['fromUser', 'toUser'])
            ->first();

        if (!$rule) {
            throw new ResourceNotFoundException('Transfer rule not found');
        }

        return $rule;
    }

    /**
     * Create a new transfer rule
     *
     * @param Account $account
     * @param array $data
     * @return EnvelopeTransferRule
     * @throws BusinessLogicException
     */
    public function createTransferRule(Account $account, array $data): EnvelopeTransferRule
    {
        DB::beginTransaction();

        try {
            // Validate that either from_user_id or from_group_id is provided
            if (empty($data['from_user_id']) && empty($data['from_group_id'])) {
                throw new BusinessLogicException('Either from_user_id or from_group_id must be provided');
            }

            // Validate that either to_user_id or to_group_id is provided
            if (empty($data['to_user_id']) && empty($data['to_group_id'])) {
                throw new BusinessLogicException('Either to_user_id or to_group_id must be provided');
            }

            $rule = EnvelopeTransferRule::create([
                'account_id' => $account->id,
                'rule_id' => $data['rule_id'] ?? null, // Auto-generated if null
                'rule_name' => $data['rule_name'] ?? null,
                'enabled' => $data['enabled'] ?? true,
                'from_user_id' => $data['from_user_id'] ?? null,
                'to_user_id' => $data['to_user_id'] ?? null,
                'from_group_id' => $data['from_group_id'] ?? null,
                'to_group_id' => $data['to_group_id'] ?? null,
                'modified_start_date' => $data['modified_start_date'] ?? null,
                'modified_end_date' => $data['modified_end_date'] ?? null,
                'envelope_types' => $data['envelope_types'] ?? null,
            ]);

            DB::commit();

            return $rule->fresh(['fromUser', 'toUser']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Transfer rule creation failed', [
                'account_id' => $account->account_id,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to create transfer rule: ' . $e->getMessage());
        }
    }

    /**
     * Update a transfer rule
     *
     * @param EnvelopeTransferRule $rule
     * @param array $data
     * @return EnvelopeTransferRule
     * @throws BusinessLogicException
     */
    public function updateTransferRule(EnvelopeTransferRule $rule, array $data): EnvelopeTransferRule
    {
        try {
            $rule->update([
                'rule_name' => $data['rule_name'] ?? $rule->rule_name,
                'enabled' => $data['enabled'] ?? $rule->enabled,
                'from_user_id' => $data['from_user_id'] ?? $rule->from_user_id,
                'to_user_id' => $data['to_user_id'] ?? $rule->to_user_id,
                'from_group_id' => $data['from_group_id'] ?? $rule->from_group_id,
                'to_group_id' => $data['to_group_id'] ?? $rule->to_group_id,
                'modified_start_date' => $data['modified_start_date'] ?? $rule->modified_start_date,
                'modified_end_date' => $data['modified_end_date'] ?? $rule->modified_end_date,
                'envelope_types' => $data['envelope_types'] ?? $rule->envelope_types,
            ]);

            return $rule->fresh(['fromUser', 'toUser']);
        } catch (\Exception $e) {
            Log::error('Transfer rule update failed', [
                'rule_id' => $rule->rule_id,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to update transfer rule: ' . $e->getMessage());
        }
    }

    /**
     * Delete a transfer rule
     *
     * @param EnvelopeTransferRule $rule
     * @return bool
     * @throws BusinessLogicException
     */
    public function deleteTransferRule(EnvelopeTransferRule $rule): bool
    {
        try {
            return $rule->delete();
        } catch (\Exception $e) {
            Log::error('Transfer rule deletion failed', [
                'rule_id' => $rule->rule_id,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to delete transfer rule: ' . $e->getMessage());
        }
    }

    /**
     * Bulk update transfer rules
     *
     * @param Account $account
     * @param array $rulesData
     * @return Collection
     * @throws BusinessLogicException
     */
    public function bulkUpdateTransferRules(Account $account, array $rulesData): Collection
    {
        DB::beginTransaction();

        try {
            $updatedRules = collect();

            foreach ($rulesData as $ruleData) {
                if (empty($ruleData['rule_id'])) {
                    throw new BusinessLogicException('rule_id is required for bulk update');
                }

                $rule = $this->getTransferRule($account, $ruleData['rule_id']);
                $rule = $this->updateTransferRule($rule, $ruleData);
                $updatedRules->push($rule);
            }

            DB::commit();

            return $updatedRules;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk transfer rule update failed', [
                'account_id' => $account->account_id,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to bulk update transfer rules: ' . $e->getMessage());
        }
    }

    /**
     * Find applicable transfer rules for an envelope
     *
     * Helper method to find which transfer rules apply to a specific envelope
     *
     * @param Account $account
     * @param int $fromUserId
     * @param string|null $envelopeType
     * @return Collection
     */
    public function findApplicableRules(Account $account, int $fromUserId, ?string $envelopeType = null): Collection
    {
        $query = EnvelopeTransferRule::forAccount($account->id)
            ->active()
            ->fromUser($fromUserId);

        $rules = $query->with(['toUser'])->get();

        // Filter by envelope type if provided
        if ($envelopeType) {
            $rules = $rules->filter(function ($rule) use ($envelopeType) {
                return $rule->appliesToEnvelopeType($envelopeType);
            });
        }

        return $rules;
    }
}
