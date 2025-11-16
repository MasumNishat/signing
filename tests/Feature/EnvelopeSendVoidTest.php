<?php

namespace Tests\Feature;

use App\Models\Envelope;
use App\Models\EnvelopeDocument;
use App\Models\EnvelopeRecipient;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EnvelopeSendVoidTest extends ApiTestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_can_send_envelope_with_documents_and_recipients()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Contract to Sign',
            'status' => 'draft',
        ]);

        // Add document
        EnvelopeDocument::create([
            'envelope_id' => $envelope->id,
            'name' => 'contract.pdf',
            'file_extension' => 'pdf',
            'order' => 1,
        ]);

        // Add recipient
        EnvelopeRecipient::create([
            'envelope_id' => $envelope->id,
            'recipient_type' => 'signer',
            'routing_order' => 1,
            'email' => 'signer@example.com',
            'name' => 'John Signer',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/send");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonPath('data.status', 'sent');

        $this->assertDatabaseHas('envelopes', [
            'id' => $envelope->id,
            'status' => 'sent',
        ]);
    }

    /** @test */
    public function test_cannot_send_envelope_without_documents()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Contract to Sign',
            'status' => 'draft',
        ]);

        // Add recipient but no document
        EnvelopeRecipient::create([
            'envelope_id' => $envelope->id,
            'recipient_type' => 'signer',
            'routing_order' => 1,
            'email' => 'signer@example.com',
            'name' => 'John Signer',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/send");

        $response->assertStatus(400);
        $this->assertErrorResponse();
    }

    /** @test */
    public function test_cannot_send_envelope_without_recipients()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Contract to Sign',
            'status' => 'draft',
        ]);

        // Add document but no recipient
        EnvelopeDocument::create([
            'envelope_id' => $envelope->id,
            'name' => 'contract.pdf',
            'file_extension' => 'pdf',
            'order' => 1,
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/send");

        $response->assertStatus(400);
        $this->assertErrorResponse();
    }

    /** @test */
    public function test_cannot_send_already_sent_envelope()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Already Sent',
            'status' => 'sent',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/send");

        $response->assertStatus(400);
        $this->assertErrorResponse();
    }

    /** @test */
    public function test_can_void_sent_envelope()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'To Be Voided',
            'status' => 'sent',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/void", [
            'void_reason' => 'No longer needed',
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonPath('data.status', 'voided');

        $this->assertDatabaseHas('envelopes', [
            'id' => $envelope->id,
            'status' => 'voided',
            'voided_reason' => 'No longer needed',
        ]);
    }

    /** @test */
    public function test_cannot_void_draft_envelope()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Draft Envelope',
            'status' => 'draft',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/void", [
            'void_reason' => 'No longer needed',
        ]);

        $response->assertStatus(400);
        $this->assertErrorResponse();
    }

    /** @test */
    public function test_cannot_void_completed_envelope()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Completed Envelope',
            'status' => 'completed',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/void", [
            'void_reason' => 'No longer needed',
        ]);

        $response->assertStatus(400);
        $this->assertErrorResponse();
    }

    /** @test */
    public function test_void_requires_reason()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'To Be Voided',
            'status' => 'sent',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/void", []);

        $response->assertStatus(422);
        $this->assertValidationErrors(['void_reason']);
    }

    /** @test */
    public function test_send_envelope_updates_sent_date_time()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Contract to Sign',
            'status' => 'draft',
        ]);

        EnvelopeDocument::create([
            'envelope_id' => $envelope->id,
            'name' => 'contract.pdf',
            'file_extension' => 'pdf',
            'order' => 1,
        ]);

        EnvelopeRecipient::create([
            'envelope_id' => $envelope->id,
            'recipient_type' => 'signer',
            'routing_order' => 1,
            'email' => 'signer@example.com',
            'name' => 'John Signer',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/send");

        $response->assertStatus(200);

        $envelope->refresh();
        $this->assertNotNull($envelope->sent_date_time);
    }

    /** @test */
    public function test_void_envelope_updates_voided_date_time()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'To Be Voided',
            'status' => 'sent',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/void", [
            'void_reason' => 'Testing void',
        ]);

        $response->assertStatus(200);

        $envelope->refresh();
        $this->assertNotNull($envelope->voided_date_time);
    }

    /** @test */
    public function test_can_void_delivered_envelope()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Delivered Envelope',
            'status' => 'delivered',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/void", [
            'void_reason' => 'Cancel delivery',
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonPath('data.status', 'voided');
    }
}
