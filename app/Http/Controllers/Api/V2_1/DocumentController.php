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

    /**
     * Get all fields (tabs) for a document
     *
     * GET /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/fields
     *
     * @param Request $request
     * @param string $accountId
     * @param string $envelopeId
     * @param string $documentId
     * @return JsonResponse
     */
    public function getFields(
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

        $fields = $this->documentService->getDocumentFields($document, [
            'type' => $request->query('type'),
            'page_number' => $request->query('page_number'),
            'recipient_id' => $request->query('recipient_id'),
        ]);

        return $this->success([
            'document_id' => $document->document_id,
            'fields' => $fields->map(function ($tab) {
                return [
                    'tab_id' => $tab->tab_id,
                    'type' => $tab->type,
                    'tab_label' => $tab->tab_label,
                    'value' => $tab->value,
                    'required' => $tab->required,
                    'locked' => $tab->locked,
                    'page_number' => $tab->page_number,
                    'x_position' => $tab->x_position,
                    'y_position' => $tab->y_position,
                    'width' => $tab->width,
                    'height' => $tab->height,
                    'recipient_id' => $tab->recipient_id,
                ];
            }),
        ], 'Document fields retrieved successfully');
    }

    /**
     * Add fields (tabs) to a document
     *
     * POST /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/fields
     *
     * @param Request $request
     * @param string $accountId
     * @param string $envelopeId
     * @param string $documentId
     * @return JsonResponse
     */
    public function addFields(
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
            'fields' => 'required|array|min:1',
            'fields.*.tab_id' => 'nullable|string|max:100',
            'fields.*.type' => 'required|string|max:50',
            'fields.*.tab_label' => 'nullable|string|max:255',
            'fields.*.value' => 'nullable|string',
            'fields.*.required' => 'nullable|boolean',
            'fields.*.locked' => 'nullable|boolean',
            'fields.*.page_number' => 'required|integer|min:1',
            'fields.*.x_position' => 'required|integer|min:0',
            'fields.*.y_position' => 'required|integer|min:0',
            'fields.*.width' => 'nullable|integer|min:1',
            'fields.*.height' => 'nullable|integer|min:1',
            'fields.*.recipient_id' => 'nullable|exists:envelope_recipients,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $fields = $this->documentService->addDocumentFields(
                $document,
                $request->input('fields', [])
            );

            return $this->created([
                'document_id' => $document->document_id,
                'fields' => array_map(function ($tab) {
                    return [
                        'tab_id' => $tab->tab_id,
                        'type' => $tab->type,
                        'tab_label' => $tab->tab_label,
                        'page_number' => $tab->page_number,
                        'x_position' => $tab->x_position,
                        'y_position' => $tab->y_position,
                    ];
                }, $fields),
            ], 'Document fields added successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Update a document field (tab)
     *
     * PUT /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/fields/{tabId}
     *
     * @param Request $request
     * @param string $accountId
     * @param string $envelopeId
     * @param string $documentId
     * @param string $tabId
     * @return JsonResponse
     */
    public function updateField(
        Request $request,
        string $accountId,
        string $envelopeId,
        string $documentId,
        string $tabId
    ): JsonResponse {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $document = $this->documentService->getDocument($envelope, $documentId);

        $validator = Validator::make($request->all(), [
            'tab_label' => 'nullable|string|max:255',
            'value' => 'nullable|string',
            'required' => 'nullable|boolean',
            'locked' => 'nullable|boolean',
            'page_number' => 'nullable|integer|min:1',
            'x_position' => 'nullable|integer|min:0',
            'y_position' => 'nullable|integer|min:0',
            'width' => 'nullable|integer|min:1',
            'height' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $tab = $this->documentService->updateDocumentField(
                $document,
                $tabId,
                $request->all()
            );

            return $this->success([
                'tab_id' => $tab->tab_id,
                'type' => $tab->type,
                'tab_label' => $tab->tab_label,
                'value' => $tab->value,
                'required' => $tab->required,
                'locked' => $tab->locked,
                'page_number' => $tab->page_number,
                'x_position' => $tab->x_position,
                'y_position' => $tab->y_position,
                'width' => $tab->width,
                'height' => $tab->height,
            ], 'Document field updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Delete a document field (tab)
     *
     * DELETE /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/fields/{tabId}
     *
     * @param string $accountId
     * @param string $envelopeId
     * @param string $documentId
     * @param string $tabId
     * @return JsonResponse
     */
    public function deleteField(
        string $accountId,
        string $envelopeId,
        string $documentId,
        string $tabId
    ): JsonResponse {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $document = $this->documentService->getDocument($envelope, $documentId);

        try {
            $this->documentService->deleteDocumentField($document, $tabId);

            return $this->noContent('Document field deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get document pages information
     *
     * GET /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/pages
     *
     * @param string $accountId
     * @param string $envelopeId
     * @param string $documentId
     * @return JsonResponse
     */
    public function getPages(
        string $accountId,
        string $envelopeId,
        string $documentId
    ): JsonResponse {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $document = $this->documentService->getDocument($envelope, $documentId);

        $pages = $this->documentService->getDocumentPages($document);

        return $this->success([
            'document_id' => $document->document_id,
            'total_pages' => count($pages),
            'pages' => $pages,
        ], 'Document pages retrieved successfully');
    }

    /**
     * Delete specific pages from a document
     *
     * DELETE /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/pages
     *
     * @param Request $request
     * @param string $accountId
     * @param string $envelopeId
     * @param string $documentId
     * @return JsonResponse
     */
    public function deletePages(
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
            'page_numbers' => 'required|array|min:1',
            'page_numbers.*' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $this->documentService->deleteDocumentPages(
                $document,
                $request->input('page_numbers', [])
            );

            return $this->success(null, 'Document pages deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get combined PDF of all documents
     *
     * GET /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/combined
     *
     * @param Request $request
     * @param string $accountId
     * @param string $envelopeId
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|JsonResponse
     */
    public function getCombined(
        Request $request,
        string $accountId,
        string $envelopeId
    ) {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        try {
            $content = $this->documentService->getCombinedDocuments($envelope);

            $filename = 'envelope_' . $envelope->envelope_id . '_combined.pdf';

            return response()->streamDownload(function () use ($content) {
                echo $content;
            }, $filename, [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get certificate of completion
     *
     * GET /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/certificate
     *
     * @param string $accountId
     * @param string $envelopeId
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|JsonResponse
     */
    public function getCertificate(
        string $accountId,
        string $envelopeId
    ) {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        try {
            $content = $this->documentService->getCertificateOfCompletion($envelope);

            $filename = 'envelope_' . $envelope->envelope_id . '_certificate.txt';

            return response()->streamDownload(function () use ($content) {
                echo $content;
            }, $filename, [
                'Content-Type' => 'text/plain',
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get HTML definition for a document
     *
     * GET /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/html_definitions
     *
     * @param string $accountId
     * @param string $envelopeId
     * @param string $documentId
     * @return JsonResponse
     */
    public function getHtmlDefinition(
        string $accountId,
        string $envelopeId,
        string $documentId
    ): JsonResponse {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $document = $this->documentService->getDocument($envelope, $documentId);

        return $this->success(
            $this->documentService->getHtmlDefinition($document),
            'HTML definition retrieved successfully'
        );
    }

    /**
     * Generate responsive HTML preview for a document
     *
     * POST /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/responsive_html_preview
     *
     * @param Request $request
     * @param string $accountId
     * @param string $envelopeId
     * @param string $documentId
     * @return JsonResponse
     */
    public function generateResponsiveHtmlPreview(
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

        $htmlDefinition = $request->input('html_definition', []);

        return $this->success(
            $this->documentService->generateResponsiveHtmlPreview($document, $htmlDefinition),
            'Responsive HTML preview generated successfully'
        );
    }

    /**
     * Bulk add documents to envelope
     *
     * PUT /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents
     *
     * @param Request $request
     * @param string $accountId
     * @param string $envelopeId
     * @return JsonResponse
     */
    public function bulkAdd(Request $request, string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        // Reuse the store method logic
        return $this->store($request, $accountId, $envelopeId);
    }

    /**
     * Bulk delete documents from envelope
     *
     * DELETE /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents
     *
     * @param Request $request
     * @param string $accountId
     * @param string $envelopeId
     * @return JsonResponse
     */
    public function bulkDestroy(Request $request, string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'document_ids' => 'required|array|min:1',
            'document_ids.*' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $deleted = 0;
            foreach ($request->input('document_ids') as $documentId) {
                $this->documentService->deleteDocument($envelope, $documentId);
                $deleted++;
            }

            return $this->success([
                'deleted_count' => $deleted,
            ], 'Documents deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Bulk update document fields
     *
     * PUT /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/fields
     *
     * @param Request $request
     * @param string $accountId
     * @param string $envelopeId
     * @param string $documentId
     * @return JsonResponse
     */
    public function bulkUpdateFields(Request $request, string $accountId, string $envelopeId, string $documentId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $document = $this->documentService->getDocument($envelope, $documentId);

        $validator = Validator::make($request->all(), [
            'fields' => 'required|array|min:1',
            'fields.*.field_id' => 'required|string',
            'fields.*.value' => 'sometimes|string',
            'fields.*.locked' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $updated = $this->documentService->bulkUpdateFields($document, $request->input('fields'));

            return $this->success($updated, 'Document fields updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Bulk delete document fields
     *
     * DELETE /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/fields
     *
     * @param Request $request
     * @param string $accountId
     * @param string $envelopeId
     * @param string $documentId
     * @return JsonResponse
     */
    public function bulkDestroyFields(Request $request, string $accountId, string $envelopeId, string $documentId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $document = $this->documentService->getDocument($envelope, $documentId);

        $validator = Validator::make($request->all(), [
            'field_ids' => 'required|array|min:1',
            'field_ids.*' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $deleted = $this->documentService->bulkDeleteFields($document, $request->input('field_ids'));

            return $this->success([
                'deleted_count' => $deleted,
            ], 'Document fields deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Delete specific page from document
     *
     * DELETE /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/pages/{pageNumber}
     *
     * @param string $accountId
     * @param string $envelopeId
     * @param string $documentId
     * @param int $pageNumber
     * @return JsonResponse
     */
    public function deletePage(string $accountId, string $envelopeId, string $documentId, int $pageNumber): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $document = $this->documentService->getDocument($envelope, $documentId);

        try {
            $this->documentService->deletePage($document, $pageNumber);

            return $this->success(null, 'Page deleted successfully', 204);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get page image
     *
     * GET /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/pages/{pageNumber}/page_image
     *
     * @param Request $request
     * @param string $accountId
     * @param string $envelopeId
     * @param string $documentId
     * @param int $pageNumber
     * @return mixed
     */
    public function getPageImage(Request $request, string $accountId, string $envelopeId, string $documentId, int $pageNumber)
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $document = $this->documentService->getDocument($envelope, $documentId);

        try {
            $imageData = $this->documentService->getPageImage($document, $pageNumber, $request->query());

            return response($imageData['content'], 200)
                ->header('Content-Type', $imageData['mime_type']);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Rotate page image
     *
     * PUT /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/pages/{pageNumber}/page_image
     *
     * @param Request $request
     * @param string $accountId
     * @param string $envelopeId
     * @param string $documentId
     * @param int $pageNumber
     * @return JsonResponse
     */
    public function rotatePageImage(Request $request, string $accountId, string $envelopeId, string $documentId, int $pageNumber): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $document = $this->documentService->getDocument($envelope, $documentId);

        $validator = Validator::make($request->all(), [
            'rotation' => 'required|in:90,180,270',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $this->documentService->rotatePageImage($document, $pageNumber, (int)$request->input('rotation'));

            return $this->success(null, 'Page image rotated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get tabs on specific page
     *
     * GET /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/pages/{pageNumber}/tabs
     *
     * @param string $accountId
     * @param string $envelopeId
     * @param string $documentId
     * @param int $pageNumber
     * @return JsonResponse
     */
    public function getPageTabs(string $accountId, string $envelopeId, string $documentId, int $pageNumber): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $document = $this->documentService->getDocument($envelope, $documentId);

        try {
            $tabs = $this->documentService->getPageTabs($document, $pageNumber);

            return $this->success($tabs, 'Page tabs retrieved successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get all tabs on document
     *
     * GET /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/tabs
     *
     * @param string $accountId
     * @param string $envelopeId
     * @param string $documentId
     * @return JsonResponse
     */
    public function getTabs(string $accountId, string $envelopeId, string $documentId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $document = $this->documentService->getDocument($envelope, $documentId);

        try {
            $tabs = $this->documentService->getDocumentTabs($document);

            return $this->success($tabs, 'Document tabs retrieved successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Add tabs to document
     *
     * POST /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/tabs
     *
     * @param Request $request
     * @param string $accountId
     * @param string $envelopeId
     * @param string $documentId
     * @return JsonResponse
     */
    public function addTabs(Request $request, string $accountId, string $envelopeId, string $documentId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $document = $this->documentService->getDocument($envelope, $documentId);

        try {
            $tabs = $this->documentService->addDocumentTabs($document, $request->all());

            return $this->success($tabs, 'Tabs added to document successfully', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Update tabs on document
     *
     * PUT /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/tabs
     *
     * @param Request $request
     * @param string $accountId
     * @param string $envelopeId
     * @param string $documentId
     * @return JsonResponse
     */
    public function updateTabs(Request $request, string $accountId, string $envelopeId, string $documentId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $document = $this->documentService->getDocument($envelope, $documentId);

        try {
            $tabs = $this->documentService->updateDocumentTabs($document, $request->all());

            return $this->success($tabs, 'Document tabs updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Delete tabs from document
     *
     * DELETE /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/tabs
     *
     * @param Request $request
     * @param string $accountId
     * @param string $envelopeId
     * @param string $documentId
     * @return JsonResponse
     */
    public function deleteTabs(Request $request, string $accountId, string $envelopeId, string $documentId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $document = $this->documentService->getDocument($envelope, $documentId);

        $validator = Validator::make($request->all(), [
            'tab_ids' => 'required|array|min:1',
            'tab_ids.*' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $deleted = $this->documentService->deleteDocumentTabs($document, $request->input('tab_ids'));

            return $this->success([
                'deleted_count' => $deleted,
            ], 'Document tabs deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get templates associated with document
     *
     * GET /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/templates
     *
     * @param string $accountId
     * @param string $envelopeId
     * @param string $documentId
     * @return JsonResponse
     */
    public function getTemplates(string $accountId, string $envelopeId, string $documentId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $document = $this->documentService->getDocument($envelope, $documentId);

        try {
            $templates = $this->documentService->getDocumentTemplates($document);

            return $this->success($templates, 'Document templates retrieved successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Add templates to document
     *
     * POST /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/templates
     *
     * @param Request $request
     * @param string $accountId
     * @param string $envelopeId
     * @param string $documentId
     * @return JsonResponse
     */
    public function addTemplates(Request $request, string $accountId, string $envelopeId, string $documentId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $document = $this->documentService->getDocument($envelope, $documentId);

        $validator = Validator::make($request->all(), [
            'templates' => 'required|array|min:1',
            'templates.*.template_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $templates = $this->documentService->addDocumentTemplates($document, $request->input('templates'));

            return $this->success($templates, 'Templates added to document successfully', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Delete template from document
     *
     * DELETE /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/templates/{templateId}
     *
     * @param string $accountId
     * @param string $envelopeId
     * @param string $documentId
     * @param string $templateId
     * @return JsonResponse
     */
    public function deleteTemplate(string $accountId, string $envelopeId, string $documentId, string $templateId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $document = $this->documentService->getDocument($envelope, $documentId);

        try {
            $this->documentService->deleteDocumentTemplate($document, $templateId);

            return $this->success(null, 'Template deleted from document successfully', 204);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
