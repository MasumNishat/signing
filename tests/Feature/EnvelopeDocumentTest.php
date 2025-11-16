<?php

namespace Tests\Feature;

use App\Models\Envelope;
use App\Models\EnvelopeDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class EnvelopeDocumentTest extends ApiTestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_can_list_envelope_documents()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        EnvelopeDocument::create([
            'envelope_id' => $envelope->id,
            'name' => 'contract.pdf',
            'file_extension' => 'pdf',
            'order' => 1,
        ]);

        EnvelopeDocument::create([
            'envelope_id' => $envelope->id,
            'name' => 'attachment.docx',
            'file_extension' => 'docx',
            'order' => 2,
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/documents");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonCount(2, 'data.documents');
    }

    /** @test */
    public function test_can_add_documents_to_envelope()
    {
        Storage::fake('documents');
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        $file = UploadedFile::fake()->create('contract.pdf', 100);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/documents", [
            'documents' => [
                [
                    'document_id' => 'doc1',
                    'name' => 'Contract',
                    'file_extension' => 'pdf',
                    'order' => 1,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('envelope_documents', [
            'envelope_id' => $envelope->id,
            'name' => 'Contract',
        ]);
    }

    /** @test */
    public function test_can_get_specific_document()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        $document = EnvelopeDocument::create([
            'envelope_id' => $envelope->id,
            'name' => 'contract.pdf',
            'file_extension' => 'pdf',
            'order' => 1,
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/documents/{$document->document_id}");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonPath('data.name', 'contract.pdf');
    }

    /** @test */
    public function test_can_update_document()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        $document = EnvelopeDocument::create([
            'envelope_id' => $envelope->id,
            'name' => 'original.pdf',
            'file_extension' => 'pdf',
            'order' => 1,
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/documents/{$document->document_id}", [
            'name' => 'updated.pdf',
            'order' => 2,
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('envelope_documents', [
            'id' => $document->id,
            'name' => 'updated.pdf',
            'order' => 2,
        ]);
    }

    /** @test */
    public function test_can_delete_document()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        $document = EnvelopeDocument::create([
            'envelope_id' => $envelope->id,
            'name' => 'to_delete.pdf',
            'file_extension' => 'pdf',
            'order' => 1,
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/documents/{$document->document_id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('envelope_documents', [
            'id' => $document->id,
        ]);
    }

    /** @test */
    public function test_can_replace_all_documents()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        // Create existing documents
        EnvelopeDocument::create(['envelope_id' => $envelope->id, 'name' => 'old1.pdf', 'file_extension' => 'pdf', 'order' => 1]);
        EnvelopeDocument::create(['envelope_id' => $envelope->id, 'name' => 'old2.pdf', 'file_extension' => 'pdf', 'order' => 2]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/documents", [
            'documents' => [
                [
                    'document_id' => 'new1',
                    'name' => 'new.pdf',
                    'file_extension' => 'pdf',
                    'order' => 1,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        // Old documents should be deleted
        $this->assertDatabaseMissing('envelope_documents', ['name' => 'old1.pdf']);
        $this->assertDatabaseMissing('envelope_documents', ['name' => 'old2.pdf']);

        // New document should exist
        $this->assertDatabaseHas('envelope_documents', ['name' => 'new.pdf']);
    }

    /** @test */
    public function test_validates_document_order()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/documents", [
            'documents' => [
                [
                    'document_id' => 'doc1',
                    'name' => 'Contract',
                    'file_extension' => 'pdf',
                    'order' => -1, // Invalid
                ],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['documents.0.order']);
    }

    /** @test */
    public function test_auto_generates_document_id_if_not_provided()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/documents", [
            'documents' => [
                [
                    'name' => 'Contract',
                    'file_extension' => 'pdf',
                    'order' => 1,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $doc = EnvelopeDocument::where('envelope_id', $envelope->id)->first();
        $this->assertNotNull($doc->document_id);
    }

    /** @test */
    public function test_supports_multiple_file_types()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        $fileTypes = ['pdf', 'docx', 'xlsx', 'txt', 'png', 'jpg'];

        foreach ($fileTypes as $index => $type) {
            EnvelopeDocument::create([
                'envelope_id' => $envelope->id,
                'name' => "document.$type",
                'file_extension' => $type,
                'order' => $index + 1,
            ]);
        }

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/documents");

        $response->assertStatus(200);
        $response->assertJsonCount(count($fileTypes), 'data.documents');
    }

    /** @test */
    public function test_cannot_modify_documents_of_sent_envelope()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Sent Envelope',
            'status' => 'sent',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/documents", [
            'documents' => [
                [
                    'name' => 'Contract',
                    'file_extension' => 'pdf',
                    'order' => 1,
                ],
            ],
        ]);

        $response->assertStatus(400);
        $this->assertErrorResponse();
    }
}
