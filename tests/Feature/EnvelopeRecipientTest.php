<?php

namespace Tests\Feature;

use App\Models\Envelope;
use App\Models\EnvelopeRecipient;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EnvelopeRecipientTest extends ApiTestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_can_list_envelope_recipients()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        EnvelopeRecipient::create([
            'envelope_id' => $envelope->id,
            'recipient_type' => 'signer',
            'routing_order' => 1,
            'email' => 'signer1@example.com',
            'name' => 'John Doe',
        ]);

        EnvelopeRecipient::create([
            'envelope_id' => $envelope->id,
            'recipient_type' => 'viewer',
            'routing_order' => 2,
            'email' => 'viewer@example.com',
            'name' => 'Jane Viewer',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/recipients");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonCount(2, 'data.recipients');
    }

    /** @test */
    public function test_can_add_recipients_to_envelope()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/recipients", [
            'recipients' => [
                [
                    'recipient_id' => 'recip1',
                    'recipient_type' => 'signer',
                    'routing_order' => 1,
                    'email' => 'signer@example.com',
                    'name' => 'John Signer',
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('envelope_recipients', [
            'envelope_id' => $envelope->id,
            'email' => 'signer@example.com',
        ]);
    }

    /** @test */
    public function test_can_get_specific_recipient()
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
            'name' => 'John Signer',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/recipients/{$recipient->recipient_id}");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonPath('data.email', 'signer@example.com');
    }

    /** @test */
    public function test_can_update_recipient()
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
            'email' => 'old@example.com',
            'name' => 'Old Name',
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/recipients/{$recipient->recipient_id}", [
            'email' => 'new@example.com',
            'name' => 'New Name',
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('envelope_recipients', [
            'id' => $recipient->id,
            'email' => 'new@example.com',
            'name' => 'New Name',
        ]);
    }

    /** @test */
    public function test_can_delete_recipient()
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
            'email' => 'delete@example.com',
            'name' => 'To Delete',
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/recipients/{$recipient->recipient_id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('envelope_recipients', [
            'id' => $recipient->id,
        ]);
    }

    /** @test */
    public function test_validates_recipient_type()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/recipients", [
            'recipients' => [
                [
                    'recipient_type' => 'invalid_type',
                    'routing_order' => 1,
                    'email' => 'test@example.com',
                    'name' => 'Test User',
                ],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['recipients.0.recipient_type']);
    }

    /** @test */
    public function test_validates_email_format()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/recipients", [
            'recipients' => [
                [
                    'recipient_type' => 'signer',
                    'routing_order' => 1,
                    'email' => 'invalid-email',
                    'name' => 'Test User',
                ],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['recipients.0.email']);
    }

    /** @test */
    public function test_supports_all_recipient_types()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        $recipientTypes = ['signer', 'viewer', 'approver', 'certified_delivery', 'carbon_copy', 'witness', 'notary', 'in_person_signer'];

        foreach ($recipientTypes as $index => $type) {
            EnvelopeRecipient::create([
                'envelope_id' => $envelope->id,
                'recipient_type' => $type,
                'routing_order' => $index + 1,
                'email' => "{$type}@example.com",
                'name' => ucfirst(str_replace('_', ' ', $type)),
            ]);
        }

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/recipients");

        $response->assertStatus(200);
        $response->assertJsonCount(count($recipientTypes), 'data.recipients');
    }

    /** @test */
    public function test_can_replace_all_recipients()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        // Create existing recipients
        EnvelopeRecipient::create(['envelope_id' => $envelope->id, 'recipient_type' => 'signer', 'routing_order' => 1, 'email' => 'old1@example.com', 'name' => 'Old 1']);
        EnvelopeRecipient::create(['envelope_id' => $envelope->id, 'recipient_type' => 'viewer', 'routing_order' => 2, 'email' => 'old2@example.com', 'name' => 'Old 2']);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/recipients", [
            'recipients' => [
                [
                    'recipient_type' => 'signer',
                    'routing_order' => 1,
                    'email' => 'new@example.com',
                    'name' => 'New Signer',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        // Old recipients should be deleted
        $this->assertDatabaseMissing('envelope_recipients', ['email' => 'old1@example.com']);
        $this->assertDatabaseMissing('envelope_recipients', ['email' => 'old2@example.com']);

        // New recipient should exist
        $this->assertDatabaseHas('envelope_recipients', ['email' => 'new@example.com']);
    }

    /** @test */
    public function test_auto_generates_recipient_id_if_not_provided()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/recipients", [
            'recipients' => [
                [
                    'recipient_type' => 'signer',
                    'routing_order' => 1,
                    'email' => 'test@example.com',
                    'name' => 'Test User',
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $recipient = EnvelopeRecipient::where('envelope_id', $envelope->id)->first();
        $this->assertNotNull($recipient->recipient_id);
    }

    /** @test */
    public function test_supports_routing_order_for_sequential_signing()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        // Create recipients with specific routing order
        EnvelopeRecipient::create(['envelope_id' => $envelope->id, 'recipient_type' => 'signer', 'routing_order' => 3, 'email' => 'third@example.com', 'name' => 'Third']);
        EnvelopeRecipient::create(['envelope_id' => $envelope->id, 'recipient_type' => 'signer', 'routing_order' => 1, 'email' => 'first@example.com', 'name' => 'First']);
        EnvelopeRecipient::create(['envelope_id' => $envelope->id, 'recipient_type' => 'signer', 'routing_order' => 2, 'email' => 'second@example.com', 'name' => 'Second']);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/recipients");

        $response->assertStatus(200);
        $response->assertJsonPath('data.recipients.0.email', 'first@example.com');
        $response->assertJsonPath('data.recipients.1.email', 'second@example.com');
        $response->assertJsonPath('data.recipients.2.email', 'third@example.com');
    }

    /** @test */
    public function test_supports_access_code_for_recipients()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/recipients", [
            'recipients' => [
                [
                    'recipient_type' => 'signer',
                    'routing_order' => 1,
                    'email' => 'secure@example.com',
                    'name' => 'Secure Signer',
                    'access_code' => 'ABC123',
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('envelope_recipients', [
            'envelope_id' => $envelope->id,
            'access_code' => 'ABC123',
        ]);
    }
}
