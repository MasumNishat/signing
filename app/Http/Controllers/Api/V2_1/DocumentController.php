<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Http\Controllers\Api\V2_1\BaseController;
use App\Models\Account;
use App\Models\Envelope;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Document Controller
 *
 * Handles all envelope document operations.
 * API Version: 2.1
 */
class DocumentController extends BaseController
{
    /**
     * Document service
     */
    protected DocumentService $documentService;

    /**
     * Initialize controller
     */
    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    /**
     * List all documents in an envelope
     *
     * GET /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents
     *
     * @param string $accountId
     * @param string $envelopeId
     * @param Request $request
     * @return JsonResponse
     */
    public function index(string $accountId, string $envelopeId, Request $request): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $documents = $this->documentService->listDocuments($envelope, [
            'include_in_download' => $request->query('include_in_download'),
            'sort_by' => $request->query('sort_by', 'order_number'),
            'sort_order' => $request->query('sort_order', 'asc'),
        ]);

        $formattedDocuments = $documents->map(function ($document) {
            return $this->documentService->getMetadata($document);
        });

        return $this->success([
            'envelope_id' => $envelope->envelope_id,
            'envelope_documents' => $formattedDocuments,
        ], 'Documents retrieved successfully');
    }

    /**
     * Add documents to an envelope
     *
     * POST /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents
     *
     * @param Request $request
     * @param string $accountId
     * @param string $envelopeId
     * @return JsonResponse
     */
    public function store(Request $request, string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        // Validate request
        $validator = Validator::make($request->all(), [
            'documents' => 'required|array|min:1|max:50',
            'documents.*.document_id' => 'nullable|string|max:100',
            'documents.*.name' => 'required|string|max:255',
            'documents.*.file' => 'nullable|file|max:25000', // 25MB
            'documents.*.document_base64' => 'nullable|string',
            'documents.*.order' => 'nullable|integer|min:1',
            'documents.*.display' => 'nullable|string|in:inline,modal',
            'documents.*.include_in_download' => 'nullable|boolean',
            'documents.*.signable' => 'nullable|boolean',
            'documents.*.transform_pdf_fields' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            // Prepare documents data
            $documentsData = [];
            foreach ($request->input('documents', []) as $index => $documentInput) {
                $documentData = $documentInput;

                // Add uploaded file if present
                if ($request->hasFile("documents.{$index}.file")) {
                    $documentData['file'] = $request->file("documents.{$index}.file");
                }

                $documentsData[] = $documentData;
            }

            $documents = $this->documentService->addDocuments($envelope, $documentsData);

            $formattedDocuments = array_map(function ($document) {
                return $this->documentService->getMetadata($document);
            }, $documents);

            return $this->created([
                'envelope_id' => $envelope->envelope_id,
                'envelope_documents' => $formattedDocuments,
            ], 'Documents added successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get a specific document
     *
     * GET /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}
     *
     * @param string $accountId
     * @param string $envelopeId
     * @param string $documentId
     * @param Request $request
     * @return JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function show(string $accountId, string $envelopeId, string $documentId, Request $request)
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $document = $this->documentService->getDocument($envelope, $documentId);

        // Check if download is requested
        if ($request->query('download') === 'true') {
            $format = $request->query('format', 'original'); // original or pdf

            try {
                $content = $this->documentService->downloadDocument($document, $format);
                $filename = $document->name;

                if ($format === 'pdf' && !str_ends_with($filename, '.pdf')) {
                    $filename = pathinfo($filename, PATHINFO_FILENAME) . '.pdf';
                }

                return response()->streamDownload(function () use ($content) {
                    echo $content;
                }, $filename, [
                    'Content-Type' => $format === 'pdf' ? 'application/pdf' : $document->mime_type,
                ]);
            } catch (\Exception $e) {
                return $this->error($e->getMessage(), 400);
            }
        }

        // Return document metadata
        return $this->success($this->documentService->getMetadata($document));
    }

    /**
     * Update a document
     *
     * PUT /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}
     *
     * @param Request $request
     * @param string $accountId
     * @param string $envelopeId
     * @param string $documentId
     * @return JsonResponse
     */
    public function update(Request $request, string $accountId, string $envelopeId, string $documentId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $document = $this->documentService->getDocument($envelope, $documentId);

        // Validate request
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'order' => 'nullable|integer|min:1',
            'display' => 'nullable|string|in:inline,modal',
            'include_in_download' => 'nullable|boolean',
            'signable' => 'nullable|boolean',
            'file' => 'nullable|file|max:25000', // 25MB
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $updateData = $request->only([
                'name',
                'order',
                'display',
                'include_in_download',
                'signable',
            ]);

            // Add file if uploaded
            if ($request->hasFile('file')) {
                $updateData['file'] = $request->file('file');
            }

            $updatedDocument = $this->documentService->updateDocument($document, $updateData);

            return $this->success(
                $this->documentService->getMetadata($updatedDocument),
                'Document updated successfully'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Delete a document
     *
     * DELETE /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}
     *
     * @param string $accountId
     * @param string $envelopeId
     * @param string $documentId
     * @return JsonResponse
     */
    public function destroy(string $accountId, string $envelopeId, string $documentId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $document = $this->documentService->getDocument($envelope, $documentId);

        try {
            $this->documentService->deleteDocument($document);

            return $this->noContent('Document deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get temporary download URL for a document
     *
     * POST /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/download_url
     *
     * @param Request $request
     * @param string $accountId
     * @param string $envelopeId
     * @param string $documentId
     * @return JsonResponse
     */
    public function getDownloadUrl(
        Request $request,
        string $accountId,
        string $envelopeId,
        string $documentId
    ): JsonResponse {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $document = $this->documentService->getDocument($envelope, $documentId);

        $validator = Validator::make($request->all(), [
            'format' => 'nullable|string|in:original,pdf',
            'expiration_minutes' => 'nullable|integer|min:1|max:1440', // Max 24 hours
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $format = $request->input('format', 'original');
            $expirationMinutes = $request->input('expiration_minutes', 60);

            $url = $this->documentService->getDownloadUrl($document, $format, $expirationMinutes);

            return $this->success([
                'url' => $url,
                'expires_in' => $expirationMinutes * 60, // Convert to seconds
                'format' => $format,
            ], 'Download URL generated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Reorder documents in an envelope
     *
     * PUT /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/reorder
     *
     * @param Request $request
     * @param string $accountId
     * @param string $envelopeId
     * @return JsonResponse
     */
    public function reorder(Request $request, string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'document_orders' => 'required|array',
            'document_orders.*' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $this->documentService->reorderDocuments(
                $envelope,
                $request->input('document_orders', [])
            );

            return $this->success(null, 'Documents reordered successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
