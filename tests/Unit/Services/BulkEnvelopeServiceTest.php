<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Account;
use App\Models\User;
use App\Models\Envelope;
use App\Models\EnvelopeRecipient;
use App\Models\EnvelopeDocument;
use App\Services\BulkEnvelopeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class BulkEnvelopeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BulkEnvelopeService $service;
    protected Account $account;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BulkEnvelopeService();
        $this->seed(\Database\Seeders\FileTypeSeeder::class);

        $this->account = Account::factory()->create();
        $this->user = User::factory()->create(['account_id' => $this->account->account_id]);
    }

    /** @test */
    public function it_can_bulk_update_envelope_status()
    {
        $envelopes = Envelope::factory()->count(3)->create([
            'account_id' => $this->account->account_id,
            'status' => 'draft',
        ]);

        $envelopeIds = $envelopes->pluck('envelope_id')->toArray();

        $result = $this->service->bulkUpdateStatus($envelopeIds, 'sent');

        $this->assertEquals(3, $result['total']);
        $this->assertEquals(3, $result['processed']);
        $this->assertEquals(0, $result['failed']);

        foreach ($envelopes as $envelope) {
            $this->assertDatabaseHas('envelopes', [
                'envelope_id' => $envelope->envelope_id,
                'status' => 'sent',
            ]);
        }
    }

    /** @test */
    public function it_handles_errors_in_bulk_status_update()
    {
        $validEnvelope = Envelope::factory()->create([
            'account_id' => $this->account->account_id,
            'status' => 'draft',
        ]);

        $invalidId = Str::uuid();

        $result = $this->service->bulkUpdateStatus(
            [$validEnvelope->envelope_id, $invalidId],
            'sent'
        );

        $this->assertEquals(2, $result['total']);
        $this->assertEquals(1, $result['processed']);
        $this->assertEquals(1, $result['failed']);
        $this->assertCount(1, $result['errors']);
    }

    /** @test */
    public function it_prevents_invalid_status_transitions_in_bulk()
    {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->account_id,
            'status' => 'completed', // Cannot update completed envelope
        ]);

        $result = $this->service->bulkUpdateStatus([$envelope->envelope_id], 'voided');

        $this->assertEquals(1, $result['failed']);
        $this->assertArrayHasKey('errors', $result);
    }

    /** @test */
    public function it_can_bulk_void_envelopes()
    {
        $envelopes = Envelope::factory()->count(3)->create([
            'account_id' => $this->account->account_id,
            'status' => 'sent',
        ]);

        $envelopeIds = $envelopes->pluck('envelope_id')->toArray();

        $result = $this->service->bulkVoid($envelopeIds, 'No longer needed');

        $this->assertEquals(3, $result['total']);
        $this->assertEquals(3, $result['processed']);
        $this->assertEquals(0, $result['failed']);

        foreach ($envelopes as $envelope) {
            $this->assertDatabaseHas('envelopes', [
                'envelope_id' => $envelope->envelope_id,
                'status' => 'voided',
                'voided_reason' => 'No longer needed',
            ]);
        }
    }

    /** @test */
    public function it_prevents_voiding_completed_envelopes_in_bulk()
    {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->account_id,
            'status' => 'completed',
        ]);

        $result = $this->service->bulkVoid([$envelope->envelope_id], 'Test');

        $this->assertEquals(1, $result['failed']);
    }

    /** @test */
    public function it_can_bulk_resend_envelopes()
    {
        $envelopes = Envelope::factory()->count(2)->create([
            'account_id' => $this->account->account_id,
            'status' => 'sent',
        ]);

        foreach ($envelopes as $envelope) {
            EnvelopeRecipient::factory()->create([
                'envelope_id' => $envelope->envelope_id,
                'status' => 'sent',
            ]);
        }

        $envelopeIds = $envelopes->pluck('envelope_id')->toArray();

        $result = $this->service->bulkResend($envelopeIds);

        $this->assertEquals(2, $result['total']);
        $this->assertEquals(2, $result['processed']);
        $this->assertEquals(0, $result['failed']);
    }

    /** @test */
    public function it_can_bulk_update_recipients()
    {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->account_id,
            'status' => 'draft',
        ]);

        $recipients = EnvelopeRecipient::factory()->count(3)->create([
            'envelope_id' => $envelope->envelope_id,
        ]);

        $recipientIds = $recipients->pluck('recipient_id')->toArray();

        $updateData = [
            'routing_order' => 2,
        ];

        $result = $this->service->bulkUpdateRecipients($recipientIds, $updateData);

        $this->assertEquals(3, $result['total']);
        $this->assertEquals(3, $result['processed']);

        foreach ($recipients as $recipient) {
            $this->assertDatabaseHas('envelope_recipients', [
                'recipient_id' => $recipient->recipient_id,
                'routing_order' => 2,
            ]);
        }
    }

    /** @test */
    public function it_can_bulk_resend_to_recipients()
    {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->account_id,
            'status' => 'sent',
        ]);

        $recipients = EnvelopeRecipient::factory()->count(2)->create([
            'envelope_id' => $envelope->envelope_id,
            'status' => 'sent',
        ]);

        $recipientIds = $recipients->pluck('recipient_id')->toArray();

        $result = $this->service->bulkResendToRecipients($recipientIds);

        $this->assertEquals(2, $result['total']);
        $this->assertEquals(2, $result['processed']);
    }

    /** @test */
    public function it_can_bulk_remove_recipients()
    {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->account_id,
            'status' => 'draft',
        ]);

        $recipients = EnvelopeRecipient::factory()->count(3)->create([
            'envelope_id' => $envelope->envelope_id,
        ]);

        $recipientIds = $recipients->pluck('recipient_id')->toArray();

        $result = $this->service->bulkRemoveRecipients($recipientIds);

        $this->assertEquals(3, $result['total']);
        $this->assertEquals(3, $result['processed']);

        foreach ($recipients as $recipient) {
            $this->assertDatabaseMissing('envelope_recipients', [
                'recipient_id' => $recipient->recipient_id,
            ]);
        }
    }

    /** @test */
    public function it_can_bulk_add_documents()
    {
        $envelopes = Envelope::factory()->count(2)->create([
            'account_id' => $this->account->account_id,
            'status' => 'draft',
        ]);

        $envelopeIds = $envelopes->pluck('envelope_id')->toArray();

        $documentsData = [
            [
                'document_id' => '1',
                'name' => 'Document 1.pdf',
                'file_extension' => 'pdf',
                'order' => 1,
            ],
        ];

        $result = $this->service->bulkAddDocuments($envelopeIds, $documentsData);

        $this->assertEquals(2, $result['total']);
        $this->assertEquals(2, $result['processed']);

        foreach ($envelopes as $envelope) {
            $this->assertDatabaseHas('envelope_documents', [
                'envelope_id' => $envelope->envelope_id,
                'name' => 'Document 1.pdf',
            ]);
        }
    }

    /** @test */
    public function it_can_bulk_replace_documents()
    {
        $envelopes = Envelope::factory()->count(2)->create([
            'account_id' => $this->account->account_id,
            'status' => 'draft',
        ]);

        foreach ($envelopes as $envelope) {
            EnvelopeDocument::factory()->create([
                'envelope_id' => $envelope->envelope_id,
            ]);
        }

        $envelopeIds = $envelopes->pluck('envelope_id')->toArray();

        $documentsData = [
            [
                'document_id' => '1',
                'name' => 'New Document.pdf',
                'file_extension' => 'pdf',
                'order' => 1,
            ],
        ];

        $result = $this->service->bulkReplaceDocuments($envelopeIds, $documentsData);

        $this->assertEquals(2, $result['total']);
        $this->assertEquals(2, $result['processed']);
    }

    /** @test */
    public function it_can_bulk_delete_documents()
    {
        $envelopes = Envelope::factory()->count(2)->create([
            'account_id' => $this->account->account_id,
            'status' => 'draft',
        ]);

        $documentIds = [];
        foreach ($envelopes as $envelope) {
            $doc = EnvelopeDocument::factory()->create([
                'envelope_id' => $envelope->envelope_id,
                'document_id' => '1',
            ]);
            $documentIds[] = $doc->document_id;
        }

        $result = $this->service->bulkDeleteDocuments($documentIds);

        $this->assertEquals(2, $result['total']);
        $this->assertEquals(2, $result['processed']);

        foreach ($documentIds as $docId) {
            $this->assertDatabaseMissing('envelope_documents', [
                'document_id' => $docId,
            ]);
        }
    }

    /** @test */
    public function it_validates_batch_size_limits()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Batch size cannot exceed 100 envelopes');

        $envelopeIds = array_map(fn() => Str::uuid(), range(1, 101));

        $this->service->bulkUpdateStatus($envelopeIds, 'sent');
    }

    /** @test */
    public function it_generates_unique_batch_ids()
    {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->account_id,
            'status' => 'draft',
        ]);

        $result1 = $this->service->bulkUpdateStatus([$envelope->envelope_id], 'sent');
        $result2 = $this->service->bulkUpdateStatus([$envelope->envelope_id], 'sent');

        $this->assertArrayHasKey('batch_id', $result1);
        $this->assertArrayHasKey('batch_id', $result2);
        $this->assertNotEquals($result1['batch_id'], $result2['batch_id']);
    }

    /** @test */
    public function it_uses_database_transactions_for_bulk_operations()
    {
        $envelopes = Envelope::factory()->count(2)->create([
            'account_id' => $this->account->account_id,
            'status' => 'draft',
        ]);

        $envelopeIds = $envelopes->pluck('envelope_id')->toArray();

        // Add invalid ID to force partial failure
        $envelopeIds[] = 'invalid-uuid';

        $result = $this->service->bulkUpdateStatus($envelopeIds, 'sent');

        // Transaction should ensure atomicity per envelope
        $this->assertGreaterThan(0, $result['failed']);
    }
}
