<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Models\Template;
use App\Models\EnvelopeDocument;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * TemplateDocumentController
 *
 * Handles template document operations.
 * Templates reuse envelope_documents table with template_id column.
 */
class TemplateDocumentController extends BaseController
{
    protected DocumentService $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    /**
     * GET /accounts/{accountId}/templates/{templateId}/documents
     *
     * Get all documents for a template
     */
    public function index(string $accountId, string $templateId): JsonResponse
    {
        try {
            $template = Template::where('account_id', $accountId)
                ->where('template_id', $templateId)
                ->with('documents')
                ->firstOrFail();

            return $this->successResponse([
                'template_documents' => $template->documents->map(function ($doc) {
                    return $this->formatDocument($doc);
                }),
            ], 'Template documents retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /accounts/{accountId}/templates/{templateId}/documents
     *
     * Add documents to a template
     */
    public function store(Request $request, string $accountId, string $templateId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'documents' => 'required|array|min:1',
                'documents.*.document_id' => 'sometimes|string|max:100',
                'documents.*.name' => 'required|string|max:255',
                'documents.*.file_extension' => 'required|string|max:10',
                'documents.*.document_base64' => 'sometimes|string',
                'documents.*.remote_url' => 'sometimes|url',
                'documents.*.order' => 'sometimes|integer|min:1',
            ]);

            $template = Template::where('account_id', $accountId)
                ->where('template_id', $templateId)
                ->firstOrFail();

            // Create documents associated with template
            $documents = [];
            foreach ($validated['documents'] as $docData) {
                $document = EnvelopeDocument::create([
                    'template_id' => $template->id,
                    'document_id' => $docData['document_id'] ?? null,
                    'name' => $docData['name'],
                    'file_extension' => $docData['file_extension'],
                    'document_base64' => $docData['document_base64'] ?? null,
                    'remote_url' => $docData['remote_url'] ?? null,
                    'order' => $docData['order'] ?? (count($documents) + 1),
                ]);
                $documents[] = $document;
            }

            return $this->createdResponse([
                'template_documents' => collect($documents)->map(function ($doc) {
                    return $this->formatDocument($doc);
                }),
            ], 'Template documents created successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * PUT /accounts/{accountId}/templates/{templateId}/documents
     *
     * Update all documents for a template (replace)
     */
    public function update(Request $request, string $accountId, string $templateId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'documents' => 'required|array',
                'documents.*.document_id' => 'sometimes|string|max:100',
                'documents.*.name' => 'required|string|max:255',
                'documents.*.file_extension' => 'required|string|max:10',
                'documents.*.document_base64' => 'sometimes|string',
                'documents.*.remote_url' => 'sometimes|url',
                'documents.*.order' => 'sometimes|integer|min:1',
            ]);

            $template = Template::where('account_id', $accountId)
                ->where('template_id', $templateId)
                ->firstOrFail();

            // Delete existing documents
            EnvelopeDocument::where('template_id', $template->id)->delete();

            // Create new documents
            $documents = [];
            foreach ($validated['documents'] as $docData) {
                $document = EnvelopeDocument::create([
                    'template_id' => $template->id,
                    'document_id' => $docData['document_id'] ?? null,
                    'name' => $docData['name'],
                    'file_extension' => $docData['file_extension'],
                    'document_base64' => $docData['document_base64'] ?? null,
                    'remote_url' => $docData['remote_url'] ?? null,
                    'order' => $docData['order'] ?? (count($documents) + 1),
                ]);
                $documents[] = $document;
            }

            return $this->successResponse([
                'template_documents' => collect($documents)->map(function ($doc) {
                    return $this->formatDocument($doc);
                }),
            ], 'Template documents updated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * DELETE /accounts/{accountId}/templates/{templateId}/documents
     *
     * Delete all documents from a template
     */
    public function destroy(string $accountId, string $templateId): JsonResponse
    {
        try {
            $template = Template::where('account_id', $accountId)
                ->where('template_id', $templateId)
                ->firstOrFail();

            $count = EnvelopeDocument::where('template_id', $template->id)->delete();

            return $this->successResponse([
                'deleted_count' => $count,
            ], 'Template documents deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /accounts/{accountId}/templates/{templateId}/documents/{documentId}
     *
     * Get a specific template document
     */
    public function show(string $accountId, string $templateId, string $documentId): JsonResponse
    {
        try {
            $template = Template::where('account_id', $accountId)
                ->where('template_id', $templateId)
                ->firstOrFail();

            $document = EnvelopeDocument::where('template_id', $template->id)
                ->where('document_id', $documentId)
                ->firstOrFail();

            return $this->successResponse(
                $this->formatDocument($document),
                'Template document retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * PUT /accounts/{accountId}/templates/{templateId}/documents/{documentId}
     *
     * Update a specific template document
     */
    public function updateSingle(Request $request, string $accountId, string $templateId, string $documentId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'file_extension' => 'sometimes|string|max:10',
                'document_base64' => 'sometimes|string',
                'remote_url' => 'sometimes|url',
                'order' => 'sometimes|integer|min:1',
            ]);

            $template = Template::where('account_id', $accountId)
                ->where('template_id', $templateId)
                ->firstOrFail();

            $document = EnvelopeDocument::where('template_id', $template->id)
                ->where('document_id', $documentId)
                ->firstOrFail();

            $document->update($validated);

            return $this->successResponse(
                $this->formatDocument($document->fresh()),
                'Template document updated successfully'
            );
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Format document for response
     */
    protected function formatDocument(EnvelopeDocument $document): array
    {
        return [
            'document_id' => $document->document_id,
            'name' => $document->name,
            'file_extension' => $document->file_extension,
            'order' => $document->order,
            'page_count' => $document->page_count,
            'created_at' => $document->created_at?->toIso8601String(),
            'updated_at' => $document->updated_at?->toIso8601String(),
        ];
    }
}
