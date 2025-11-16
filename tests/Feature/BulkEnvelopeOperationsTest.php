<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Account;
use App\Models\User;
use App\Models\Envelope;
use App\Models\EnvelopeRecipient;
use App\Models\EnvelopeDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

class BulkEnvelopeOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected Account $account;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\FileTypeSeeder::class);

        $this->account = Account::factory()->create();
        $this->user = User::factory()->create([
            'account_id' => $this->account->account_id,
        ]);

        Passport::actingAs($this->user);
    }

    /** @test */
    public function user_can_bulk_update_envelope_status()
    {
        $envelopes = Envelope::factory()->count(3)->create([
            'account_id' => $this->account->account_id,
            'status' => 'draft',
        ]);

        $envelopeIds = $envelopes->pluck('envelope_id')->toArray();

        $response = $this->putJson(
            "/api/v2.1/accounts/{$this->account->account_id}/envelopes/bulk/status",
            [
                'envelope_ids' => $envelopeIds,
                'status' => 'sent',
            ]
        );

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'batch_id',
                'total',
                'processed',
                'failed',
            ],
        ]);

        $this->assertEquals(3, $response->json('data.total'));
        $this->assertEquals(3, $response->json('data.processed'));
        $this->assertEquals(0, $response->json('data.failed'));
    }

    /** @test */
    public function bulk_status_update_validates_envelope_ids()
    {
        $response = $this->putJson(
            "/api/v2.1/accounts/{$this->account->account_id}/envelopes/bulk/status",
            [
                'envelope_ids' => [],
                'status' => 'sent',
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['envelope_ids']);
    }

    /** @test */
    public function bulk_status_update_enforces_batch_size_limit()
    {
        $envelopeIds = array_fill(0, 101, 'fake-uuid');

        $response = $this->putJson(
            "/api/v2.1/accounts/{$this->account->account_id}/envelopes/bulk/status",
            [
                'envelope_ids' => $envelopeIds,
                'status' => 'sent',
            ]
        );

        $response->assertStatus(422);
    }

    /** @test */
    public function user_can_bulk_void_envelopes()
    {
        $envelopes = Envelope::factory()->count(3)->create([
            'account_id' => $this->account->account_id,
            'status' => 'sent',
        ]);

        $envelopeIds = $envelopes->pluck('envelope_id')->toArray();

        $response = $this->postJson(
            "/api/v2.1/accounts/{$this->account->account_id}/envelopes/bulk/void",
            [
                'envelope_ids' => $envelopeIds,
                'voided_reason' => 'No longer needed',
            ]
        );

        $response->assertOk();
        $this->assertEquals(3, $response->json('data.processed'));

        foreach ($envelopes as $envelope) {
            $this->assertDatabaseHas('envelopes', [
                'envelope_id' => $envelope->envelope_id,
                'status' => 'voided',
            ]);
        }
    }

    /** @test */
    public function bulk_void_requires_reason()
    {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->account_id,
            'status' => 'sent',
        ]);

        $response = $this->postJson(
            "/api/v2.1/accounts/{$this->account->account_id}/envelopes/bulk/void",
            [
                'envelope_ids' => [$envelope->envelope_id],
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['voided_reason']);
    }

    /** @test */
    public function user_can_bulk_resend_envelopes()
    {
        $envelopes = Envelope::factory()->count(2)->create([
            'account_id' => $this->account->account_id,
            'status' => 'sent',
        ]);

        foreach ($envelopes as $envelope) {
            EnvelopeRecipient::factory()->create([
                'envelope_id' => $envelope->envelope_id,
            ]);
        }

        $envelopeIds = $envelopes->pluck('envelope_id')->toArray();

        $response = $this->postJson(
            "/api/v2.1/accounts/{$this->account->account_id}/envelopes/bulk/resend",
            [
                'envelope_ids' => $envelopeIds,
            ]
        );

        $response->assertOk();
        $this->assertEquals(2, $response->json('data.processed'));
    }

    /** @test */
    public function user_can_bulk_update_recipients()
    {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->account_id,
            'status' => 'draft',
        ]);

        $recipients = EnvelopeRecipient::factory()->count(3)->create([
            'envelope_id' => $envelope->envelope_id,
        ]);

        $recipientIds = $recipients->pluck('recipient_id')->toArray();

        $response = $this->putJson(
            "/api/v2.1/accounts/{$this->account->account_id}/envelopes/bulk/recipients",
            [
                'recipient_ids' => $recipientIds,
                'routing_order' => 2,
            ]
        );

        $response->assertOk();
        $this->assertEquals(3, $response->json('data.processed'));
    }

    /** @test */
    public function user_can_bulk_resend_to_recipients()
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

        $response = $this->postJson(
            "/api/v2.1/accounts/{$this->account->account_id}/envelopes/bulk/recipients/resend",
            [
                'recipient_ids' => $recipientIds,
            ]
        );

        $response->assertOk();
        $this->assertEquals(2, $response->json('data.processed'));
    }

    /** @test */
    public function user_can_bulk_remove_recipients()
    {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->account_id,
            'status' => 'draft',
        ]);

        $recipients = EnvelopeRecipient::factory()->count(3)->create([
            'envelope_id' => $envelope->envelope_id,
        ]);

        $recipientIds = $recipients->pluck('recipient_id')->toArray();

        $response = $this->deleteJson(
            "/api/v2.1/accounts/{$this->account->account_id}/envelopes/bulk/recipients",
            [
                'recipient_ids' => $recipientIds,
            ]
        );

        $response->assertOk();
        $this->assertEquals(3, $response->json('data.processed'));
    }

    /** @test */
    public function user_can_bulk_add_documents()
    {
        $envelopes = Envelope::factory()->count(2)->create([
            'account_id' => $this->account->account_id,
            'status' => 'draft',
        ]);

        $envelopeIds = $envelopes->pluck('envelope_id')->toArray();

        $response = $this->postJson(
            "/api/v2.1/accounts/{$this->account->account_id}/envelopes/bulk/documents",
            [
                'envelope_ids' => $envelopeIds,
                'documents' => [
                    [
                        'document_id' => '1',
                        'name' => 'Document 1.pdf',
                        'file_extension' => 'pdf',
                    ],
                ],
            ]
        );

        $response->assertOk();
        $this->assertEquals(2, $response->json('data.processed'));
    }

    /** @test */
    public function user_can_bulk_replace_documents()
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

        $response = $this->putJson(
            "/api/v2.1/accounts/{$this->account->account_id}/envelopes/bulk/documents",
            [
                'envelope_ids' => $envelopeIds,
                'documents' => [
                    [
                        'document_id' => '1',
                        'name' => 'New Document.pdf',
                        'file_extension' => 'pdf',
                    ],
                ],
            ]
        );

        $response->assertOk();
        $this->assertEquals(2, $response->json('data.processed'));
    }

    /** @test */
    public function user_can_bulk_delete_documents()
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

        $response = $this->deleteJson(
            "/api/v2.1/accounts/{$this->account->account_id}/envelopes/bulk/documents",
            [
                'document_ids' => $documentIds,
            ]
        );

        $response->assertOk();
        $this->assertEquals(2, $response->json('data.processed'));
    }

    /** @test */
    public function bulk_operations_return_error_details()
    {
        $validEnvelope = Envelope::factory()->create([
            'account_id' => $this->account->account_id,
            'status' => 'draft',
        ]);

        $invalidId = 'invalid-uuid';

        $response = $this->putJson(
            "/api/v2.1/accounts/{$this->account->account_id}/envelopes/bulk/status",
            [
                'envelope_ids' => [$validEnvelope->envelope_id, $invalidId],
                'status' => 'sent',
            ]
        );

        $response->assertOk();
        $this->assertEquals(2, $response->json('data.total'));
        $this->assertGreaterThan(0, $response->json('data.failed'));
        $this->assertArrayHasKey('errors', $response->json('data'));
    }

    /** @test */
    public function bulk_operations_generate_unique_batch_ids()
    {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->account_id,
            'status' => 'draft',
        ]);

        $response1 = $this->putJson(
            "/api/v2.1/accounts/{$this->account->account_id}/envelopes/bulk/status",
            [
                'envelope_ids' => [$envelope->envelope_id],
                'status' => 'sent',
            ]
        );

        $response2 = $this->putJson(
            "/api/v2.1/accounts/{$this->account->account_id}/envelopes/bulk/status",
            [
                'envelope_ids' => [$envelope->envelope_id],
                'status' => 'sent',
            ]
        );

        $this->assertNotEquals(
            $response1->json('data.batch_id'),
            $response2->json('data.batch_id')
        );
    }

    /** @test */
    public function user_can_bulk_download_documents()
    {
        $envelopes = Envelope::factory()->count(2)->create([
            'account_id' => $this->account->account_id,
            'status' => 'completed',
        ]);

        foreach ($envelopes as $envelope) {
            EnvelopeDocument::factory()->create([
                'envelope_id' => $envelope->envelope_id,
            ]);
        }

        $envelopeIds = $envelopes->pluck('envelope_id')->toArray();

        $response = $this->postJson(
            "/api/v2.1/accounts/{$this->account->account_id}/envelopes/bulk/download",
            [
                'envelope_ids' => $envelopeIds,
                'format' => 'combined',
            ]
        );

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'download_url',
                'batch_id',
                'total',
            ],
        ]);
    }
}
