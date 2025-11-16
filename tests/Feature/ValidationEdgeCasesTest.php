<?php

namespace Tests\Feature;

use App\Models\Envelope;
use App\Models\EnvelopeDocument;
use App\Models\EnvelopeRecipient;
use App\Models\EnvelopeTab;
use App\Models\Template;
use App\Models\Signature;
use App\Models\Brand;
use App\Models\User;
use App\Models\SigningGroup;
use App\Models\Folder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ValidationEdgeCasesTest extends ApiTestCase
{
    use RefreshDatabase;

    // ========== Envelope Validation Tests ==========

    /** @test */
    public function test_validates_envelope_subject_max_length()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes", [
            'subject' => str_repeat('a', 501), // Too long
            'status' => 'draft',
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['subject']);
    }

    /** @test */
    public function test_validates_envelope_status_enum()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes", [
            'subject' => 'Test',
            'status' => 'invalid_status',
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['status']);
    }

    /** @test */
    public function test_envelope_cannot_be_sent_without_both_documents_and_recipients()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test',
            'status' => 'draft',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/send");

        $response->assertStatus(400);
        $this->assertErrorResponse();
    }

    /** @test */
    public function test_validates_email_format_for_recipients()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test',
            'status' => 'draft',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/recipients", [
            'recipients' => [
                [
                    'recipient_type' => 'signer',
                    'routing_order' => 1,
                    'email' => 'not-an-email',
                    'name' => 'Test User',
                ],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['recipients.0.email']);
    }

    /** @test */
    public function test_validates_routing_order_is_positive()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test',
            'status' => 'draft',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/recipients", [
            'recipients' => [
                [
                    'recipient_type' => 'signer',
                    'routing_order' => -1,
                    'email' => 'test@example.com',
                    'name' => 'Test User',
                ],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['recipients.0.routing_order']);
    }

    /** @test */
    public function test_validates_tab_position_is_positive()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test',
            'status' => 'draft',
        ]);

        $recipient = EnvelopeRecipient::create([
            'envelope_id' => $envelope->id,
            'recipient_type' => 'signer',
            'routing_order' => 1,
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/tabs", [
            'sign_here_tabs' => [
                [
                    'recipient_id' => $recipient->recipient_id,
                    'page_number' => 1,
                    'x_position' => -10, // Invalid
                    'y_position' => 100,
                ],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['sign_here_tabs.0.x_position']);
    }

    /** @test */
    public function test_validates_document_order_is_positive()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test',
            'status' => 'draft',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/documents", [
            'documents' => [
                [
                    'name' => 'test.pdf',
                    'file_extension' => 'pdf',
                    'order' => -1,
                ],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['documents.0.order']);
    }

    // ========== Template Validation Tests ==========

    /** @test */
    public function test_validates_template_name_required()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/templates", [
            'status' => 'active',
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['template_name']);
    }

    /** @test */
    public function test_validates_template_name_max_length()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/templates", [
            'template_name' => str_repeat('a', 256),
            'status' => 'active',
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['template_name']);
    }

    /** @test */
    public function test_cannot_create_envelope_from_inactive_template()
    {
        $this->createAndAuthenticateUser();

        $template = Template::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'template_name' => 'Inactive Template',
            'status' => 'inactive',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/templates/{$template->template_id}/create_envelope", [
            'status' => 'draft',
        ]);

        $response->assertStatus(400);
        $this->assertErrorResponse();
    }

    // ========== User Validation Tests ==========

    /** @test */
    public function test_validates_unique_email_on_user_creation()
    {
        $this->createAndAuthenticateUser();

        User::create([
            'account_id' => $this->account->id,
            'user_name' => 'existing',
            'email' => 'existing@example.com',
            'password' => \Hash::make('password'),
            'user_status' => 'active',
            'user_type' => 'member',
            'created_datetime' => now(),
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/users", [
            'user_name' => 'newuser',
            'email' => 'existing@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'user_status' => 'active',
            'user_type' => 'member',
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['email']);
    }

    /** @test */
    public function test_validates_user_status_enum()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/users", [
            'user_name' => 'testuser',
            'email' => 'test@example.com',
            'user_status' => 'invalid_status',
            'user_type' => 'member',
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['user_status']);
    }

    // ========== Signature Validation Tests ==========

    /** @test */
    public function test_validates_signature_font_enum()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/signatures", [
            'signature_name' => 'Test Signature',
            'signature_type' => 'account',
            'signature_font' => 'invalid_font',
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['signature_font']);
    }

    /** @test */
    public function test_validates_signature_type_enum()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/signatures", [
            'signature_name' => 'Test Signature',
            'signature_type' => 'invalid_type',
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['signature_type']);
    }

    // ========== Brand Validation Tests ==========

    /** @test */
    public function test_validates_brand_name_required()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/brands", [
            'brand_company' => 'Test Company',
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['brand_name']);
    }

    /** @test */
    public function test_validates_brand_name_max_length()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/brands", [
            'brand_name' => str_repeat('a', 256),
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['brand_name']);
    }

    // ========== Group Validation Tests ==========

    /** @test */
    public function test_validates_signing_group_type_enum()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/signing_groups", [
            'group_name' => 'Test Group',
            'group_type' => 'invalid_type',
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['group_type']);
    }

    /** @test */
    public function test_validates_signing_group_name_required()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/signing_groups", [
            'group_type' => 'private',
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['group_name']);
    }

    // ========== Edge Cases: Empty Data ==========

    /** @test */
    public function test_handles_empty_recipient_array()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test',
            'status' => 'draft',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/recipients", [
            'recipients' => [],
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['recipients']);
    }

    /** @test */
    public function test_handles_empty_document_array()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test',
            'status' => 'draft',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/documents", [
            'documents' => [],
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['documents']);
    }

    // ========== Edge Cases: Null Values ==========

    /** @test */
    public function test_handles_null_envelope_subject()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes", [
            'subject' => null,
            'status' => 'draft',
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['subject']);
    }

    /** @test */
    public function test_handles_null_recipient_email()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test',
            'status' => 'draft',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/recipients", [
            'recipients' => [
                [
                    'recipient_type' => 'signer',
                    'routing_order' => 1,
                    'email' => null,
                    'name' => 'Test User',
                ],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['recipients.0.email']);
    }

    // ========== Edge Cases: Boundary Values ==========

    /** @test */
    public function test_handles_minimum_routing_order()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test',
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
    }

    /** @test */
    public function test_handles_large_routing_order()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test',
            'status' => 'draft',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/recipients", [
            'recipients' => [
                [
                    'recipient_type' => 'signer',
                    'routing_order' => 100,
                    'email' => 'test@example.com',
                    'name' => 'Test User',
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();
    }

    // ========== Edge Cases: Special Characters ==========

    /** @test */
    public function test_handles_special_characters_in_envelope_subject()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes", [
            'subject' => 'Test!@#$%^&*()_+-=[]{}|;:,.<>?',
            'status' => 'draft',
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_handles_unicode_in_recipient_name()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test',
            'status' => 'draft',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/recipients", [
            'recipients' => [
                [
                    'recipient_type' => 'signer',
                    'routing_order' => 1,
                    'email' => 'test@example.com',
                    'name' => '日本語テスト',
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();
    }

    // ========== Edge Cases: Concurrent Operations ==========

    /** @test */
    public function test_prevents_concurrent_envelope_modifications_with_locks()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test',
            'status' => 'draft',
        ]);

        // First user locks the envelope
        $lockResponse = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/lock", [
            'lock_duration_in_seconds' => 300,
        ]);

        $lockResponse->assertStatus(201);

        // Second attempt to lock should fail
        $secondLockResponse = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/lock", [
            'lock_duration_in_seconds' => 300,
        ]);

        $secondLockResponse->assertStatus(400);
        $this->assertErrorResponse();
    }

    // ========== Edge Cases: Large Batch Operations ==========

    /** @test */
    public function test_handles_large_recipient_batch()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test',
            'status' => 'draft',
        ]);

        $recipients = [];
        for ($i = 1; $i <= 50; $i++) {
            $recipients[] = [
                'recipient_type' => 'signer',
                'routing_order' => $i,
                'email' => "recipient{$i}@example.com",
                'name' => "Recipient {$i}",
            ];
        }

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/recipients", [
            'recipients' => $recipients,
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_handles_large_document_batch()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test',
            'status' => 'draft',
        ]);

        $documents = [];
        for ($i = 1; $i <= 20; $i++) {
            $documents[] = [
                'name' => "document{$i}.pdf",
                'file_extension' => 'pdf',
                'order' => $i,
            ];
        }

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/documents", [
            'documents' => $documents,
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();
    }

    // ========== Edge Cases: Resource Not Found ==========

    /** @test */
    public function test_returns_404_for_nonexistent_envelope()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/nonexistent-id");

        $response->assertStatus(404);
        $this->assertErrorResponse();
    }

    /** @test */
    public function test_returns_404_for_nonexistent_template()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/templates/nonexistent-id");

        $response->assertStatus(404);
        $this->assertErrorResponse();
    }

    /** @test */
    public function test_returns_404_for_nonexistent_user()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/users/999999");

        $response->assertStatus(404);
        $this->assertErrorResponse();
    }

    // ========== Edge Cases: Cross-Account Access ==========

    /** @test */
    public function test_prevents_cross_account_envelope_access()
    {
        $this->createAndAuthenticateUser();

        // Create a second account
        $otherAccount = \App\Models\Account::create([
            'plan_id' => $this->account->plan_id,
            'account_id' => 'acc_other_' . \Str::random(12),
            'account_name' => 'Other Account',
            'billing_period_envelopes_sent' => 0,
            'billing_period_envelopes_allowed' => 100,
            'created_date' => now(),
        ]);

        $otherEnvelope = Envelope::create([
            'account_id' => $otherAccount->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Other Account Envelope',
            'status' => 'draft',
        ]);

        // Try to access other account's envelope
        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$otherEnvelope->envelope_id}");

        $response->assertStatus(404);
    }

    // ========== Edge Cases: Deletion Protection ==========

    /** @test */
    public function test_cannot_delete_system_folder()
    {
        $this->createAndAuthenticateUser();

        $systemFolder = Folder::create([
            'account_id' => $this->account->id,
            'owner_user_id' => $this->user->id,
            'folder_name' => 'Inbox',
            'folder_type' => 'inbox',
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/folders/{$systemFolder->folder_id}");

        $response->assertStatus(400);
        $this->assertErrorResponse();
    }

    /** @test */
    public function test_cannot_delete_default_brand()
    {
        $this->createAndAuthenticateUser();

        $defaultBrand = Brand::create([
            'account_id' => $this->account->id,
            'brand_name' => 'Default Brand',
            'is_default' => true,
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/brands/{$defaultBrand->brand_id}");

        $response->assertStatus(400);
        $this->assertErrorResponse();
    }

    // ========== Pagination Edge Cases ==========

    /** @test */
    public function test_handles_large_page_number()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes?page=999");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
    }

    /** @test */
    public function test_handles_zero_page_number()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes?page=0");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
    }

    /** @test */
    public function test_handles_large_per_page_value()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes?per_page=1000");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
    }

    // ========== Date Edge Cases ==========

    /** @test */
    public function test_handles_past_expiration_date()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Expired Envelope',
            'status' => 'sent',
            'expires_date_time' => now()->subDays(1),
        ]);

        $this->assertTrue($envelope->hasExpired());
    }

    /** @test */
    public function test_handles_future_scheduled_send_date()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Scheduled Envelope',
            'status' => 'draft',
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/workflow", [
            'workflow_status' => 'paused',
            'resume_date' => now()->addDays(7)->toDateString(),
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }
}
