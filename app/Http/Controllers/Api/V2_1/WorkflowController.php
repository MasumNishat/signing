<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Http\Controllers\Api\BaseController;
use App\Models\Account;
use App\Models\Envelope;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Workflow Controller
 *
 * Handles envelope workflow and routing operations.
 * Supports sequential/parallel routing, scheduled sending, and workflow progression.
 *
 * Endpoints:
 * - POST   /envelopes/{id}/workflow/start     - Start workflow
 * - POST   /envelopes/{id}/workflow/pause     - Pause workflow
 * - POST   /envelopes/{id}/workflow/resume    - Resume workflow
 * - POST   /envelopes/{id}/workflow/cancel    - Cancel workflow
 * - GET    /envelopes/{id}/workflow/status    - Get workflow status
 * - GET    /envelopes/{id}/workflow/recipients/current - Get current active recipients
 * - GET    /envelopes/{id}/workflow/recipients/pending - Get pending recipients
 */
class WorkflowController extends BaseController
{
    /**
     * Workflow service
     */
    protected WorkflowService $workflowService;

    /**
     * Initialize controller
     */
    public function __construct(WorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    /**
     * Initialize and start workflow
     *
     * POST /v2.1/accounts/{accountId}/envelopes/{envelopeId}/workflow/start
     *
     * @param Request $request
     * @param string $accountId
     * @param string $envelopeId
     * @return JsonResponse
     */
    public function start(Request $request, string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'routing_type' => 'nullable|string|in:sequential,parallel,mixed',
            'scheduled_sending' => 'nullable|array',
            'scheduled_sending.resume_date' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            // Initialize workflow if not exists
            if (!$envelope->workflow) {
                $this->workflowService->initializeWorkflow(
                    $envelope,
                    $request->input('routing_type')
                );
                $envelope->refresh();
            }

            // Start workflow
            $scheduledDate = null;
            if ($request->has('scheduled_sending.resume_date')) {
                $scheduledDate = new \DateTime($request->input('scheduled_sending.resume_date'));
            }

            $workflow = $this->workflowService->startWorkflow($envelope, $scheduledDate);

            $status = $this->workflowService->getWorkflowStatus($envelope);

            return $this->success([
                'envelope_id' => $envelope->envelope_id,
                'workflow' => $status,
                'message' => $scheduledDate ? 'Workflow scheduled successfully' : 'Workflow started successfully',
            ], 'Workflow operation completed');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Pause workflow
     *
     * POST /v2.1/accounts/{accountId}/envelopes/{envelopeId}/workflow/pause
     *
     * @param Request $request
     * @param string $accountId
     * @param string $envelopeId
     * @return JsonResponse
     */
    public function pause(Request $request, string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'resume_date' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $resumeDate = null;
            if ($request->has('resume_date')) {
                $resumeDate = new \DateTime($request->input('resume_date'));
            }

            $this->workflowService->pauseWorkflow($envelope, $resumeDate);

            $status = $this->workflowService->getWorkflowStatus($envelope);

            return $this->success([
                'envelope_id' => $envelope->envelope_id,
                'workflow' => $status,
            ], 'Workflow paused successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Resume paused workflow
     *
     * POST /v2.1/accounts/{accountId}/envelopes/{envelopeId}/workflow/resume
     *
     * @param string $accountId
     * @param string $envelopeId
     * @return JsonResponse
     */
    public function resume(string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        try {
            $this->workflowService->resumeWorkflow($envelope);

            $status = $this->workflowService->getWorkflowStatus($envelope);

            return $this->success([
                'envelope_id' => $envelope->envelope_id,
                'workflow' => $status,
            ], 'Workflow resumed successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Cancel workflow
     *
     * POST /v2.1/accounts/{accountId}/envelopes/{envelopeId}/workflow/cancel
     *
     * @param Request $request
     * @param string $accountId
     * @param string $envelopeId
     * @return JsonResponse
     */
    public function cancel(Request $request, string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $reason = $request->input('reason', 'Workflow cancelled by sender');

            $this->workflowService->cancelWorkflow($envelope, $reason);

            $status = $this->workflowService->getWorkflowStatus($envelope);

            return $this->success([
                'envelope_id' => $envelope->envelope_id,
                'workflow' => $status,
                'reason' => $reason,
            ], 'Workflow cancelled successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get workflow status
     *
     * GET /v2.1/accounts/{accountId}/envelopes/{envelopeId}/workflow/status
     *
     * @param string $accountId
     * @param string $envelopeId
     * @return JsonResponse
     */
    public function status(string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $status = $this->workflowService->getWorkflowStatus($envelope);

        return $this->success([
            'envelope_id' => $envelope->envelope_id,
            'workflow' => $status,
        ], 'Workflow status retrieved successfully');
    }

    /**
     * Get current active recipients
     *
     * GET /v2.1/accounts/{accountId}/envelopes/{envelopeId}/workflow/recipients/current
     *
     * @param string $accountId
     * @param string $envelopeId
     * @return JsonResponse
     */
    public function currentRecipients(string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $recipients = $this->workflowService->getCurrentActiveRecipients($envelope);

        $formattedRecipients = $recipients->map(function ($recipient) {
            return [
                'recipient_id' => $recipient->recipient_id,
                'recipient_type' => $recipient->recipient_type,
                'name' => $recipient->name,
                'email' => $recipient->email,
                'routing_order' => $recipient->routing_order,
                'status' => $recipient->status,
            ];
        });

        return $this->success([
            'envelope_id' => $envelope->envelope_id,
            'current_routing_order' => $envelope->workflow?->current_routing_order,
            'current_recipients' => $formattedRecipients,
        ], 'Current active recipients retrieved successfully');
    }

    /**
     * Get pending recipients
     *
     * GET /v2.1/accounts/{accountId}/envelopes/{envelopeId}/workflow/recipients/pending
     *
     * @param string $accountId
     * @param string $envelopeId
     * @return JsonResponse
     */
    public function pendingRecipients(string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $recipients = $this->workflowService->getPendingRecipients($envelope);

        $formattedRecipients = $recipients->map(function ($recipient) {
            return [
                'recipient_id' => $recipient->recipient_id,
                'recipient_type' => $recipient->recipient_type,
                'name' => $recipient->name,
                'email' => $recipient->email,
                'routing_order' => $recipient->routing_order,
                'status' => $recipient->status,
            ];
        });

        return $this->success([
            'envelope_id' => $envelope->envelope_id,
            'current_routing_order' => $envelope->workflow?->current_routing_order,
            'pending_recipients' => $formattedRecipients,
        ], 'Pending recipients retrieved successfully');
    }
}
