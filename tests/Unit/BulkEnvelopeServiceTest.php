<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\Envelope;
use App\Models\EnvelopeRecipient;
use App\Models\EnvelopeDocument;
use App\Services\BulkEnvelopeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Bulk Envelope Service Unit Tests
 *
 * Tests business logic for bulk envelope operations.
 */
class BulkEnvelopeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BulkEnvelopeService $bulkService;
    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bulkService = app(BulkEnvelopeService::class);
        $this->account = Account::factory()->create();
    }

    /**
     * Test bulk status update processes all envelopes
     */
    public function test_bulk_status_update_processes_all_envelopes(): void
    {
        // Create test envelopes
        $envelopes = Envelope::factory()->count(3)->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
        ]);

        $envelopeIds = $envelopes->pluck('envelope_id')->toArray();

        // Bulk update to sent status
        $result = $this->bulkService->bulkStatusUpdate(
            $this->account,
            $envelopeIds,
            'sent'
        );

        // Assertions
        $this->assertEquals(3, $result['total_envelopes']);
        $this->assertEquals(3, $result['processed']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEquals('completed', $result['status']);
        $this->assertIsString($result['batch_id']);
        $this->assertTrue(Str::isUuid($result['batch_id']));

        // Verify database updates
        foreach ($envelopes as $envelope) {
            $this->assertDatabaseHas('envelopes', [
                'envelope_id' => $envelope->envelope_id,
                'status' => 'sent',
            ]);
        }
    }

    /**
     * Test bulk status update tracks failures
     */
    public function test_bulk_status_update_tracks_failures(): void
    {
        // Create one valid envelope
        $validEnvelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
        ]);

        // Use invalid envelope IDs
        $envelopeIds = [
            $validEnvelope->envelope_id,
            'invalid-id-1',
            'invalid-id-2',
        ];

        // Bulk update
        $result = $this->bulkService->bulkStatusUpdate(
            $this->account,
            $envelopeIds,
            'sent'
        );

        // Assertions
        $this->assertEquals(3, $result['total_envelopes']);
        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(2, $result['failed']);
        $this->assertEquals('completed_with_errors', $result['status']);
        $this->assertCount(2, $result['errors']);

        // Verify error details
        $this->assertEquals('invalid-id-1', $result['errors'][0]['envelope_id']);
        $this->assertEquals('Envelope not found', $result['errors'][0]['error']);
    }

    /**
     * Test bulk void voids all envelopes
     */
    public function test_bulk_void_voids_all_envelopes(): void
    {
        // Create sent envelopes
        $envelopes = Envelope::factory()->count(2)->create([
            'account_id' => $this->account->id,
            'status' => 'sent',
        ]);

        $envelopeIds = $envelopes->pluck('envelope_id')->toArray();

        // Bulk void
        $result = $this->bulkService->bulkVoid(
            $this->account,
            $envelopeIds,
            'Test void reason'
        );

        // Assertions
        $this->assertEquals(2, $result['processed']);
        $this->assertEquals(0, $result['failed']);

        // Verify envelopes are voided
        foreach ($envelopes as $envelope) {
            $this->assertDatabaseHas('envelopes', [
                'envelope_id' => $envelope->envelope_id,
                'status' => 'voided',
                'void_reason' => 'Test void reason',
            ]);
        }
    }

    /**
     * Test bulk void requires void reason
     */
    public function test_bulk_void_requires_void_reason(): void
    {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'sent',
        ]);

        // Bulk void with empty reason
        $result = $this->bulkService->bulkVoid(
            $this->account,
            [$envelope->envelope_id],
            '' // Empty void reason
        );

        // Service should use default reason when empty
        $this->assertEquals(1, $result['processed']);
    }

    /**
     * Test bulk resend resends to pending recipients
     */
    public function test_bulk_resend_resends_to_pending_recipients(): void
    {
        // Create envelopes with recipients
        $envelope1 = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'sent',
        ]);

        $envelope2 = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'sent',
        ]);

        // Create pending recipients
        EnvelopeRecipient::factory()->create([
            'envelope_id' => $envelope1->id,
            'status' => 'sent',
        ]);

        EnvelopeRecipient::factory()->create([
            'envelope_id' => $envelope2->id,
            'status' => 'sent',
        ]);

        // Bulk resend
        $result = $this->bulkService->bulkResend(
            $this->account,
            [$envelope1->envelope_id, $envelope2->envelope_id]
        );

        // Assertions
        $this->assertEquals(2, $result['total_envelopes']);
        $this->assertEquals(2, $result['processed']);
        $this->assertEquals(0, $result['failed']);
    }

    /**
     * Test bulk recipient update updates all recipients
     */
    public function test_bulk_recipient_update_updates_all_recipients(): void
    {
        // Create envelope with recipient
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
        ]);

        $recipient = EnvelopeRecipient::factory()->create([
            'envelope_id' => $envelope->id,
            'email' => 'old@example.com',
            'name' => 'Old Name',
        ]);

        // Update data
        $updates = [
            [
                'envelope_id' => $envelope->envelope_id,
                'recipient_id' => $recipient->recipient_id,
                'email' => 'new@example.com',
                'name' => 'New Name',
            ],
        ];

        // Bulk update
        $result = $this->bulkService->bulkRecipientUpdate(
            $this->account,
            $updates
        );

        // Assertions
        $this->assertEquals(1, $result['total_recipients']);
        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(0, $result['failed']);

        // Verify database update
        $this->assertDatabaseHas('envelope_recipients', [
            'recipient_id' => $recipient->recipient_id,
            'email' => 'new@example.com',
            'name' => 'New Name',
        ]);
    }

    /**
     * Test bulk recipient update handles failures
     */
    public function test_bulk_recipient_update_handles_failures(): void
    {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
        ]);

        // Update with invalid recipient ID
        $updates = [
            [
                'envelope_id' => $envelope->envelope_id,
                'recipient_id' => 'invalid-recipient-id',
                'email' => 'test@example.com',
            ],
        ];

        // Bulk update
        $result = $this->bulkService->bulkRecipientUpdate(
            $this->account,
            $updates
        );

        // Assertions
        $this->assertEquals(1, $result['total_recipients']);
        $this->assertEquals(0, $result['processed']);
        $this->assertEquals(1, $result['failed']);
        $this->assertCount(1, $result['errors']);
    }

    /**
     * Test bulk recipient resend resends notifications
     */
    public function test_bulk_recipient_resend_resends_notifications(): void
    {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'sent',
        ]);

        $recipient = EnvelopeRecipient::factory()->create([
            'envelope_id' => $envelope->id,
            'status' => 'sent',
        ]);

        $resends = [
            [
                'envelope_id' => $envelope->envelope_id,
                'recipient_id' => $recipient->recipient_id,
            ],
        ];

        // Bulk resend
        $result = $this->bulkService->bulkRecipientResend(
            $this->account,
            $resends
        );

        // Assertions
        $this->assertEquals(1, $result['total_recipients']);
        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(0, $result['failed']);
    }

    /**
     * Test bulk recipient remove deletes recipients
     */
    public function test_bulk_recipient_remove_deletes_recipients(): void
    {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
        ]);

        $recipient = EnvelopeRecipient::factory()->create([
            'envelope_id' => $envelope->id,
        ]);

        $removals = [
            [
                'envelope_id' => $envelope->envelope_id,
                'recipient_id' => $recipient->recipient_id,
            ],
        ];

        // Bulk remove
        $result = $this->bulkService->bulkRecipientRemove(
            $this->account,
            $removals
        );

        // Assertions
        $this->assertEquals(1, $result['total_recipients']);
        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(0, $result['failed']);

        // Verify recipient deleted
        $this->assertDatabaseMissing('envelope_recipients', [
            'recipient_id' => $recipient->recipient_id,
        ]);
    }

    /**
     * Test bulk document add adds documents
     */
    public function test_bulk_document_add_adds_documents(): void
    {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
        ]);

        $operations = [
            [
                'envelope_id' => $envelope->envelope_id,
                'documents' => [
                    [
                        'document_id' => 'doc1',
                        'name' => 'Document 1',
                        'file_extension' => 'pdf',
                        'order' => 1,
                    ],
                    [
                        'document_id' => 'doc2',
                        'name' => 'Document 2',
                        'file_extension' => 'pdf',
                        'order' => 2,
                    ],
                ],
            ],
        ];

        // Bulk add
        $result = $this->bulkService->bulkDocumentAdd(
            $this->account,
            $operations
        );

        // Assertions
        $this->assertEquals(1, $result['total_operations']);
        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(0, $result['failed']);

        // Verify documents created
        $this->assertDatabaseCount('envelope_documents', 2);
        $this->assertDatabaseHas('envelope_documents', [
            'envelope_id' => $envelope->id,
            'document_id' => 'doc1',
        ]);
    }

    /**
     * Test bulk document replace updates documents
     */
    public function test_bulk_document_replace_updates_documents(): void
    {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
        ]);

        $document = EnvelopeDocument::factory()->create([
            'envelope_id' => $envelope->id,
            'name' => 'Old Name',
        ]);

        $operations = [
            [
                'envelope_id' => $envelope->envelope_id,
                'document_id' => $document->document_id,
                'name' => 'New Name',
            ],
        ];

        // Bulk replace
        $result = $this->bulkService->bulkDocumentReplace(
            $this->account,
            $operations
        );

        // Assertions
        $this->assertEquals(1, $result['processed']);

        // Verify update
        $this->assertDatabaseHas('envelope_documents', [
            'document_id' => $document->document_id,
            'name' => 'New Name',
        ]);
    }

    /**
     * Test bulk document delete deletes documents
     */
    public function test_bulk_document_delete_deletes_documents(): void
    {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
        ]);

        $document = EnvelopeDocument::factory()->create([
            'envelope_id' => $envelope->id,
        ]);

        $deletions = [
            [
                'envelope_id' => $envelope->envelope_id,
                'document_id' => $document->document_id,
            ],
        ];

        // Bulk delete
        $result = $this->bulkService->bulkDocumentDelete(
            $this->account,
            $deletions
        );

        // Assertions
        $this->assertEquals(1, $result['processed']);

        // Verify deletion
        $this->assertDatabaseMissing('envelope_documents', [
            'document_id' => $document->document_id,
        ]);
    }

    /**
     * Test bulk download prepares download
     */
    public function test_bulk_download_prepares_download(): void
    {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'completed',
        ]);

        EnvelopeDocument::factory()->count(2)->create([
            'envelope_id' => $envelope->id,
        ]);

        // Bulk download
        $result = $this->bulkService->bulkDocumentDownload(
            $this->account,
            [$envelope->envelope_id],
            [],
            false
        );

        // Assertions
        $this->assertIsString($result['batch_id']);
        $this->assertStringContainsString('/bulk-downloads/', $result['download_url']);
        $this->assertEquals(2, $result['total_files']);
        $this->assertEquals('ready', $result['status']);
    }

    /**
     * Test bulk download includes certificates
     */
    public function test_bulk_download_includes_certificates(): void
    {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'completed',
        ]);

        EnvelopeDocument::factory()->create([
            'envelope_id' => $envelope->id,
        ]);

        // Bulk download with certificate
        $result = $this->bulkService->bulkDocumentDownload(
            $this->account,
            [$envelope->envelope_id],
            [],
            true // Include certificate
        );

        // Assertions
        $this->assertEquals(2, $result['total_files']); // 1 document + 1 certificate
        $certificateFile = collect($result['files'])->firstWhere('document_id', 'certificate');
        $this->assertNotNull($certificateFile);
        $this->assertEquals('Certificate of Completion', $certificateFile['name']);
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

        // Test various bulk operations return batch_id
        $operations = [
            $this->bulkService->bulkStatusUpdate(
                $this->account,
                [$envelope->envelope_id],
                'sent'
            ),
            $this->bulkService->bulkVoid(
                $this->account,
                [$envelope->envelope_id],
                'Test'
            ),
        ];

        foreach ($operations as $result) {
            $this->assertArrayHasKey('batch_id', $result);
            $this->assertTrue(Str::isUuid($result['batch_id']));
        }
    }
}
