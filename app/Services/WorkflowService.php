<?php

namespace App\Services;

use App\Models\Envelope;
use App\Models\EnvelopeWorkflow;
use App\Models\EnvelopeWorkflowStep;
use App\Models\EnvelopeRecipient;
use App\Exceptions\Custom\BusinessLogicException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Workflow Service
 *
 * Handles all business logic for envelope workflow and routing operations.
 * Manages sequential/parallel routing, scheduled sending, and workflow progression.
 */
class WorkflowService
{
    /**
     * Initialize workflow from envelope recipients
     *
     * @param Envelope $envelope
     * @param string|null $routingType Override routing type (auto-detected if null)
     * @return EnvelopeWorkflow
     */
    public function initializeWorkflow(Envelope $envelope, ?string $routingType = null): EnvelopeWorkflow
    {
        DB::beginTransaction();

        try {
            // Determine routing type from recipients if not specified
            if ($routingType === null) {
                $routingType = $this->detectRoutingType($envelope);
            }

            // Create or update workflow
            $workflow = $envelope->workflow;
            if (!$workflow) {
                $workflow = new EnvelopeWorkflow();
                $workflow->envelope_id = $envelope->id;
            }

            $workflow->workflow_status = EnvelopeWorkflow::STATUS_NOT_STARTED;
            $workflow->routing_type = $routingType;
            $workflow->current_routing_order = 1;
            $workflow->auto_navigation = true;
            $workflow->save();

            // Create workflow steps from recipients
            $this->createStepsFromRecipients($envelope, $workflow);

            DB::commit();

            return $workflow->fresh(['steps']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to initialize workflow', [
                'envelope_id' => $envelope->envelope_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Detect routing type from recipients
     *
     * @param Envelope $envelope
     * @return string
     */
    protected function detectRoutingType(Envelope $envelope): string
    {
        $recipients = $envelope->recipients;

        // Check if all recipients have same routing order (parallel)
        $routingOrders = $recipients->pluck('routing_order')->unique();

        if ($routingOrders->count() === 1) {
            return EnvelopeWorkflow::ROUTING_PARALLEL;
        }

        // Check if routing orders are sequential (1, 2, 3, ...)
        $maxOrder = $routingOrders->max();
        $minOrder = $routingOrders->min();

        if ($maxOrder - $minOrder + 1 === $routingOrders->count()) {
            // Check if multiple recipients at some routing orders
            $hasDuplicates = $recipients->groupBy('routing_order')
                ->filter(function ($group) {
                    return $group->count() > 1;
                })
                ->isNotEmpty();

            return $hasDuplicates ? EnvelopeWorkflow::ROUTING_MIXED : EnvelopeWorkflow::ROUTING_SEQUENTIAL;
        }

        return EnvelopeWorkflow::ROUTING_MIXED;
    }

    /**
     * Create workflow steps from recipients
     *
     * @param Envelope $envelope
     * @param EnvelopeWorkflow $workflow
     * @return void
     */
    protected function createStepsFromRecipients(Envelope $envelope, EnvelopeWorkflow $workflow): void
    {
        // Delete existing steps
        $workflow->steps()->delete();

        $recipients = $envelope->recipients()->orderBy('routing_order')->orderBy('id')->get();

        $order = 1;
        foreach ($recipients as $recipient) {
            $step = new EnvelopeWorkflowStep();
            $step->envelope_id = $envelope->id;
            $step->workflow_id = $workflow->id;
            $step->recipient_id = $recipient->id;
            $step->action = $this->getActionFromRecipientType($recipient->recipient_type);
            $step->routing_order = $recipient->routing_order;
            $step->status = EnvelopeWorkflowStep::STATUS_PENDING;
            $step->order = $order++;
            $step->save();
        }
    }

    /**
     * Get workflow action from recipient type
     *
     * @param string $recipientType
     * @return string
     */
    protected function getActionFromRecipientType(string $recipientType): string
    {
        return match ($recipientType) {
            EnvelopeRecipient::TYPE_SIGNER, EnvelopeRecipient::TYPE_IN_PERSON_SIGNER => EnvelopeWorkflowStep::ACTION_SIGN,
            EnvelopeRecipient::TYPE_CARBON_COPY => EnvelopeWorkflowStep::ACTION_RECEIVE_COPY,
            EnvelopeRecipient::TYPE_CERTIFIED_DELIVERY => EnvelopeWorkflowStep::ACTION_CERTIFY,
            EnvelopeRecipient::TYPE_AGENT => EnvelopeWorkflowStep::ACTION_DELEGATE,
            default => EnvelopeWorkflowStep::ACTION_VIEW,
        };
    }

    /**
     * Start workflow (begin routing to recipients)
     *
     * @param Envelope $envelope
     * @param \DateTime|null $scheduledDate Optional scheduled sending date
     * @return EnvelopeWorkflow
     * @throws BusinessLogicException
     */
    public function startWorkflow(Envelope $envelope, ?\DateTime $scheduledDate = null): EnvelopeWorkflow
    {
        $workflow = $envelope->workflow;

        if (!$workflow) {
            throw new BusinessLogicException('Workflow not initialized. Call initializeWorkflow first.');
        }

        if ($workflow->isInProgress()) {
            throw new BusinessLogicException('Workflow already in progress');
        }

        if ($workflow->isCompleted()) {
            throw new BusinessLogicException('Workflow already completed');
        }

        DB::beginTransaction();

        try {
            if ($scheduledDate && $scheduledDate > now()) {
                // Schedule for later
                $workflow->workflow_status = EnvelopeWorkflow::STATUS_PAUSED;
                $workflow->scheduled_sending_resume_date = $scheduledDate;
                $workflow->save();
            } else {
                // Start immediately
                $workflow->start();

                // Trigger first routing order
                $this->triggerRoutingOrder($envelope, 1);
            }

            DB::commit();

            return $workflow->fresh(['steps']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Trigger all steps for a specific routing order
     *
     * @param Envelope $envelope
     * @param int $routingOrder
     * @return void
     */
    protected function triggerRoutingOrder(Envelope $envelope, int $routingOrder): void
    {
        $steps = $envelope->workflowSteps()
            ->byRoutingOrder($routingOrder)
            ->pending()
            ->get();

        foreach ($steps as $step) {
            $step->markAsTriggered();

            // TODO: Send notification to recipient
            Log::info('Workflow step triggered', [
                'envelope_id' => $envelope->envelope_id,
                'recipient_id' => $step->recipient->recipient_id,
                'routing_order' => $routingOrder,
            ]);
        }
    }

    /**
     * Progress workflow after recipient completes their action
     *
     * @param EnvelopeRecipient $recipient
     * @return bool Returns true if workflow progressed
     * @throws BusinessLogicException
     */
    public function progressWorkflow(EnvelopeRecipient $recipient): bool
    {
        $envelope = $recipient->envelope;
        $workflow = $envelope->workflow;

        if (!$workflow || !$workflow->isInProgress()) {
            return false;
        }

        DB::beginTransaction();

        try {
            // Mark recipient's workflow step as completed
            $step = $envelope->workflowSteps()
                ->where('recipient_id', $recipient->id)
                ->inProgress()
                ->first();

            if ($step) {
                if ($recipient->hasDeclined()) {
                    $step->markAsDeclined();
                    // Decline entire workflow
                    $this->cancelWorkflow($envelope, 'Recipient declined');
                    DB::commit();
                    return true;
                } else {
                    $step->markAsCompleted();
                }
            }

            // Check if current routing order is complete
            $currentOrder = $workflow->current_routing_order;
            $remainingInOrder = $envelope->workflowSteps()
                ->byRoutingOrder($currentOrder)
                ->where('status', '!=', EnvelopeWorkflowStep::STATUS_COMPLETED)
                ->count();

            if ($remainingInOrder === 0) {
                // All recipients in current order completed
                // Check if there are more routing orders
                $nextOrder = $currentOrder + 1;
                $hasNextOrder = $envelope->workflowSteps()
                    ->byRoutingOrder($nextOrder)
                    ->exists();

                if ($hasNextOrder) {
                    // Move to next routing order
                    $workflow->moveToNextRoutingOrder();
                    $this->triggerRoutingOrder($envelope, $nextOrder);
                } else {
                    // Workflow complete
                    $workflow->complete();
                    $envelope->markAsCompleted();
                }
            }

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to progress workflow', [
                'envelope_id' => $envelope->envelope_id,
                'recipient_id' => $recipient->recipient_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Pause workflow
     *
     * @param Envelope $envelope
     * @param \DateTime|null $resumeDate Optional resume date
     * @return EnvelopeWorkflow
     * @throws BusinessLogicException
     */
    public function pauseWorkflow(Envelope $envelope, ?\DateTime $resumeDate = null): EnvelopeWorkflow
    {
        $workflow = $envelope->workflow;

        if (!$workflow) {
            throw new BusinessLogicException('Workflow not found');
        }

        if (!$workflow->isInProgress()) {
            throw new BusinessLogicException('Cannot pause workflow that is not in progress');
        }

        DB::beginTransaction();

        try {
            $workflow->pause();

            if ($resumeDate) {
                $workflow->scheduled_sending_resume_date = $resumeDate;
                $workflow->save();
            }

            DB::commit();

            return $workflow;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Resume paused workflow
     *
     * @param Envelope $envelope
     * @return EnvelopeWorkflow
     * @throws BusinessLogicException
     */
    public function resumeWorkflow(Envelope $envelope): EnvelopeWorkflow
    {
        $workflow = $envelope->workflow;

        if (!$workflow) {
            throw new BusinessLogicException('Workflow not found');
        }

        if (!$workflow->isPaused()) {
            throw new BusinessLogicException('Workflow is not paused');
        }

        DB::beginTransaction();

        try {
            $workflow->resume();
            $workflow->scheduled_sending_resume_date = null;
            $workflow->save();

            // Check if we need to trigger routing order
            $currentOrder = $workflow->current_routing_order;
            $hasActiveSteps = $envelope->workflowSteps()
                ->byRoutingOrder($currentOrder)
                ->inProgress()
                ->exists();

            if (!$hasActiveSteps) {
                // Re-trigger current routing order
                $this->triggerRoutingOrder($envelope, $currentOrder);
            }

            DB::commit();

            return $workflow->fresh(['steps']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cancel workflow
     *
     * @param Envelope $envelope
     * @param string|null $reason
     * @return EnvelopeWorkflow
     * @throws BusinessLogicException
     */
    public function cancelWorkflow(Envelope $envelope, ?string $reason = null): EnvelopeWorkflow
    {
        $workflow = $envelope->workflow;

        if (!$workflow) {
            throw new BusinessLogicException('Workflow not found');
        }

        if ($workflow->isCompleted()) {
            throw new BusinessLogicException('Cannot cancel completed workflow');
        }

        DB::beginTransaction();

        try {
            $workflow->cancel();

            // Mark all pending steps as failed
            $envelope->workflowSteps()
                ->pending()
                ->orWhere('status', EnvelopeWorkflowStep::STATUS_IN_PROGRESS)
                ->update(['status' => EnvelopeWorkflowStep::STATUS_FAILED]);

            // Void envelope if applicable
            if ($envelope->isSent() || $envelope->status === 'delivered') {
                $envelope->markAsVoided($reason ?? 'Workflow cancelled');
            }

            DB::commit();

            return $workflow;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get current active recipients (those at current routing order)
     *
     * @param Envelope $envelope
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCurrentActiveRecipients(Envelope $envelope)
    {
        $workflow = $envelope->workflow;

        if (!$workflow || !$workflow->isInProgress()) {
            return collect();
        }

        $currentOrder = $workflow->current_routing_order;

        return $envelope->recipients()
            ->where('routing_order', $currentOrder)
            ->get();
    }

    /**
     * Get pending recipients (those at higher routing orders)
     *
     * @param Envelope $envelope
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPendingRecipients(Envelope $envelope)
    {
        $workflow = $envelope->workflow;

        if (!$workflow || !$workflow->isInProgress()) {
            return collect();
        }

        $currentOrder = $workflow->current_routing_order;

        return $envelope->recipients()
            ->where('routing_order', '>', $currentOrder)
            ->orderBy('routing_order')
            ->get();
    }

    /**
     * Get completed recipients
     *
     * @param Envelope $envelope
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCompletedRecipients(Envelope $envelope)
    {
        return $envelope->recipients()
            ->where('status', EnvelopeRecipient::STATUS_SIGNED)
            ->orWhere('status', EnvelopeRecipient::STATUS_COMPLETED)
            ->get();
    }

    /**
     * Check if recipient can currently act (based on routing order)
     *
     * @param EnvelopeRecipient $recipient
     * @return bool
     */
    public function canRecipientAct(EnvelopeRecipient $recipient): bool
    {
        $workflow = $recipient->envelope->workflow;

        if (!$workflow || !$workflow->isInProgress()) {
            // No workflow or not in progress - recipient can act
            return true;
        }

        // Check if workflow uses parallel routing (anyone can act)
        if ($workflow->isParallel()) {
            return true;
        }

        // Check if recipient is at current routing order
        return $recipient->routing_order === $workflow->current_routing_order;
    }

    /**
     * Process scheduled workflows (cron job)
     *
     * @return int Number of workflows resumed
     */
    public function processScheduledWorkflows(): int
    {
        $workflows = EnvelopeWorkflow::readyToResume()->get();

        $count = 0;
        foreach ($workflows as $workflow) {
            try {
                $this->resumeWorkflow($workflow->envelope);
                $count++;
            } catch (\Exception $e) {
                Log::error('Failed to resume scheduled workflow', [
                    'workflow_id' => $workflow->id,
                    'envelope_id' => $workflow->envelope->envelope_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    /**
     * Get workflow status metadata
     *
     * @param Envelope $envelope
     * @return array
     */
    public function getWorkflowStatus(Envelope $envelope): array
    {
        $workflow = $envelope->workflow;

        if (!$workflow) {
            return [
                'has_workflow' => false,
            ];
        }

        $steps = $workflow->steps()
            ->with('recipient')
            ->orderedByExecution()
            ->get();

        $stepsData = $steps->map(function ($step) {
            return [
                'step_id' => $step->id,
                'action' => $step->action,
                'routing_order' => $step->routing_order,
                'recipient_id' => $step->recipient->recipient_id,
                'recipient_name' => $step->recipient->name,
                'recipient_email' => $step->recipient->email,
                'status' => $step->status,
                'triggered_at' => $step->triggered_date_time?->toIso8601String(),
                'completed_at' => $step->completed_date_time?->toIso8601String(),
            ];
        });

        return [
            'has_workflow' => true,
            'status' => $workflow->workflow_status,
            'routing_type' => $workflow->routing_type,
            'current_routing_order' => $workflow->current_routing_order,
            'scheduled_sending' => [
                'enabled' => $workflow->hasScheduledSending(),
                'resume_date' => $workflow->scheduled_sending_resume_date?->toIso8601String(),
                'time_reached' => $workflow->isScheduledTimeReached(),
            ],
            'auto_navigation' => $workflow->auto_navigation,
            'total_steps' => $steps->count(),
            'completed_steps' => $steps->where('status', EnvelopeWorkflowStep::STATUS_COMPLETED)->count(),
            'pending_steps' => $steps->where('status', EnvelopeWorkflowStep::STATUS_PENDING)->count(),
            'in_progress_steps' => $steps->where('status', EnvelopeWorkflowStep::STATUS_IN_PROGRESS)->count(),
            'steps' => $stepsData,
        ];
    }
}
