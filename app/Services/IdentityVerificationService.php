<?php

namespace App\Services;

use App\Models\IdentityVerificationWorkflow;
use Illuminate\Support\Collection;

class IdentityVerificationService
{
    /**
     * Get all identity verification workflows for an account.
     *
     * @param int $accountId
     * @param string|null $status Filter by workflow status (active, inactive)
     * @return Collection
     */
    public function getWorkflows(int $accountId, ?string $status = null): Collection
    {
        return IdentityVerificationWorkflow::forAccount($accountId)
            ->byStatus($status)
            ->orderBy('workflow_name')
            ->get();
    }

    /**
     * Get a specific identity verification workflow.
     *
     * @param int $accountId
     * @param string $workflowId
     * @return IdentityVerificationWorkflow|null
     */
    public function getWorkflow(int $accountId, string $workflowId): ?IdentityVerificationWorkflow
    {
        return IdentityVerificationWorkflow::forAccount($accountId)
            ->where('workflow_id', $workflowId)
            ->first();
    }

    /**
     * Get the default available identity verification workflows.
     * In production, these would be configured per account.
     * This provides a basic set of common workflows.
     *
     * @return array
     */
    public function getDefaultWorkflows(): array
    {
        return [
            [
                'workflow_type' => IdentityVerificationWorkflow::TYPE_ID_CHECK,
                'workflow_name' => 'ID Verification',
                'workflow_label' => 'ID Check',
                'default_name' => 'ID Verification',
                'default_description' => 'Verify identity using government-issued ID document',
                'steps' => [
                    ['name' => 'upload_document', 'required' => true],
                    ['name' => 'verify_document', 'required' => true],
                ],
                'input_options' => [
                    'document_types' => ['passport', 'drivers_license', 'national_id'],
                ],
            ],
            [
                'workflow_type' => IdentityVerificationWorkflow::TYPE_PHONE_AUTH,
                'workflow_name' => 'Phone Authentication',
                'workflow_label' => 'Phone Auth',
                'default_name' => 'Phone Authentication',
                'default_description' => 'Verify identity using phone number authentication',
                'phone_auth_recipient_may_provide_number' => true,
                'steps' => [
                    ['name' => 'enter_phone', 'required' => true],
                    ['name' => 'send_code', 'required' => true],
                    ['name' => 'verify_code', 'required' => true],
                ],
            ],
            [
                'workflow_type' => IdentityVerificationWorkflow::TYPE_SMS_AUTH,
                'workflow_name' => 'SMS Authentication',
                'workflow_label' => 'SMS Auth',
                'default_name' => 'SMS Authentication',
                'default_description' => 'Verify identity using SMS code',
                'sms_auth_configuration_name' => 'default_sms_config',
                'steps' => [
                    ['name' => 'send_sms', 'required' => true],
                    ['name' => 'verify_code', 'required' => true],
                ],
            ],
            [
                'workflow_type' => IdentityVerificationWorkflow::TYPE_KNOWLEDGE_BASED,
                'workflow_name' => 'Knowledge-Based Authentication',
                'workflow_label' => 'KBA',
                'default_name' => 'Knowledge-Based Auth',
                'default_description' => 'Verify identity using personal knowledge questions',
                'steps' => [
                    ['name' => 'collect_info', 'required' => true],
                    ['name' => 'ask_questions', 'required' => true],
                    ['name' => 'verify_answers', 'required' => true],
                ],
                'input_options' => [
                    'question_count' => 5,
                    'pass_threshold' => 80,
                ],
            ],
            [
                'workflow_type' => IdentityVerificationWorkflow::TYPE_ID_LOOKUP,
                'workflow_name' => 'ID Lookup',
                'workflow_label' => 'ID Lookup',
                'default_name' => 'ID Lookup',
                'default_description' => 'Verify identity using database lookup',
                'id_check_configuration_name' => 'default_id_lookup',
                'steps' => [
                    ['name' => 'collect_info', 'required' => true],
                    ['name' => 'database_lookup', 'required' => true],
                    ['name' => 'verify_match', 'required' => true],
                ],
            ],
        ];
    }
}
