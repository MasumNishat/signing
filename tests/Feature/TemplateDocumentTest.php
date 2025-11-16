<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Template;
use App\Models\EnvelopeDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Template Documents Integration Tests
 *
 * Tests all 6 template document endpoints:
 * - GET /templates/{id}/documents
 * - POST /templates/{id}/documents
 * - PUT /templates/{id}/documents
 * - DELETE /templates/{id}/documents
 * - GET /templates/{id}/documents/{docId}
 * - PUT /templates/{id}/documents/{docId}
 */
class TemplateDocumentTest extends TestCase
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
            'description' => 'Test template for document tests',
            'created_by_user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user);
    }

    
    public function test_can_list_template_documents()
    {
        // Create test documents
        EnvelopeDocument::create([
            'template_id' => $this->template->id,
            'document_id' => 'doc-1',
            'name' => 'Document 1.pdf',
            'file_extension' => 'pdf',
            'order' => 1,
        ]);

        EnvelopeDocument::create([
            'template_id' => $this->template->id,
            'document_id' => 'doc-2',
            'name' => 'Document 2.docx',
            'file_extension' => 'docx',
            'order' => 2,
        ]);

        $response = $this->getJson("/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/documents");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'template_documents' => [
                        '*' => [
                            'document_id',
                            'name',
                            'file_extension',
                            'order',
                            'page_count',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                ],
                'message',
            ])
            ->assertJsonCount(2, 'data.template_documents');
    }

    
    public function test_can_add_documents_to_template()
    {
        $documentsData = [
            'documents' => [
                [
                    'name' => 'Contract.pdf',
                    'file_extension' => 'pdf',
                    'document_base64' => base64_encode('fake pdf content'),
                    'order' => 1,
                ],
                [
                    'name' => 'Appendix.docx',
                    'file_extension' => 'docx',
                    'remote_url' => 'https://example.com/appendix.docx',
                    'order' => 2,
                ],
            ],
        ];

        $response = $this->postJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/documents",
            $documentsData
        );

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'template_documents' => [
                        '*' => ['document_id', 'name', 'file_extension', 'order'],
                    ],
                ],
            ])
            ->assertJsonCount(2, 'data.template_documents');

        $this->assertDatabaseHas('envelope_documents', [
            'template_id' => $this->template->id,
            'name' => 'Contract.pdf',
            'file_extension' => 'pdf',
        ]);

        $this->assertDatabaseHas('envelope_documents', [
            'template_id' => $this->template->id,
            'name' => 'Appendix.docx',
            'remote_url' => 'https://example.com/appendix.docx',
        ]);
    }

    
    public function test_can_replace_all_template_documents()
    {
        // Create existing documents
        EnvelopeDocument::create([
            'template_id' => $this->template->id,
            'document_id' => 'old-doc-1',
            'name' => 'Old Document.pdf',
            'file_extension' => 'pdf',
        ]);

        $newDocuments = [
            'documents' => [
                [
                    'name' => 'New Document.pdf',
                    'file_extension' => 'pdf',
                    'order' => 1,
                ],
            ],
        ];

        $response = $this->putJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/documents",
            $newDocuments
        );

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.template_documents');

        // Old document should be deleted
        $this->assertDatabaseMissing('envelope_documents', [
            'document_id' => 'old-doc-1',
        ]);

        // New document should exist
        $this->assertDatabaseHas('envelope_documents', [
            'template_id' => $this->template->id,
            'name' => 'New Document.pdf',
        ]);
    }

    
    public function test_can_delete_all_template_documents()
    {
        // Create documents
        EnvelopeDocument::create([
            'template_id' => $this->template->id,
            'document_id' => 'doc-1',
            'name' => 'Document 1.pdf',
            'file_extension' => 'pdf',
        ]);

        EnvelopeDocument::create([
            'template_id' => $this->template->id,
            'document_id' => 'doc-2',
            'name' => 'Document 2.pdf',
            'file_extension' => 'pdf',
        ]);

        $response = $this->deleteJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/documents"
        );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['deleted_count' => 2],
            ]);

        $this->assertDatabaseMissing('envelope_documents', [
            'template_id' => $this->template->id,
        ]);
    }

    
    public function test_can_get_specific_template_document()
    {
        $document = EnvelopeDocument::create([
            'template_id' => $this->template->id,
            'document_id' => 'doc-123',
            'name' => 'Specific Document.pdf',
            'file_extension' => 'pdf',
            'order' => 1,
            'page_count' => 5,
        ]);

        $response = $this->getJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/documents/doc-123"
        );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'document_id' => 'doc-123',
                    'name' => 'Specific Document.pdf',
                    'file_extension' => 'pdf',
                    'order' => 1,
                    'page_count' => 5,
                ],
            ]);
    }

    
    public function test_can_update_specific_template_document()
    {
        $document = EnvelopeDocument::create([
            'template_id' => $this->template->id,
            'document_id' => 'doc-456',
            'name' => 'Original Name.pdf',
            'file_extension' => 'pdf',
            'order' => 1,
        ]);

        $updateData = [
            'name' => 'Updated Name.pdf',
            'order' => 2,
        ];

        $response = $this->putJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/documents/doc-456",
            $updateData
        );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'document_id' => 'doc-456',
                    'name' => 'Updated Name.pdf',
                    'order' => 2,
                ],
            ]);

        $this->assertDatabaseHas('envelope_documents', [
            'document_id' => 'doc-456',
            'name' => 'Updated Name.pdf',
            'order' => 2,
        ]);
    }

    
    public function returns_404_for_nonexistent_template()
    {
        $response = $this->getJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/nonexistent-template/documents"
        );

        $response->assertStatus(404);
    }

    
    public function returns_404_for_nonexistent_document()
    {
        $response = $this->getJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/documents/nonexistent-doc"
        );

        $response->assertStatus(404);
    }

    
    public function test_validates_required_fields_when_adding_documents()
    {
        $invalidData = [
            'documents' => [
                [
                    'name' => 'Missing file extension',
                    // Missing file_extension
                ],
            ],
        ];

        $response = $this->postJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/documents",
            $invalidData
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['documents.0.file_extension']);
    }

    
    public function test_auto_generates_document_id_if_not_provided()
    {
        $documentData = [
            'documents' => [
                [
                    'name' => 'Auto ID Document.pdf',
                    'file_extension' => 'pdf',
                ],
            ],
        ];

        $response = $this->postJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/documents",
            $documentData
        );

        $response->assertStatus(201);

        $document = EnvelopeDocument::where('template_id', $this->template->id)->first();
        $this->assertNotNull($document->document_id);
        $this->assertNotEmpty($document->document_id);
    }
}
