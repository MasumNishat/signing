<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Http\Controllers\Controller;
use App\Services\IdentityVerificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class IdentityVerificationController extends Controller
{
    protected IdentityVerificationService $identityVerificationService;

    public function __construct(IdentityVerificationService $identityVerificationService)
    {
        $this->identityVerificationService = $identityVerificationService;
    }

    /**
     * Get the list of identity verification workflows for an account.
     *
     * GET /v2.1/accounts/{accountId}/identity_verification
     *
     * This method returns a list of Identity Verification workflows that are available to an account.
     * These workflows can be used to verify the identity of recipients before they can sign documents.
     *
     * @param Request $request
     * @param string $accountId
     * @return JsonResponse
     */
    public function getIdentityVerificationOptions(Request $request, string $accountId): JsonResponse
    {
        try {
            // Get status filter from query parameter
            $status = $request->query('identity_verification_workflow_status');

            // Get workflows from database
            $workflows = $this->identityVerificationService->getWorkflows((int) $accountId, $status);

            // If no workflows exist in database, return default workflows
            if ($workflows->isEmpty()) {
                $defaultWorkflows = $this->identityVerificationService->getDefaultWorkflows();

                return $this->success([
                    'identityVerification' => array_map(function ($workflow) use ($accountId) {
                        return $this->formatWorkflowResponse($workflow, $accountId);
                    }, $defaultWorkflows),
                ]);
            }

            return $this->success([
                'identityVerification' => $workflows->map(function ($workflow) {
                    return $this->formatWorkflowResponse($workflow);
                })->toArray(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Format workflow response.
     *
     * @param mixed $workflow Can be array or IdentityVerificationWorkflow model
     * @param int|null $accountId
     * @return array
     */
    private function formatWorkflowResponse($workflow, ?int $accountId = null): array
    {
        // Handle array (default workflows)
        if (is_array($workflow)) {
            return [
                'workflowId' => uniqid('wf_'),
                'workflowName' => $workflow['workflow_name'],
                'workflowType' => $workflow['workflow_type'],
                'workflowStatus' => 'active',
                'workflowLabel' => $workflow['workflow_label'] ?? null,
                'defaultName' => $workflow['default_name'] ?? null,
                'defaultDescription' => $workflow['default_description'] ?? null,
                'signatureProvider' => $workflow['signature_provider'] ?? null,
                'phoneAuthRecipientMayProvideNumber' => $workflow['phone_auth_recipient_may_provide_number'] ?? false,
                'idCheckConfigurationName' => $workflow['id_check_configuration_name'] ?? null,
                'smsAuthConfigurationName' => $workflow['sms_auth_configuration_name'] ?? null,
                'steps' => $workflow['steps'] ?? [],
                'inputOptions' => $workflow['input_options'] ?? [],
            ];
        }

        // Handle model
        return [
            'workflowId' => $workflow->workflow_id,
            'workflowName' => $workflow->workflow_name,
            'workflowType' => $workflow->workflow_type,
            'workflowStatus' => $workflow->workflow_status,
            'workflowLabel' => $workflow->workflow_label,
            'defaultName' => $workflow->default_name,
            'defaultDescription' => $workflow->default_description,
            'signatureProvider' => $workflow->signature_provider,
            'phoneAuthRecipientMayProvideNumber' => $workflow->phone_auth_recipient_may_provide_number,
            'idCheckConfigurationName' => $workflow->id_check_configuration_name,
            'smsAuthConfigurationName' => $workflow->sms_auth_configuration_name,
            'steps' => $workflow->steps,
            'inputOptions' => $workflow->input_options,
        ];
    }
}
