<?php

namespace Tests\Feature;

use App\Models\Envelope;
use App\Models\EnvelopeRecipient;
use App\Models\EnvelopeTab;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EnvelopeTabTest extends ApiTestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_can_list_envelope_tabs()
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

        EnvelopeTab::create([
            'envelope_id' => $envelope->id,
            'recipient_id' => $recipient->id,
            'tab_type' => 'sign_here',
            'page_number' => 1,
            'x_position' => 100,
            'y_position' => 200,
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/tabs");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $this->assertNotEmpty($response->json('data'));
    }

    /** @test */
    public function test_can_add_tabs_to_envelope()
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

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/tabs", [
            'sign_here_tabs' => [
                [
                    'tab_id' => 'tab1',
                    'recipient_id' => $recipient->recipient_id,
                    'page_number' => 1,
                    'x_position' => 150,
                    'y_position' => 250,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('envelope_tabs', [
            'envelope_id' => $envelope->id,
            'tab_type' => 'sign_here',
        ]);
    }

    /** @test */
    public function test_supports_multiple_tab_types()
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

        $tabTypes = ['sign_here', 'initial_here', 'date_signed', 'text', 'checkbox', 'radio_group', 'dropdown'];

        foreach ($tabTypes as $index => $type) {
            EnvelopeTab::create([
                'envelope_id' => $envelope->id,
                'recipient_id' => $recipient->id,
                'tab_type' => $type,
                'page_number' => 1,
                'x_position' => 100 + ($index * 50),
                'y_position' => 200,
            ]);
        }

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/tabs");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_update_tab()
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

        $tab = EnvelopeTab::create([
            'envelope_id' => $envelope->id,
            'recipient_id' => $recipient->id,
            'tab_type' => 'text',
            'page_number' => 1,
            'x_position' => 100,
            'y_position' => 200,
            'value' => 'Original',
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/tabs/{$tab->tab_id}", [
            'value' => 'Updated',
            'required' => true,
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('envelope_tabs', [
            'id' => $tab->id,
            'value' => 'Updated',
            'required' => true,
        ]);
    }

    /** @test */
    public function test_can_delete_tab()
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

        $tab = EnvelopeTab::create([
            'envelope_id' => $envelope->id,
            'recipient_id' => $recipient->id,
            'tab_type' => 'text',
            'page_number' => 1,
            'x_position' => 100,
            'y_position' => 200,
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/tabs/{$tab->tab_id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('envelope_tabs', [
            'id' => $tab->id,
        ]);
    }

    /** @test */
    public function test_validates_required_fields_for_tabs()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/tabs", [
            'sign_here_tabs' => [
                [
                    // Missing required fields
                ],
            ],
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function test_supports_anchor_positioning()
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

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/tabs", [
            'sign_here_tabs' => [
                [
                    'recipient_id' => $recipient->recipient_id,
                    'page_number' => 1,
                    'anchor_string' => '/sig/',
                    'anchor_x_offset' => 10,
                    'anchor_y_offset' => -5,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('envelope_tabs', [
            'envelope_id' => $envelope->id,
            'anchor_string' => '/sig/',
        ]);
    }

    /** @test */
    public function test_supports_conditional_tabs()
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

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/tabs", [
            'text_tabs' => [
                [
                    'recipient_id' => $recipient->recipient_id,
                    'page_number' => 1,
                    'x_position' => 100,
                    'y_position' => 200,
                    'conditional_parent_label' => 'checkbox1',
                    'conditional_parent_value' => 'on',
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('envelope_tabs', [
            'envelope_id' => $envelope->id,
            'conditional_parent_label' => 'checkbox1',
        ]);
    }

    /** @test */
    public function test_supports_formula_tabs()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        $recipient = EnvelopeRecipient::create([
            'envelope_id' => $this->account->id,
            'recipient_type' => 'signer',
            'routing_order' => 1,
            'email' => 'signer@example.com',
            'name' => 'John Signer',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/tabs", [
            'formula_tabs' => [
                [
                    'recipient_id' => $recipient->recipient_id,
                    'page_number' => 1,
                    'x_position' => 100,
                    'y_position' => 200,
                    'formula' => '[field1] + [field2]',
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('envelope_tabs', [
            'envelope_id' => $envelope->id,
            'tab_type' => 'formula',
        ]);
    }

    /** @test */
    public function test_can_group_tabs_by_type()
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

        // Create different tab types
        EnvelopeTab::create(['envelope_id' => $envelope->id, 'recipient_id' => $recipient->id, 'tab_type' => 'sign_here', 'page_number' => 1, 'x_position' => 100, 'y_position' => 200]);
        EnvelopeTab::create(['envelope_id' => $envelope->id, 'recipient_id' => $recipient->id, 'tab_type' => 'sign_here', 'page_number' => 1, 'x_position' => 150, 'y_position' => 200]);
        EnvelopeTab::create(['envelope_id' => $envelope->id, 'recipient_id' => $recipient->id, 'tab_type' => 'text', 'page_number' => 1, 'x_position' => 100, 'y_position' => 250]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/tabs");

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        // Should have tabs grouped by type
        $this->assertArrayHasKey('sign_here_tabs', $response->json('data'));
        $this->assertArrayHasKey('text_tabs', $response->json('data'));
    }
}
