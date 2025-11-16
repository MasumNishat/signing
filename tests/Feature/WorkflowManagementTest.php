<?php

namespace Tests\Feature;

use App\Models\Envelope;
use App\Models\EnvelopeWorkflow;
use App\Models\EnvelopeWorkflowStep;
use App\Models\EnvelopeRecipient;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WorkflowManagementTest extends ApiTestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_can_get_envelope_workflow()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        EnvelopeWorkflow::create([
            'envelope_id' => $envelope->id,
            'workflow_status' => 'in_progress',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/workflow");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_update_envelope_workflow()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        EnvelopeWorkflow::create([
            'envelope_id' => $envelope->id,
            'workflow_status' => 'in_progress',
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/workflow", [
            'workflow_status' => 'paused',
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('envelope_workflow', [
            'envelope_id' => $envelope->id,
            'workflow_status' => 'paused',
        ]);
    }

    /** @test */
    public function test_can_get_workflow_steps()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        $recipient = EnvelopeRecipient::create([
            'envelope_id' => $envelope->id,
            'recipient_type' => 'signer',
            'routing_order' => 1,
            'email' => 'signer@example.com',
            'name' => 'Test Signer',
        ]);

        EnvelopeWorkflowStep::create([
            'envelope_id' => $envelope->id,
            'recipient_id' => $recipient->id,
            'step_number' => 1,
            'step_type' => 'sign',
            'status' => 'pending',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/workflow/steps");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_get_specific_workflow_step()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        $recipient = EnvelopeRecipient::create([
            'envelope_id' => $envelope->id,
            'recipient_type' => 'signer',
            'routing_order' => 1,
            'email' => 'signer@example.com',
            'name' => 'Test Signer',
        ]);

        $step = EnvelopeWorkflowStep::create([
            'envelope_id' => $envelope->id,
            'recipient_id' => $recipient->id,
            'step_number' => 1,
            'step_type' => 'sign',
            'status' => 'pending',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/workflow/steps/{$step->id}");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_update_workflow_step()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        $recipient = EnvelopeRecipient::create([
            'envelope_id' => $envelope->id,
            'recipient_type' => 'signer',
            'routing_order' => 1,
            'email' => 'signer@example.com',
            'name' => 'Test Signer',
        ]);

        $step = EnvelopeWorkflowStep::create([
            'envelope_id' => $envelope->id,
            'recipient_id' => $recipient->id,
            'step_number' => 1,
            'step_type' => 'sign',
            'status' => 'pending',
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/workflow/steps/{$step->id}", [
            'status' => 'completed',
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('envelope_workflow_steps', [
            'id' => $step->id,
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function test_supports_sequential_workflow()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Sequential Workflow',
            'status' => 'draft',
        ]);

        // Create recipients with different routing orders
        $recipient1 = EnvelopeRecipient::create(['envelope_id' => $envelope->id, 'recipient_type' => 'signer', 'routing_order' => 1, 'email' => 'first@example.com', 'name' => 'First']);
        $recipient2 = EnvelopeRecipient::create(['envelope_id' => $envelope->id, 'recipient_type' => 'signer', 'routing_order' => 2, 'email' => 'second@example.com', 'name' => 'Second']);

        // Create workflow steps
        EnvelopeWorkflowStep::create(['envelope_id' => $envelope->id, 'recipient_id' => $recipient1->id, 'step_number' => 1, 'step_type' => 'sign', 'status' => 'pending']);
        EnvelopeWorkflowStep::create(['envelope_id' => $envelope->id, 'recipient_id' => $recipient2->id, 'step_number' => 2, 'step_type' => 'sign', 'status' => 'pending']);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/workflow/steps");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonCount(2, 'data.steps');
    }

    /** @test */
    public function test_supports_parallel_workflow()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Parallel Workflow',
            'status' => 'draft',
        ]);

        // Create recipients with same routing order (parallel)
        $recipient1 = EnvelopeRecipient::create(['envelope_id' => $envelope->id, 'recipient_type' => 'signer', 'routing_order' => 1, 'email' => 'parallel1@example.com', 'name' => 'Parallel 1']);
        $recipient2 = EnvelopeRecipient::create(['envelope_id' => $envelope->id, 'recipient_type' => 'signer', 'routing_order' => 1, 'email' => 'parallel2@example.com', 'name' => 'Parallel 2']);

        EnvelopeWorkflowStep::create(['envelope_id' => $envelope->id, 'recipient_id' => $recipient1->id, 'step_number' => 1, 'step_type' => 'sign', 'status' => 'pending']);
        EnvelopeWorkflowStep::create(['envelope_id' => $envelope->id, 'recipient_id' => $recipient2->id, 'step_number' => 1, 'step_type' => 'sign', 'status' => 'pending']);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/workflow/steps");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_supports_mixed_workflow()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Mixed Workflow',
            'status' => 'draft',
        ]);

        // Step 1: Two parallel signers
        $recipient1 = EnvelopeRecipient::create(['envelope_id' => $envelope->id, 'recipient_type' => 'signer', 'routing_order' => 1, 'email' => 'parallel1@example.com', 'name' => 'Parallel 1']);
        $recipient2 = EnvelopeRecipient::create(['envelope_id' => $envelope->id, 'recipient_type' => 'signer', 'routing_order' => 1, 'email' => 'parallel2@example.com', 'name' => 'Parallel 2']);

        // Step 2: One approver (sequential after step 1)
        $recipient3 = EnvelopeRecipient::create(['envelope_id' => $envelope->id, 'recipient_type' => 'approver', 'routing_order' => 2, 'email' => 'approver@example.com', 'name' => 'Approver']);

        EnvelopeWorkflowStep::create(['envelope_id' => $envelope->id, 'recipient_id' => $recipient1->id, 'step_number' => 1, 'step_type' => 'sign', 'status' => 'pending']);
        EnvelopeWorkflowStep::create(['envelope_id' => $envelope->id, 'recipient_id' => $recipient2->id, 'step_number' => 1, 'step_type' => 'sign', 'status' => 'pending']);
        EnvelopeWorkflowStep::create(['envelope_id' => $envelope->id, 'recipient_id' => $recipient3->id, 'step_number' => 2, 'step_type' => 'approve', 'status' => 'pending']);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/workflow/steps");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonCount(3, 'data.steps');
    }

    /** @test */
    public function test_can_pause_workflow()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        $workflow = EnvelopeWorkflow::create([
            'envelope_id' => $envelope->id,
            'workflow_status' => 'in_progress',
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/workflow", [
            'workflow_status' => 'paused',
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('envelope_workflow', [
            'id' => $workflow->id,
            'workflow_status' => 'paused',
        ]);
    }

    /** @test */
    public function test_can_resume_paused_workflow()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        $workflow = EnvelopeWorkflow::create([
            'envelope_id' => $envelope->id,
            'workflow_status' => 'paused',
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/workflow", [
            'workflow_status' => 'in_progress',
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('envelope_workflow', [
            'id' => $workflow->id,
            'workflow_status' => 'in_progress',
        ]);
    }

    /** @test */
    public function test_supports_scheduled_sending()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Scheduled Envelope',
            'status' => 'draft',
        ]);

        $scheduledDate = now()->addDays(2);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/workflow", [
            'workflow_status' => 'paused',
            'resume_date' => $scheduledDate->toDateString(),
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('envelope_workflow', [
            'envelope_id' => $envelope->id,
            'resume_date' => $scheduledDate->toDateString(),
        ]);
    }

    /** @test */
    public function test_validates_workflow_status_transitions()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        EnvelopeWorkflow::create([
            'envelope_id' => $envelope->id,
            'workflow_status' => 'completed',
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/workflow", [
            'workflow_status' => 'in_progress',
        ]);

        $response->assertStatus(400);
        $this->assertErrorResponse();
    }

    /** @test */
    public function test_supports_different_step_types()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        $stepTypes = ['sign', 'approve', 'view', 'certify'];
        $routingOrder = 1;

        foreach ($stepTypes as $type) {
            $recipient = EnvelopeRecipient::create([
                'envelope_id' => $envelope->id,
                'recipient_type' => $type === 'approve' ? 'approver' : ($type === 'certify' ? 'certified_delivery' : ($type === 'view' ? 'viewer' : 'signer')),
                'routing_order' => $routingOrder++,
                'email' => "{$type}@example.com",
                'name' => ucfirst($type),
            ]);

            EnvelopeWorkflowStep::create([
                'envelope_id' => $envelope->id,
                'recipient_id' => $recipient->id,
                'step_number' => $routingOrder - 1,
                'step_type' => $type,
                'status' => 'pending',
            ]);
        }

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/workflow/steps");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonCount(4, 'data.steps');
    }
}
