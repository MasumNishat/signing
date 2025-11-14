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
}
