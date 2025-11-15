<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Template;
use App\Models\EnvelopeRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Template Recipients Integration Tests
 *
 * Tests all 6 template recipient endpoints:
 * - GET /templates/{id}/recipients
 * - POST /templates/{id}/recipients
 * - PUT /templates/{id}/recipients
 * - DELETE /templates/{id}/recipients
 * - GET /templates/{id}/recipients/{recipId}
 * - PUT /templates/{id}/recipients/{recipId}
 */
class TemplateRecipientTest extends TestCase
{
    use RefreshDatabase;

    private Account $account;
    private User $user;
    private Template $template;

    protected function setUp(): void
    {
        parent::setUp();

        $this->account = Account::factory()->create();
        $this->user = User::factory()->create([
            'account_id' => $this->account->id,
        ]);

        $this->template = Template::create([
            'account_id' => $this->account->id,
            'template_id' => 'tpl-' . uniqid(),
            'name' => 'Test Template',
            'created_by_user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user);
    }

    
    public function test_can_list_template_recipients()
    {
        // Create test recipients
        EnvelopeRecipient::create([
            'template_id' => $this->template->id,
            'recipient_id' => 'recip-1',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'type' => 'signer',
            'routing_order' => 1,
            'status' => 'created',
        ]);

        EnvelopeRecipient::create([
            'template_id' => $this->template->id,
            'recipient_id' => 'recip-2',
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'type' => 'approver',
            'routing_order' => 2,
            'status' => 'created',
        ]);

        $response = $this->getJson("/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/recipients");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'template_recipients' => [
                        '*' => [
                            'recipient_id',
                            'name',
                            'email',
                            'type',
                            'routing_order',
                            'status',
                        ],
                    ],
                ],
            ])
            ->assertJsonCount(2, 'data.template_recipients');
    }

    
    public function test_can_add_recipients_to_template()
    {
        $recipientsData = [
            'recipients' => [
                [
                    'name' => 'Alice Johnson',
                    'email' => 'alice@example.com',
                    'type' => 'signer',
                    'routing_order' => 1,
                    'access_code' => '1234',
                    'note' => 'Please sign all pages',
                ],
                [
                    'name' => 'Bob Williams',
                    'email' => 'bob@example.com',
                    'type' => 'viewer',
                    'routing_order' => 2,
                    'phone_number' => '+1234567890',
                ],
            ],
        ];

        $response = $this->postJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/recipients",
            $recipientsData
        );

        $response->assertStatus(201)
            ->assertJsonCount(2, 'data.template_recipients');

        $this->assertDatabaseHas('envelope_recipients', [
            'template_id' => $this->template->id,
            'name' => 'Alice Johnson',
            'email' => 'alice@example.com',
            'type' => 'signer',
            'access_code' => '1234',
        ]);

        $this->assertDatabaseHas('envelope_recipients', [
            'template_id' => $this->template->id,
            'name' => 'Bob Williams',
            'type' => 'viewer',
            'phone_number' => '+1234567890',
        ]);
    }

    
    public function test_can_replace_all_template_recipients()
    {
        // Create existing recipient
        EnvelopeRecipient::create([
            'template_id' => $this->template->id,
            'recipient_id' => 'old-recip',
            'name' => 'Old Recipient',
            'email' => 'old@example.com',
            'type' => 'signer',
            'status' => 'created',
        ]);

        $newRecipients = [
            'recipients' => [
                [
                    'name' => 'New Recipient',
                    'email' => 'new@example.com',
                    'type' => 'signer',
                    'routing_order' => 1,
                ],
            ],
        ];

        $response = $this->putJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/recipients",
            $newRecipients
        );

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.template_recipients');

        // Old recipient should be deleted
        $this->assertDatabaseMissing('envelope_recipients', [
            'recipient_id' => 'old-recip',
        ]);

        // New recipient should exist
        $this->assertDatabaseHas('envelope_recipients', [
            'template_id' => $this->template->id,
            'name' => 'New Recipient',
            'email' => 'new@example.com',
        ]);
    }

    
    public function test_can_delete_all_template_recipients()
    {
        // Create recipients
        EnvelopeRecipient::create([
            'template_id' => $this->template->id,
            'recipient_id' => 'recip-1',
            'name' => 'Recipient 1',
            'email' => 'recip1@example.com',
            'type' => 'signer',
            'status' => 'created',
        ]);

        EnvelopeRecipient::create([
            'template_id' => $this->template->id,
            'recipient_id' => 'recip-2',
            'name' => 'Recipient 2',
            'email' => 'recip2@example.com',
            'type' => 'signer',
            'status' => 'created',
        ]);

        $response = $this->deleteJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/recipients"
        );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['deleted_count' => 2],
            ]);

        $this->assertDatabaseMissing('envelope_recipients', [
            'template_id' => $this->template->id,
        ]);
    }

    
    public function test_can_get_specific_template_recipient()
    {
        $recipient = EnvelopeRecipient::create([
            'template_id' => $this->template->id,
            'recipient_id' => 'recip-123',
            'name' => 'Specific Recipient',
            'email' => 'specific@example.com',
            'type' => 'signer',
            'routing_order' => 1,
            'status' => 'created',
            'access_code' => '5678',
        ]);

        $response = $this->getJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/recipients/recip-123"
        );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'recipient_id' => 'recip-123',
                    'name' => 'Specific Recipient',
                    'email' => 'specific@example.com',
                    'type' => 'signer',
                    'access_code' => '5678',
                ],
            ]);
    }

    
    public function test_can_update_specific_template_recipient()
    {
        $recipient = EnvelopeRecipient::create([
            'template_id' => $this->template->id,
            'recipient_id' => 'recip-456',
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'type' => 'signer',
            'routing_order' => 1,
            'status' => 'created',
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'routing_order' => 2,
            'access_code' => '9999',
        ];

        $response = $this->putJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/recipients/recip-456",
            $updateData
        );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'recipient_id' => 'recip-456',
                    'name' => 'Updated Name',
                    'email' => 'updated@example.com',
                    'routing_order' => 2,
                    'access_code' => '9999',
                ],
            ]);

        $this->assertDatabaseHas('envelope_recipients', [
            'recipient_id' => 'recip-456',
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    
    public function test_validates_recipient_type()
    {
        $invalidData = [
            'recipients' => [
                [
                    'name' => 'Test Recipient',
                    'email' => 'test@example.com',
                    'type' => 'invalid_type', // Invalid type
                ],
            ],
        ];

        $response = $this->postJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/recipients",
            $invalidData
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['recipients.0.type']);
    }

    
    public function test_validates_email_format()
    {
        $invalidData = [
            'recipients' => [
                [
                    'name' => 'Test Recipient',
                    'email' => 'not-an-email', // Invalid email
                    'type' => 'signer',
                ],
            ],
        ];

        $response = $this->postJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/recipients",
            $invalidData
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['recipients.0.email']);
    }

    
    public function test_supports_all_recipient_types()
    {
        $types = ['signer', 'viewer', 'approver', 'certified_delivery', 'in_person_signer', 'carbon_copy', 'agent', 'intermediary'];

        foreach ($types as $index => $type) {
            $response = $this->postJson(
                "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/recipients",
                [
                    'recipients' => [
                        [
                            'name' => "Recipient $type",
                            'email' => "$type@example.com",
                            'type' => $type,
                        ],
                    ],
                ]
            );

            $response->assertStatus(201);

            $this->assertDatabaseHas('envelope_recipients', [
                'template_id' => $this->template->id,
                'type' => $type,
            ]);
        }
    }

    
    public function test_auto_generates_recipient_id_if_not_provided()
    {
        $recipientData = [
            'recipients' => [
                [
                    'name' => 'Auto ID Recipient',
                    'email' => 'auto@example.com',
                    'type' => 'signer',
                ],
            ],
        ];

        $response = $this->postJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/recipients",
            $recipientData
        );

        $response->assertStatus(201);

        $recipient = EnvelopeRecipient::where('template_id', $this->template->id)->first();
        $this->assertNotNull($recipient->recipient_id);
        $this->assertNotEmpty($recipient->recipient_id);
    }
}
