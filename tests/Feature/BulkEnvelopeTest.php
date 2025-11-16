<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Envelope;
use App\Models\EnvelopeRecipient;
use App\Models\EnvelopeDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * Bulk Envelope Feature Tests
 *
 * Tests API endpoints for bulk envelope operations.
 */
class BulkEnvelopeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        // Run passport install
        $this->artisan('passport:install', ['--no-interaction' => true]);

        // Create user and account
        $this->account = Account::factory()->create();
        $this->user = User::factory()->create([
            'account_id' => $this->account->id,
        ]);

        // Authenticate user
        Passport::actingAs($this->user);
    }

    /**
     * Test bulk status update API endpoint
     */
    public function test_bulk_status_update_api_endpoint(): void
    {
        $envelopes = Envelope::factory()->count(3)->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
        ]);

        $envelopeIds = $envelopes->pluck('envelope_id')->toArray();

        $response = $this->putJson(sprintf(
            '/api/v2.1/accounts/%s/envelopes/bulk/status',
            $this->account->account_id
        ), [
            'envelope_ids' => $envelopeIds,
            'status' => 'sent',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'batch_id',
                'total_envelopes',
                'processed',
                'failed',
                'errors',
                'status',
            ],
        ]);

        $data = $response->json('data');
        $this->assertEquals(3, $data['total_envelopes']);
        $this->assertEquals(3, $data['processed']);
        $this->assertEquals(0, $data['failed']);
    }

    /**
     * Test bulk void API endpoint
     */
    public function test_bulk_void_api_endpoint(): void
    {
        $envelopes = Envelope::factory()->count(2)->create([
            'account_id' => $this->account->id,
            'status' => 'sent',
        ]);

        $response = $this->postJson(sprintf(
            '/api/v2.1/accounts/%s/envelopes/bulk/void',
            $this->account->account_id
        ), [
            'envelope_ids' => $envelopes->pluck('envelope_id')->toArray(),
            'void_reason' => 'Test void reason',
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(2, $data['processed']);
        $this->assertEquals(0, $data['failed']);
    }

    /**
     * Test bulk resend API endpoint
     */
    public function test_bulk_resend_api_endpoint(): void
    {
        $envelopes = Envelope::factory()->count(2)->create([
            'account_id' => $this->account->id,
            'status' => 'sent',
        ]);

        // Add recipients
        foreach ($envelopes as $envelope) {
            EnvelopeRecipient::factory()->create([
                'envelope_id' => $envelope->id,
                'status' => 'sent',
            ]);
        }

        $response = $this->postJson(sprintf(
            '/api/v2.1/accounts/%s/envelopes/bulk/resend',
            $this->account->account_id
        ), [
            'envelope_ids' => $envelopes->pluck('envelope_id')->toArray(),
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(2, $data['processed']);
    }

    /**
     * Test bulk recipient update API endpoint
     */
    public function test_bulk_recipient_update_api_endpoint(): void
    {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
        ]);

        $recipient = EnvelopeRecipient::factory()->create([
            'envelope_id' => $envelope->id,
            'email' => 'old@example.com',
        ]);

        $response = $this->putJson(sprintf(
            '/api/v2.1/accounts/%s/envelopes/bulk/recipients',
            $this->account->account_id
        ), [
            'updates' => [
                [
                    'envelope_id' => $envelope->envelope_id,
                    'recipient_id' => $recipient->recipient_id,
                    'email' => 'new@example.com',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(1, $data['processed']);

        // Verify database update
        $this->assertDatabaseHas('envelope_recipients', [
            'recipient_id' => $recipient->recipient_id,
            'email' => 'new@example.com',
        ]);
    }

    /**
     * Test bulk recipient resend API endpoint
     */
    public function test_bulk_recipient_resend_api_endpoint(): void
    {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'sent',
        ]);

        $recipient = EnvelopeRecipient::factory()->create([
            'envelope_id' => $envelope->id,
            'status' => 'sent',
        ]);

        $response = $this->postJson(sprintf(
            '/api/v2.1/accounts/%s/envelopes/bulk/recipients/resend',
            $this->account->account_id
        ), [
            'resends' => [
                [
                    'envelope_id' => $envelope->envelope_id,
                    'recipient_id' => $recipient->recipient_id,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(1, $data['processed']);
    }

    /**
     * Test bulk recipient remove API endpoint
     */
    public function test_bulk_recipient_remove_api_endpoint(): void
    {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
        ]);

        $recipient = EnvelopeRecipient::factory()->create([
            'envelope_id' => $envelope->id,
        ]);

        $response = $this->deleteJson(sprintf(
            '/api/v2.1/accounts/%s/envelopes/bulk/recipients',
            $this->account->account_id
        ), [
            'removals' => [
                [
                    'envelope_id' => $envelope->envelope_id,
                    'recipient_id' => $recipient->recipient_id,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(1, $data['processed']);

        // Verify deletion
        $this->assertDatabaseMissing('envelope_recipients', [
            'recipient_id' => $recipient->recipient_id,
        ]);
    }

    /**
     * Test bulk document add API endpoint
     */
    public function test_bulk_document_add_api_endpoint(): void
    {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
        ]);

        $response = $this->postJson(sprintf(
            '/api/v2.1/accounts/%s/envelopes/bulk/documents',
            $this->account->account_id
        ), [
            'operations' => [
                [
                    'envelope_id' => $envelope->envelope_id,
                    'documents' => [
                        [
                            'document_id' => 'doc1',
                            'name' => 'Document 1',
                            'file_extension' => 'pdf',
                            'order' => 1,
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(1, $data['processed']);

        // Verify document creation
        $this->assertDatabaseHas('envelope_documents', [
            'envelope_id' => $envelope->id,
            'document_id' => 'doc1',
        ]);
    }

    /**
     * Test bulk document replace API endpoint
     */
    public function test_bulk_document_replace_api_endpoint(): void
    {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
        ]);

        $document = EnvelopeDocument::factory()->create([
            'envelope_id' => $envelope->id,
            'name' => 'Old Name',
        ]);

        $response = $this->putJson(sprintf(
            '/api/v2.1/accounts/%s/envelopes/bulk/documents',
            $this->account->account_id
        ), [
            'operations' => [
                [
                    'envelope_id' => $envelope->envelope_id,
                    'document_id' => $document->document_id,
                    'name' => 'New Name',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(1, $data['processed']);

        // Verify update
        $this->assertDatabaseHas('envelope_documents', [
            'document_id' => $document->document_id,
            'name' => 'New Name',
        ]);
    }

    /**
     * Test bulk document delete API endpoint
     */
    public function test_bulk_document_delete_api_endpoint(): void
    {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
        ]);

        $document = EnvelopeDocument::factory()->create([
            'envelope_id' => $envelope->id,
        ]);

        $response = $this->deleteJson(sprintf(
            '/api/v2.1/accounts/%s/envelopes/bulk/documents',
            $this->account->account_id
        ), [
            'deletions' => [
                [
                    'envelope_id' => $envelope->envelope_id,
                    'document_id' => $document->document_id,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(1, $data['processed']);

        // Verify deletion
        $this->assertDatabaseMissing('envelope_documents', [
            'document_id' => $document->document_id,
        ]);
    }

    /**
     * Test bulk download API endpoint
     */
    public function test_bulk_download_api_endpoint(): void
    {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'completed',
        ]);

        EnvelopeDocument::factory()->count(2)->create([
            'envelope_id' => $envelope->id,
        ]);

        $response = $this->postJson(sprintf(
            '/api/v2.1/accounts/%s/envelopes/bulk/download',
            $this->account->account_id
        ), [
            'envelope_ids' => [$envelope->envelope_id],
            'include_certificate' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'batch_id',
                'download_url',
                'total_files',
                'files',
                'expires_at',
                'status',
            ],
        ]);

        $data = $response->json('data');
        $this->assertEquals(3, $data['total_files']); // 2 docs + 1 certificate
        $this->assertEquals('ready', $data['status']);
    }

    /**
     * Test bulk operations validate permissions
     */
    public function test_bulk_operations_validate_permissions(): void
    {
        // Test without authentication
        Passport::actingAs(null);

        $response = $this->putJson(sprintf(
            '/api/v2.1/accounts/%s/envelopes/bulk/status',
            $this->account->account_id
        ), [
            'envelope_ids' => ['env1'],
            'status' => 'sent',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test bulk operations validate input
     */
    public function test_bulk_operations_validate_input(): void
    {
        // Test with missing required fields
        $response = $this->putJson(sprintf(
            '/api/v2.1/accounts/%s/envelopes/bulk/status',
            $this->account->account_id
        ), [
            // Missing envelope_ids
            'status' => 'sent',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['envelope_ids']);
    }

    /**
     * Test bulk operations handle not found
     */
    public function test_bulk_operations_handle_not_found(): void
    {
        $response = $this->putJson(sprintf(
            '/api/v2.1/accounts/%s/envelopes/bulk/status',
            $this->account->account_id
        ), [
            'envelope_ids' => ['invalid-id-1', 'invalid-id-2'],
            'status' => 'sent',
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(2, $data['failed']);
        $this->assertEquals('completed_with_errors', $data['status']);
    }

    /**
     * Test bulk operations transaction rollback
     */
    public function test_bulk_operations_transaction_rollback(): void
    {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
        ]);

        $recipient = EnvelopeRecipient::factory()->create([
            'envelope_id' => $envelope->id,
        ]);

        // Try to delete a non-existent recipient
        $response = $this->deleteJson(sprintf(
            '/api/v2.1/accounts/%s/envelopes/bulk/recipients',
            $this->account->account_id
        ), [
            'removals' => [
                [
                    'envelope_id' => $envelope->envelope_id,
                    'recipient_id' => 'invalid-id',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(1, $data['failed']);

        // Verify original recipient still exists
        $this->assertDatabaseHas('envelope_recipients', [
            'recipient_id' => $recipient->recipient_id,
        ]);
    }

    /**
     * Test bulk operations track errors
     */
    public function test_bulk_operations_track_errors(): void
    {
        $validEnvelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
        ]);

        $response = $this->putJson(sprintf(
            '/api/v2.1/accounts/%s/envelopes/bulk/status',
            $this->account->account_id
        ), [
            'envelope_ids' => [
                $validEnvelope->envelope_id,
                'invalid-id-1',
                'invalid-id-2',
            ],
            'status' => 'sent',
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');

        // Verify error tracking
        $this->assertArrayHasKey('errors', $data);
        $this->assertCount(2, $data['errors']);
        $this->assertEquals('invalid-id-1', $data['errors'][0]['envelope_id']);
        $this->assertArrayHasKey('error', $data['errors'][0]);
    }

    /**
     * Test bulk void validates void reason
     */
    public function test_bulk_void_validates_void_reason(): void
    {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'sent',
        ]);

        // Test without void_reason
        $response = $this->postJson(sprintf(
            '/api/v2.1/accounts/%s/envelopes/bulk/void',
            $this->account->account_id
        ), [
            'envelope_ids' => [$envelope->envelope_id],
            // Missing void_reason
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['void_reason']);
    }

    /**
     * Test bulk operations return batch ID
     */
    public function test_bulk_operations_return_batch_id(): void
    {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
        ]);

        $response = $this->putJson(sprintf(
            '/api/v2.1/accounts/%s/envelopes/bulk/status',
            $this->account->account_id
        ), [
            'envelope_ids' => [$envelope->envelope_id],
            'status' => 'sent',
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertArrayHasKey('batch_id', $data);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $data['batch_id']
        );
    }
}
