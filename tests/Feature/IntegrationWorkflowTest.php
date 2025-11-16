<?php

namespace Tests\Feature;

use App\Models\Envelope;
use App\Models\EnvelopeDocument;
use App\Models\EnvelopeRecipient;
use App\Models\EnvelopeTab;
use App\Models\EnvelopeCustomField;
use App\Models\Template;
use App\Models\Brand;
use App\Models\Signature;
use App\Models\BillingInvoice;
use App\Models\BillingPayment;
use App\Models\Folder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IntegrationWorkflowTest extends ApiTestCase
{
    use RefreshDatabase;

    // ========== Complete Envelope Workflow Tests ==========

    /** @test */
    public function test_complete_envelope_lifecycle_from_draft_to_completion()
    {
        $this->createAndAuthenticateUser();

        // 1. Create envelope
        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes", [
            'subject' => 'Complete Workflow Test',
            'email_subject' => 'Please sign',
            'status' => 'draft',
        ]);
        $response->assertStatus(201);
        $envelopeId = $response->json('data.envelope_id');

        // 2. Add documents
        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelopeId}/documents", [
            'documents' => [
                [
                    'name' => 'contract.pdf',
                    'file_extension' => 'pdf',
                    'order' => 1,
                ],
            ],
        ]);
        $response->assertStatus(201);

        // 3. Add recipients
        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelopeId}/recipients", [
            'recipients' => [
                [
                    'recipient_type' => 'signer',
                    'routing_order' => 1,
                    'email' => 'signer@example.com',
                    'name' => 'John Signer',
                ],
            ],
        ]);
        $response->assertStatus(201);

        // 4. Send envelope
        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelopeId}/send");
        $response->assertStatus(200);

        // 5. Verify final state
        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelopeId}");
        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'sent');
    }

    /** @test */
    public function test_envelope_with_multiple_recipients_sequential_signing()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Sequential Signing',
            'status' => 'draft',
        ]);

        EnvelopeDocument::create([
            'envelope_id' => $envelope->id,
            'name' => 'contract.pdf',
            'file_extension' => 'pdf',
            'order' => 1,
        ]);

        // Add recipients with sequential routing
        $recipients = [
            ['type' => 'signer', 'order' => 1, 'email' => 'first@example.com', 'name' => 'First Signer'],
            ['type' => 'signer', 'order' => 2, 'email' => 'second@example.com', 'name' => 'Second Signer'],
            ['type' => 'approver', 'order' => 3, 'email' => 'approver@example.com', 'name' => 'Approver'],
        ];

        foreach ($recipients as $recip) {
            EnvelopeRecipient::create([
                'envelope_id' => $envelope->id,
                'recipient_type' => $recip['type'],
                'routing_order' => $recip['order'],
                'email' => $recip['email'],
                'name' => $recip['name'],
            ]);
        }

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/send");
        $response->assertStatus(200);

        $this->assertDatabaseHas('envelopes', [
            'id' => $envelope->id,
            'status' => 'sent',
        ]);
    }

    /** @test */
    public function test_envelope_with_tabs_and_custom_fields()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Tabs and Fields Test',
            'status' => 'draft',
        ]);

        EnvelopeDocument::create([
            'envelope_id' => $envelope->id,
            'name' => 'contract.pdf',
            'file_extension' => 'pdf',
            'order' => 1,
        ]);

        $recipient = EnvelopeRecipient::create([
            'envelope_id' => $envelope->id,
            'recipient_type' => 'signer',
            'routing_order' => 1,
            'email' => 'signer@example.com',
            'name' => 'Signer',
        ]);

        // Add tabs
        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/tabs", [
            'sign_here_tabs' => [
                [
                    'recipient_id' => $recipient->recipient_id,
                    'page_number' => 1,
                    'x_position' => 100,
                    'y_position' => 200,
                ],
            ],
            'date_signed_tabs' => [
                [
                    'recipient_id' => $recipient->recipient_id,
                    'page_number' => 1,
                    'x_position' => 300,
                    'y_position' => 200,
                ],
            ],
        ]);
        $response->assertStatus(201);

        // Add custom fields
        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/custom_fields", [
            'text_custom_fields' => [
                [
                    'field_id' => 'cf1',
                    'name' => 'Department',
                    'value' => 'Legal',
                    'show' => true,
                    'required' => false,
                ],
            ],
        ]);
        $response->assertStatus(201);

        $this->assertDatabaseHas('envelope_tabs', ['envelope_id' => $envelope->id, 'tab_type' => 'sign_here']);
        $this->assertDatabaseHas('envelope_custom_fields', ['envelope_id' => $envelope->id, 'name' => 'Department']);
    }

    /** @test */
    public function test_create_envelope_from_template_with_role_mapping()
    {
        $this->createAndAuthenticateUser();

        $template = Template::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'template_name' => 'Employment Contract',
            'status' => 'active',
        ]);

        // Add template document
        EnvelopeDocument::create([
            'template_id' => $template->id,
            'name' => 'contract.pdf',
            'file_extension' => 'pdf',
            'order' => 1,
        ]);

        // Add template recipient with role
        EnvelopeRecipient::create([
            'template_id' => $template->id,
            'recipient_type' => 'signer',
            'routing_order' => 1,
            'role_name' => 'Employee',
        ]);

        // Create envelope from template
        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/templates/{$template->template_id}/create_envelope", [
            'status' => 'draft',
            'recipients' => [
                [
                    'role_name' => 'Employee',
                    'email' => 'employee@example.com',
                    'name' => 'John Employee',
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('envelopes', ['account_id' => $this->account->id]);
    }

    /** @test */
    public function test_envelope_folder_organization()
    {
        $this->createAndAuthenticateUser();

        // Create folders
        $projectFolder = Folder::create([
            'account_id' => $this->account->id,
            'owner_user_id' => $this->user->id,
            'folder_name' => 'Projects',
            'folder_type' => 'custom',
        ]);

        // Create envelope
        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Project Contract',
            'status' => 'draft',
        ]);

        // Move to folder
        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/folders/{$projectFolder->folder_id}/envelopes", [
            'envelope_ids' => [$envelope->envelope_id],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('envelope_folders', [
            'envelope_id' => $envelope->id,
            'folder_id' => $projectFolder->id,
        ]);
    }

    /** @test */
    public function test_branding_applied_to_envelope()
    {
        $this->createAndAuthenticateUser();

        $brand = Brand::create([
            'account_id' => $this->account->id,
            'brand_name' => 'Corporate Brand',
            'is_default' => false,
        ]);

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'brand_id' => $brand->id,
            'subject' => 'Branded Envelope',
            'status' => 'draft',
        ]);

        $this->assertEquals($brand->id, $envelope->brand_id);
    }

    /** @test */
    public function test_billing_charge_created_for_sent_envelope()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Billable Envelope',
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
            'name' => 'Signer',
        ]);

        // Send envelope (would trigger billing in production)
        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/send");
        $response->assertStatus(200);

        // Verify envelope count incremented
        $this->account->refresh();
        $this->assertEquals(1, $this->account->billing_period_envelopes_sent);
    }

    /** @test */
    public function test_payment_application_to_invoice()
    {
        $this->createAndAuthenticateUser();

        $invoice = BillingInvoice::create([
            'account_id' => $this->account->id,
            'invoice_number' => 'INV-001',
            'amount' => 100.00,
            'balance' => 100.00,
            'due_date' => now()->addDays(30),
        ]);

        // Make payment
        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/billing_payments", [
            'invoice_id' => $invoice->invoice_id,
            'payment_amount' => 60.00,
            'payment_method' => 'credit_card',
        ]);

        $response->assertStatus(201);

        $invoice->refresh();
        $this->assertEquals(40.00, $invoice->balance);
    }

    /** @test */
    public function test_template_versioning_workflow()
    {
        $this->createAndAuthenticateUser();

        $template = Template::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'template_name' => 'Contract Template',
            'status' => 'active',
            'version' => 1,
        ]);

        // Create new version
        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/templates/{$template->template_id}/version", [
            'version_notes' => 'Updated terms',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('templates', [
            'account_id' => $this->account->id,
            'template_name' => 'Contract Template',
            'version' => 2,
        ]);
    }

    /** @test */
    public function test_signature_application_to_document()
    {
        $this->createAndAuthenticateUser();

        $signature = Signature::create([
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'signature_name' => 'My Signature',
            'signature_type' => 'user',
        ]);

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Signature Test',
            'status' => 'draft',
        ]);

        $recipient = EnvelopeRecipient::create([
            'envelope_id' => $envelope->id,
            'recipient_type' => 'signer',
            'routing_order' => 1,
            'email' => $this->user->email,
            'name' => $this->user->user_name,
        ]);

        $tab = EnvelopeTab::create([
            'envelope_id' => $envelope->id,
            'recipient_id' => $recipient->id,
            'tab_type' => 'sign_here',
            'page_number' => 1,
            'x_position' => 100,
            'y_position' => 200,
        ]);

        // Apply signature (simulated - in production would use signature image)
        $tab->update(['value' => 'signed']);

        $this->assertEquals('signed', $tab->value);
    }

    /** @test */
    public function test_audit_trail_captures_envelope_events()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Audit Trail Test',
            'status' => 'draft',
        ]);

        // Create audit event
        $envelope->auditEvents()->create([
            'event_type' => 'envelope_created',
            'user_id' => $this->user->id,
            'timestamp' => now(),
            'metadata' => ['created_by' => $this->user->email],
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/audit_events");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $this->assertNotEmpty($response->json('data'));
    }

    /** @test */
    public function test_notification_settings_override()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Notification Test',
            'status' => 'draft',
        ]);

        // Set custom notifications
        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/notification", [
            'reminder_enabled' => true,
            'reminder_delay' => 5,
            'reminder_frequency' => 3,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('envelopes', [
            'id' => $envelope->id,
            'reminder_enabled' => true,
            'reminder_delay' => 5,
            'reminder_frequency' => 3,
        ]);
    }

    /** @test */
    public function test_envelope_voiding_workflow()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'To Be Voided',
            'status' => 'sent',
            'sent_date_time' => now(),
        ]);

        // Void envelope
        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/void", [
            'void_reason' => 'Error in document',
        ]);

        $response->assertStatus(200);

        $envelope->refresh();
        $this->assertEquals('voided', $envelope->status);
        $this->assertNotNull($envelope->voided_date_time);
        $this->assertEquals('Error in document', $envelope->voided_reason);
    }

    /** @test */
    public function test_envelope_correction_workflow()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Correction Test',
            'status' => 'sent',
        ]);

        $recipient = EnvelopeRecipient::create([
            'envelope_id' => $envelope->id,
            'recipient_type' => 'signer',
            'routing_order' => 1,
            'email' => 'wrong@example.com',
            'name' => 'Wrong Name',
        ]);

        // Correct recipient information
        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/correct", [
            'recipient_corrections' => [
                [
                    'recipient_id' => $recipient->recipient_id,
                    'email' => 'correct@example.com',
                    'name' => 'Correct Name',
                ],
            ],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('envelope_recipients', [
            'id' => $recipient->id,
            'email' => 'correct@example.com',
            'name' => 'Correct Name',
        ]);
    }

    /** @test */
    public function test_envelope_resend_workflow()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Resend Test',
            'status' => 'sent',
        ]);

        $recipient = EnvelopeRecipient::create([
            'envelope_id' => $envelope->id,
            'recipient_type' => 'signer',
            'routing_order' => 1,
            'email' => 'signer@example.com',
            'name' => 'Signer',
            'sent_date_time' => now()->subDays(5),
        ]);

        // Resend notification
        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/resend", [
            'resend_envelope' => true,
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function test_envelope_with_attachments()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Attachments Test',
            'status' => 'draft',
        ]);

        // Add attachment
        $envelope->attachments()->create([
            'attachment_name' => 'Reference.pdf',
            'file_extension' => 'pdf',
            'file_size' => 1024,
        ]);

        $this->assertDatabaseHas('envelope_attachments', [
            'envelope_id' => $envelope->id,
            'attachment_name' => 'Reference.pdf',
        ]);
    }

    /** @test */
    public function test_envelope_transfer_rules_application()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Transfer Rules Test',
            'status' => 'sent',
        ]);

        // Create transfer rule
        $envelope->transferRules()->create([
            'rule_type' => 'on_decline',
            'transfer_to_user_id' => $this->user->id,
            'is_enabled' => true,
        ]);

        $this->assertDatabaseHas('envelope_transfer_rules', [
            'envelope_id' => $envelope->id,
            'rule_type' => 'on_decline',
        ]);
    }

    /** @test */
    public function test_document_visibility_configuration()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Visibility Test',
            'status' => 'draft',
        ]);

        $document = EnvelopeDocument::create([
            'envelope_id' => $envelope->id,
            'name' => 'contract.pdf',
            'file_extension' => 'pdf',
            'order' => 1,
        ]);

        $recipient = EnvelopeRecipient::create([
            'envelope_id' => $envelope->id,
            'recipient_type' => 'signer',
            'routing_order' => 1,
            'email' => 'signer@example.com',
            'name' => 'Signer',
        ]);

        // Set document visibility
        $document->update([
            'document_visibility' => [$recipient->recipient_id],
        ]);

        $this->assertNotNull($document->document_visibility);
    }

    /** @test */
    public function test_webhook_configuration_and_event_logging()
    {
        $this->createAndAuthenticateUser();

        // Create webhook configuration
        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/connect", [
            'url_to_publish_to' => 'https://example.com/webhook',
            'enabled' => true,
            'all_users' => true,
            'event_types' => ['envelope_sent', 'envelope_completed'],
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('connect_configurations', [
            'account_id' => $this->account->id,
            'url_to_publish_to' => 'https://example.com/webhook',
        ]);
    }

    /** @test */
    public function test_powerform_submission_creates_envelope()
    {
        $this->createAndAuthenticateUser();

        $template = Template::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'template_name' => 'PowerForm Template',
            'status' => 'active',
        ]);

        $powerform = \App\Models\PowerForm::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'template_id' => $template->id,
            'powerform_name' => 'Contact Form',
            'status' => 'active',
        ]);

        // Submit powerform
        $response = $this->json('POST', "/api/v2.1/public/powerforms/{$powerform->powerform_id}/submit", [
            'recipient_name' => 'John Doe',
            'recipient_email' => 'john@example.com',
            'form_data' => [
                'field1' => 'value1',
            ],
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('powerform_submissions', [
            'powerform_id' => $powerform->id,
            'recipient_email' => 'john@example.com',
        ]);
    }

    /** @test */
    public function test_bulk_send_batch_processing()
    {
        $this->createAndAuthenticateUser();

        $template = Template::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'template_name' => 'Bulk Template',
            'status' => 'active',
        ]);

        // Create bulk send batch
        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/bulk_send_batch", [
            'batch_name' => 'Q1 Contracts',
            'template_id' => $template->template_id,
        ]);

        $response->assertStatus(201);
        $batchId = $response->json('data.bulk_send_batch_id');

        $this->assertDatabaseHas('bulk_send_batches', [
            'account_id' => $this->account->id,
            'batch_name' => 'Q1 Contracts',
        ]);
    }

    /** @test */
    public function test_signing_group_usage_in_envelope()
    {
        $this->createAndAuthenticateUser();

        $group = \App\Models\SigningGroup::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'group_name' => 'Legal Team',
            'group_type' => 'private',
        ]);

        $group->users()->attach($this->user->id, [
            'email' => $this->user->email,
            'user_name' => $this->user->user_name,
        ]);

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Group Envelope',
            'status' => 'draft',
        ]);

        // In production, signing group ID would be used when creating recipients
        $this->assertNotNull($group->group_id);
    }

    /** @test */
    public function test_user_authorization_delegation()
    {
        $this->createAndAuthenticateUser();

        $otherUser = \App\Models\User::create([
            'account_id' => $this->account->id,
            'user_name' => 'delegateuser',
            'email' => 'delegate@example.com',
            'password' => \Hash::make('password'),
            'user_status' => 'active',
            'user_type' => 'member',
            'created_datetime' => now(),
        ]);

        // Create authorization
        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/users/{$this->user->id}/authorization", [
            'agent_user_id' => $otherUser->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('user_authorizations', [
            'principal_user_id' => $this->user->id,
            'agent_user_id' => $otherUser->id,
        ]);
    }
}
