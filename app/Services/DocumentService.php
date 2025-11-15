<?php

namespace App\Services;

use App\Models\Envelope;
use App\Models\EnvelopeDocument;
use App\Exceptions\Custom\BusinessLogicException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Document Service
 *
 * Handles all business logic for envelope document operations.
 * Orchestrates storage, conversion, and database operations.
 */
class DocumentService
{
    /**
     * Document storage service
     */
    protected DocumentStorageService $storage;

    /**
     * Document conversion service
     */
    protected DocumentConversionService $conversion;

    /**
     * Initialize the service
     */
    public function __construct(
        DocumentStorageService $storage,
        DocumentConversionService $conversion
    ) {
        $this->storage = $storage;
        $this->conversion = $conversion;
    }

    /**
     * Add documents to an envelope
     *
     * @param Envelope $envelope
     * @param array $documents Array of document data
     * @return array Created documents
     * @throws BusinessLogicException
     */
    public function addDocuments(Envelope $envelope, array $documents): array
    {
        // Validate envelope is in draft status
        if (!$envelope->isDraft()) {
            throw new BusinessLogicException('Documents can only be added to draft envelopes');
        }

        $createdDocuments = [];

        DB::beginTransaction();

        try {
            foreach ($documents as $index => $documentData) {
                $document = $this->addDocument($envelope, $documentData, $index + 1);
                $createdDocuments[] = $document;
            }

            DB::commit();

            return $createdDocuments;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to add documents to envelope', [
                'envelope_id' => $envelope->envelope_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Add a single document to an envelope
     *
     * @param Envelope $envelope
     * @param array $documentData
     * @param int $orderNumber
     * @return EnvelopeDocument
     */
    protected function addDocument(Envelope $envelope, array $documentData, int $orderNumber): EnvelopeDocument
    {
        $file = $documentData['file'] ?? null;
        $documentId = $documentData['document_id'] ?? 'doc_' . Str::uuid()->toString();

        // Create document record
        $document = new EnvelopeDocument();
        $document->envelope_id = $envelope->id;
        $document->document_id = $documentId;
        $document->name = $documentData['name'] ?? ($file ? $file->getClientOriginalName() : 'Untitled');
        $document->order_number = $documentData['order'] ?? $orderNumber;
        $document->display = $documentData['display'] ?? 'inline';
        $document->include_in_download = $documentData['include_in_download'] ?? true;
        $document->signable = $documentData['signable'] ?? true;
        $document->transform_pdf_fields = $documentData['transform_pdf_fields'] ?? false;

        // Handle file upload
        if ($file instanceof UploadedFile) {
            $this->handleFileUpload($document, $file, $envelope->envelope_id);
        } elseif (isset($documentData['document_base64'])) {
            $this->handleBase64Upload($document, $documentData['document_base64'], $envelope->envelope_id);
        } else {
            throw new BusinessLogicException('Document must include either a file upload or base64 content');
        }

        $document->save();

        // Trigger conversion if needed
        if ($this->conversion->needsConversion($document->mime_type)) {
            $this->conversion->queueConversion(
                $document->id,
                $document->file_path,
                $document->mime_type
            );
        } else {
            // Already PDF, mark as completed
            $document->conversion_status = 'completed';
            $document->pdf_path = $document->file_path;
            $document->converted_at = now();
            $document->save();
        }

        return $document;
    }

    /**
     * Handle file upload
     *
     * @param EnvelopeDocument $document
     * @param UploadedFile $file
     * @param string $envelopeId
     */
    protected function handleFileUpload(EnvelopeDocument $document, UploadedFile $file, string $envelopeId): void
    {
        $storageInfo = $this->storage->storeDocument($file, $envelopeId, [
            'document_id' => $document->document_id,
        ]);

        $document->file_path = $storageInfo['path'];
        $document->file_size = $storageInfo['size'];
        $document->mime_type = $storageInfo['mime_type'];
        $document->file_extension = $storageInfo['extension'];
        $document->file_hash = $storageInfo['hash'];
    }

    /**
     * Handle base64 document upload
     *
     * @param EnvelopeDocument $document
     * @param string $base64Content
     * @param string $envelopeId
     */
    protected function handleBase64Upload(EnvelopeDocument $document, string $base64Content, string $envelopeId): void
    {
        // Decode base64
        $fileData = base64_decode($base64Content);

        if ($fileData === false) {
            throw new BusinessLogicException('Invalid base64 content');
        }

        // Create temporary file
        $tempPath = storage_path('app/temp/' . Str::uuid() . '.tmp');
        file_put_contents($tempPath, $fileData);

        try {
            // Create UploadedFile instance
            $file = new UploadedFile(
                $tempPath,
                $document->name,
                mime_content_type($tempPath),
                null,
                true
            );

            $this->handleFileUpload($document, $file, $envelopeId);
        } finally {
            // Clean up temp file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }

        // Store base64 for reference (if configured)
        if (config('documents.storage.store_base64', false)) {
            $document->document_base64 = $base64Content;
        }
    }

    /**
     * List documents for an envelope
     *
     * @param Envelope $envelope
     * @param array $options Filter and sort options
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function listDocuments(Envelope $envelope, array $options = [])
    {
        $query = $envelope->documents()->with('tabs');

        // Apply filters
        if (isset($options['include_in_download'])) {
            $query->where('include_in_download', $options['include_in_download']);
        }

        // Apply sorting
        $sortBy = $options['sort_by'] ?? 'order_number';
        $sortOrder = $options['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->get();
    }

    /**
     * Get a specific document
     *
     * @param Envelope $envelope
     * @param string $documentId
     * @return EnvelopeDocument
     * @throws BusinessLogicException
     */
    public function getDocument(Envelope $envelope, string $documentId): EnvelopeDocument
    {
        $document = $envelope->documents()
            ->where('document_id', $documentId)
            ->first();

        if (!$document) {
            throw new BusinessLogicException('Document not found');
        }

        return $document;
    }

    /**
     * Download document content
     *
     * @param EnvelopeDocument $document
     * @param string $format Format: 'original' or 'pdf'
     * @return string File content
     * @throws BusinessLogicException
     */
    public function downloadDocument(EnvelopeDocument $document, string $format = 'original'): string
    {
        if ($format === 'pdf') {
            // Return PDF version
            if (!$document->pdf_path) {
                throw new BusinessLogicException('PDF version not available');
            }

            if ($document->conversion_status !== 'completed') {
                throw new BusinessLogicException('Document conversion is still in progress');
            }

            return $this->storage->getDocument($document->pdf_path, $document->envelope->envelope_id);
        }

        // Return original file
        if (!$document->file_path) {
            throw new BusinessLogicException('Document file not found');
        }

        return $this->storage->getDocument($document->file_path, $document->envelope->envelope_id);
    }

    /**
     * Get temporary download URL for document
     *
     * @param EnvelopeDocument $document
     * @param string $format Format: 'original' or 'pdf'
     * @param int $expirationMinutes
     * @return string Temporary URL
     */
    public function getDownloadUrl(
        EnvelopeDocument $document,
        string $format = 'original',
        int $expirationMinutes = 60
    ): string {
        $path = ($format === 'pdf') ? $document->pdf_path : $document->file_path;

        if (!$path) {
            throw new BusinessLogicException("Document path not available for format: {$format}");
        }

        return $this->storage->getTemporaryUrl($path, $expirationMinutes, $document->envelope->envelope_id);
    }

    /**
     * Update a document
     *
     * @param EnvelopeDocument $document
     * @param array $data Update data
     * @return EnvelopeDocument
     * @throws BusinessLogicException
     */
    public function updateDocument(EnvelopeDocument $document, array $data): EnvelopeDocument
    {
        // Validate envelope is in draft status
        if (!$document->envelope->isDraft()) {
            throw new BusinessLogicException('Documents can only be updated in draft envelopes');
        }

        DB::beginTransaction();

        try {
            // Update basic fields
            if (isset($data['name'])) {
                $document->name = $data['name'];
            }

            if (isset($data['order'])) {
                $document->order_number = $data['order'];
            }

            if (isset($data['display'])) {
                $document->display = $data['display'];
            }

            if (isset($data['include_in_download'])) {
                $document->include_in_download = $data['include_in_download'];
            }

            if (isset($data['signable'])) {
                $document->signable = $data['signable'];
            }

            // Handle file replacement
            if (isset($data['file'])) {
                $file = $data['file'];

                if ($file instanceof UploadedFile) {
                    // Delete old file
                    if ($document->file_path) {
                        $this->storage->deleteDocument($document->file_path, $document->envelope->envelope_id);
                    }

                    // Upload new file
                    $this->handleFileUpload($document, $file, $document->envelope->envelope_id);

                    // Trigger conversion for new file
                    if ($this->conversion->needsConversion($document->mime_type)) {
                        $document->conversion_status = 'pending';
                        $document->pdf_path = null;
                        $document->converted_at = null;

                        $this->conversion->queueConversion(
                            $document->id,
                            $document->file_path,
                            $document->mime_type
                        );
                    }
                }
            }

            $document->save();

            DB::commit();

            return $document->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a document
     *
     * @param EnvelopeDocument $document
     * @return bool
     * @throws BusinessLogicException
     */
    public function deleteDocument(EnvelopeDocument $document): bool
    {
        // Validate envelope is in draft status
        if (!$document->envelope->isDraft()) {
            throw new BusinessLogicException('Documents can only be deleted from draft envelopes');
        }

        DB::beginTransaction();

        try {
            // Delete associated tabs
            $document->tabs()->delete();

            // Delete files from storage
            if ($document->file_path) {
                $this->storage->deleteDocument($document->file_path, $document->envelope->envelope_id);
            }

            if ($document->pdf_path && $document->pdf_path !== $document->file_path) {
                $this->storage->deleteDocument($document->pdf_path, $document->envelope->envelope_id);
            }

            // Delete document record
            $document->delete();

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete document', [
                'document_id' => $document->document_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get document metadata
     *
     * @param EnvelopeDocument $document
     * @return array
     */
    public function getMetadata(EnvelopeDocument $document): array
    {
        return [
            'document_id' => $document->document_id,
            'name' => $document->name,
            'order' => $document->order_number,
            'display' => $document->display,
            'file_extension' => $document->file_extension,
            'file_size' => $document->file_size,
            'mime_type' => $document->mime_type,
            'pages' => $document->pages,
            'include_in_download' => $document->include_in_download,
            'signable' => $document->signable,
            'conversion_status' => $document->conversion_status,
            'converted_at' => $document->converted_at?->toIso8601String(),
            'created_at' => $document->created_at->toIso8601String(),
            'updated_at' => $document->updated_at->toIso8601String(),
        ];
    }

    /**
     * Reorder documents in an envelope
     *
     * @param Envelope $envelope
     * @param array $documentOrders Array of ['document_id' => order_number]
     * @return bool
     */
    public function reorderDocuments(Envelope $envelope, array $documentOrders): bool
    {
        if (!$envelope->isDraft()) {
            throw new BusinessLogicException('Documents can only be reordered in draft envelopes');
        }

        DB::beginTransaction();

        try {
            foreach ($documentOrders as $documentId => $orderNumber) {
                $envelope->documents()
                    ->where('document_id', $documentId)
                    ->update(['order_number' => $orderNumber]);
            }

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get all fields (tabs) for a specific document
     *
     * @param EnvelopeDocument $document
     * @param array $options Filter options
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDocumentFields(EnvelopeDocument $document, array $options = [])
    {
        $query = $document->tabs();

        // Filter by field type
        if (isset($options['type'])) {
            $query->where('type', $options['type']);
        }

        // Filter by page
        if (isset($options['page_number'])) {
            $query->where('page_number', $options['page_number']);
        }

        // Filter by recipient
        if (isset($options['recipient_id'])) {
            $query->where('recipient_id', $options['recipient_id']);
        }

        return $query->get();
    }

    /**
     * Add fields (tabs) to a document
     *
     * @param EnvelopeDocument $document
     * @param array $fields Array of field data
     * @return array Created tabs
     * @throws BusinessLogicException
     */
    public function addDocumentFields(EnvelopeDocument $document, array $fields): array
    {
        if (!$document->envelope->isDraft()) {
            throw new BusinessLogicException('Fields can only be added to draft envelopes');
        }

        $createdTabs = [];

        DB::beginTransaction();

        try {
            foreach ($fields as $fieldData) {
                $tab = $document->tabs()->create([
                    'envelope_id' => $document->envelope_id,
                    'document_id' => $document->id,
                    'recipient_id' => $fieldData['recipient_id'] ?? null,
                    'tab_id' => $fieldData['tab_id'] ?? 'tab_' . Str::uuid()->toString(),
                    'type' => $fieldData['type'],
                    'tab_label' => $fieldData['tab_label'] ?? null,
                    'value' => $fieldData['value'] ?? null,
                    'required' => $fieldData['required'] ?? false,
                    'locked' => $fieldData['locked'] ?? false,
                    'page_number' => $fieldData['page_number'],
                    'x_position' => $fieldData['x_position'],
                    'y_position' => $fieldData['y_position'],
                    'width' => $fieldData['width'] ?? 100,
                    'height' => $fieldData['height'] ?? 20,
                ]);

                $createdTabs[] = $tab;
            }

            DB::commit();

            return $createdTabs;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to add document fields', [
                'document_id' => $document->document_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update a document field (tab)
     *
     * @param EnvelopeDocument $document
     * @param string $tabId
     * @param array $data Update data
     * @return \App\Models\EnvelopeTab
     * @throws BusinessLogicException
     */
    public function updateDocumentField(EnvelopeDocument $document, string $tabId, array $data)
    {
        if (!$document->envelope->isDraft()) {
            throw new BusinessLogicException('Fields can only be updated in draft envelopes');
        }

        $tab = $document->tabs()->where('tab_id', $tabId)->first();

        if (!$tab) {
            throw new BusinessLogicException('Field not found');
        }

        DB::beginTransaction();

        try {
            $tab->update(array_filter([
                'tab_label' => $data['tab_label'] ?? null,
                'value' => $data['value'] ?? null,
                'required' => $data['required'] ?? null,
                'locked' => $data['locked'] ?? null,
                'page_number' => $data['page_number'] ?? null,
                'x_position' => $data['x_position'] ?? null,
                'y_position' => $data['y_position'] ?? null,
                'width' => $data['width'] ?? null,
                'height' => $data['height'] ?? null,
            ], function ($value) {
                return $value !== null;
            }));

            DB::commit();

            return $tab->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a document field (tab)
     *
     * @param EnvelopeDocument $document
     * @param string $tabId
     * @return bool
     * @throws BusinessLogicException
     */
    public function deleteDocumentField(EnvelopeDocument $document, string $tabId): bool
    {
        if (!$document->envelope->isDraft()) {
            throw new BusinessLogicException('Fields can only be deleted from draft envelopes');
        }

        $tab = $document->tabs()->where('tab_id', $tabId)->first();

        if (!$tab) {
            throw new BusinessLogicException('Field not found');
        }

        DB::beginTransaction();

        try {
            $tab->delete();
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get document pages information
     *
     * @param EnvelopeDocument $document
     * @return array
     */
    public function getDocumentPages(EnvelopeDocument $document): array
    {
        // For now, return basic page information
        // In a real implementation, this would extract actual page data from the PDF
        $pages = [];

        for ($i = 1; $i <= ($document->pages ?? 1); $i++) {
            $pages[] = [
                'page_number' => $i,
                'width' => 612, // Standard letter size in points (8.5" * 72)
                'height' => 792, // Standard letter size in points (11" * 72)
                'dpi' => 72,
            ];
        }

        return $pages;
    }

    /**
     * Delete specific pages from a document
     *
     * @param EnvelopeDocument $document
     * @param array $pageNumbers Pages to delete
     * @return EnvelopeDocument
     * @throws BusinessLogicException
     */
    public function deleteDocumentPages(EnvelopeDocument $document, array $pageNumbers): EnvelopeDocument
    {
        if (!$document->envelope->isDraft()) {
            throw new BusinessLogicException('Pages can only be deleted from draft envelopes');
        }

        // This is a placeholder implementation
        // In production, this would:
        // 1. Load the PDF
        // 2. Remove specified pages
        // 3. Save the modified PDF
        // 4. Update the document record
        // 5. Update tabs that reference deleted pages

        throw new BusinessLogicException('Page deletion is not yet implemented');
    }

    /**
     * Get combined PDF of all documents in an envelope
     *
     * @param Envelope $envelope
     * @param array $options Combination options
     * @return string PDF content
     * @throws BusinessLogicException
     */
    public function getCombinedDocuments(Envelope $envelope, array $options = []): string
    {
        $documents = $envelope->documents()
            ->where('include_in_download', true)
            ->orderBy('order_number')
            ->get();

        if ($documents->isEmpty()) {
            throw new BusinessLogicException('No documents to combine');
        }

        // Check if all documents are converted
        foreach ($documents as $document) {
            if ($document->conversion_status !== 'completed') {
                throw new BusinessLogicException('Not all documents have been converted to PDF');
            }
        }

        // This is a placeholder implementation
        // In production, this would use a PDF library to:
        // 1. Load each PDF
        // 2. Combine them in order
        // 3. Add certificate of completion if requested
        // 4. Return the combined PDF content

        // For now, return the first document's PDF
        $firstDocument = $documents->first();
        return $this->storage->getDocument($firstDocument->pdf_path, $envelope->envelope_id);
    }

    /**
     * Get certificate of completion for an envelope
     *
     * @param Envelope $envelope
     * @return string PDF content
     * @throws BusinessLogicException
     */
    public function getCertificateOfCompletion(Envelope $envelope): string
    {
        if ($envelope->status !== 'completed') {
            throw new BusinessLogicException('Certificate is only available for completed envelopes');
        }

        // This is a placeholder implementation
        // In production, this would generate a PDF certificate containing:
        // 1. Envelope information
        // 2. List of signers and signatures
        // 3. Timestamps of all actions
        // 4. Audit trail summary

        // For now, generate a simple text-based certificate
        $certificate = "CERTIFICATE OF COMPLETION\n\n";
        $certificate .= "Envelope ID: {$envelope->envelope_id}\n";
        $certificate .= "Subject: {$envelope->email_subject}\n";
        $certificate .= "Status: {$envelope->status}\n";
        $certificate .= "Completed: {$envelope->completed_at}\n\n";
        $certificate .= "This envelope was completed on {$envelope->completed_at}.\n";

        return $certificate;
    }

    /**
     * Get HTML definition for a document
     *
     * @param EnvelopeDocument $document
     * @return array
     */
    public function getHtmlDefinition(EnvelopeDocument $document): array
    {
        // Placeholder implementation
        // In production, this would return the actual HTML definition used to generate
        // responsive HTML for the document

        return [
            'document_id' => $document->document_id,
            'html_definition' => [
                'source' => 'document',
                'display_anchor_prefix' => '/sn',
                'display_anchors' => [],
                'display_metadata' => [],
                'display_page_number' => 1,
                'display_settings' => [
                    'display_label' => $document->name,
                    'display' => 'inline',
                    'inline_outer_style' => '',
                    'pre_label' => '',
                    'scroll_to_top_button' => false,
                    'table_style' => '',
                ],
                'remove_empty_tags' => 'true',
            ],
            'message' => 'HTML definition placeholder - full implementation requires responsive HTML processing',
        ];
    }

    /**
     * Generate responsive HTML preview for a document
     *
     * @param EnvelopeDocument $document
     * @param array $htmlDefinition
     * @return array
     */
    public function generateResponsiveHtmlPreview(EnvelopeDocument $document, array $htmlDefinition = []): array
    {
        // Placeholder implementation
        // In production, this would:
        // 1. Convert PDF to responsive HTML
        // 2. Apply display settings from htmlDefinition
        // 3. Generate preview URLs for different devices
        // 4. Return HTML content and metadata

        return [
            'document_id' => $document->document_id,
            'document_name' => $document->name,
            'html_preview' => [
                'html_content' => '<html><body><h1>Responsive HTML Preview</h1><p>Document: ' . $document->name . '</p></body></html>',
                'preview_url' => route('documents.show', [
                    'accountId' => $document->envelope->account->account_id,
                    'envelopeId' => $document->envelope->envelope_id,
                    'documentId' => $document->document_id,
                ]),
            ],
            'message' => 'Responsive HTML preview placeholder - full implementation requires HTML conversion library',
        ];
    }

    /**
     * Get HTML definitions for all envelope documents
     *
     * @param Envelope $envelope
     * @return array
     */
    public function getEnvelopeHtmlDefinitions(Envelope $envelope): array
    {
        $documents = $envelope->documents()->get();
        $htmlDefinitions = [];

        foreach ($documents as $document) {
            $htmlDefinitions[] = $this->getHtmlDefinition($document);
        }

        return [
            'envelope_id' => $envelope->envelope_id,
            'total_documents' => $documents->count(),
            'html_definitions' => $htmlDefinitions,
            'message' => 'HTML definitions placeholder - full implementation requires responsive HTML processing',
        ];
    }

    /**
     * Generate responsive HTML preview for all envelope documents
     *
     * @param Envelope $envelope
     * @param array $htmlDefinition
     * @return array
     */
    public function generateEnvelopeResponsiveHtmlPreview(Envelope $envelope, array $htmlDefinition = []): array
    {
        $documents = $envelope->documents()->get();
        $previews = [];

        foreach ($documents as $document) {
            $previews[] = $this->generateResponsiveHtmlPreview($document, $htmlDefinition);
        }

        return [
            'envelope_id' => $envelope->envelope_id,
            'total_documents' => $documents->count(),
            'previews' => $previews,
            'message' => 'Responsive HTML preview placeholder - full implementation requires HTML conversion library',
        ];
    }
}
