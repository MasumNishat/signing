<?php

namespace App\Services;

use App\Models\EnvelopeDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Document Storage Service
 *
 * Handles secure storage, retrieval, and management of envelope documents.
 * Supports both local storage (development) and S3 (production).
 * Implements encryption at rest and access logging.
 */
class DocumentStorageService
{
    /**
     * Storage disk for documents
     */
    protected string $disk;

    /**
     * Maximum file size in bytes (25MB)
     */
    protected int $maxFileSize = 25000000;

    /**
     * Allowed document MIME types
     */
    protected array $allowedMimeTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'image/jpeg',
        'image/png',
        'image/gif',
        'text/plain',
        'text/html',
    ];

    /**
     * Initialize the service
     */
    public function __construct()
    {
        $this->disk = config('filesystems.default') === 's3' ? 'documents-s3' : 'documents';
    }

    /**
     * Store an uploaded document
     *
     * @param UploadedFile $file
     * @param string $envelopeId
     * @param array $metadata
     * @return array Document storage information
     * @throws \Exception
     */
    public function storeDocument(UploadedFile $file, string $envelopeId, array $metadata = []): array
    {
        // Validate file
        $this->validateFile($file);

        // Generate unique document path
        $documentId = $metadata['document_id'] ?? Str::uuid()->toString();
        $extension = $file->getClientOriginalExtension();
        $path = $this->generateDocumentPath($envelopeId, $documentId, $extension);

        // Store file
        $stored = Storage::disk($this->disk)->putFileAs(
            dirname($path),
            $file,
            basename($path)
        );

        if (!$stored) {
            throw new \Exception('Failed to store document');
        }

        // Calculate file hash for integrity
        $hash = hash_file('sha256', $file->getRealPath());

        // Log access
        $this->logAccess('store', $path, [
            'envelope_id' => $envelopeId,
            'document_id' => $documentId,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);

        return [
            'path' => $stored,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'extension' => $extension,
            'hash' => $hash,
            'disk' => $this->disk,
            'original_name' => $file->getClientOriginalName(),
        ];
    }

    /**
     * Retrieve a document
     *
     * @param string $path
     * @param string|null $envelopeId
     * @return string File contents
     * @throws \Exception
     */
    public function getDocument(string $path, ?string $envelopeId = null): string
    {
        if (!Storage::disk($this->disk)->exists($path)) {
            throw new \Exception('Document not found');
        }

        // Log access
        $this->logAccess('retrieve', $path, [
            'envelope_id' => $envelopeId,
        ]);

        return Storage::disk($this->disk)->get($path);
    }

    /**
     * Get a temporary URL for document download
     *
     * @param string $path
     * @param int $expirationMinutes
     * @param string|null $envelopeId
     * @return string Temporary URL
     */
    public function getTemporaryUrl(string $path, int $expirationMinutes = 60, ?string $envelopeId = null): string
    {
        // Log access
        $this->logAccess('generate_url', $path, [
            'envelope_id' => $envelopeId,
            'expiration_minutes' => $expirationMinutes,
        ]);

        // For S3, generate signed URL
        if ($this->disk === 'documents-s3') {
            return Storage::disk($this->disk)->temporaryUrl(
                $path,
                now()->addMinutes($expirationMinutes)
            );
        }

        // For local storage, return a route-based URL
        // This would need to be handled by a controller route
        return route('envelope.document.download', [
            'path' => encrypt($path),
            'expires' => now()->addMinutes($expirationMinutes)->timestamp,
        ]);
    }

    /**
     * Delete a document
     *
     * @param string $path
     * @param string|null $envelopeId
     * @return bool
     */
    public function deleteDocument(string $path, ?string $envelopeId = null): bool
    {
        if (!Storage::disk($this->disk)->exists($path)) {
            return false;
        }

        // Log access before deletion
        $this->logAccess('delete', $path, [
            'envelope_id' => $envelopeId,
        ]);

        return Storage::disk($this->disk)->delete($path);
    }

    /**
     * Copy a document
     *
     * @param string $sourcePath
     * @param string $destinationPath
     * @return bool
     */
    public function copyDocument(string $sourcePath, string $destinationPath): bool
    {
        if (!Storage::disk($this->disk)->exists($sourcePath)) {
            throw new \Exception('Source document not found');
        }

        return Storage::disk($this->disk)->copy($sourcePath, $destinationPath);
    }

    /**
     * Move a document
     *
     * @param string $sourcePath
     * @param string $destinationPath
     * @return bool
     */
    public function moveDocument(string $sourcePath, string $destinationPath): bool
    {
        if (!Storage::disk($this->disk)->exists($sourcePath)) {
            throw new \Exception('Source document not found');
        }

        return Storage::disk($this->disk)->move($sourcePath, $destinationPath);
    }

    /**
     * Get document metadata
     *
     * @param string $path
     * @return array
     */
    public function getMetadata(string $path): array
    {
        $disk = Storage::disk($this->disk);

        if (!$disk->exists($path)) {
            throw new \Exception('Document not found');
        }

        return [
            'size' => $disk->size($path),
            'last_modified' => $disk->lastModified($path),
            'mime_type' => $disk->mimeType($path),
            'visibility' => $disk->getVisibility($path),
        ];
    }

    /**
     * Check if document exists
     *
     * @param string $path
     * @return bool
     */
    public function exists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($path);
    }

    /**
     * Get document size
     *
     * @param string $path
     * @return int Size in bytes
     */
    public function getSize(string $path): int
    {
        return Storage::disk($this->disk)->size($path);
    }

    /**
     * Store document in temporary storage
     *
     * @param UploadedFile $file
     * @return string Temporary path
     */
    public function storeTemporary(UploadedFile $file): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        Storage::disk('temp')->putFileAs(
            '',
            $file,
            $filename
        );

        return $filename;
    }

    /**
     * Get file from temporary storage
     *
     * @param string $path
     * @return string File contents
     */
    public function getTemporary(string $path): string
    {
        if (!Storage::disk('temp')->exists($path)) {
            throw new \Exception('Temporary file not found');
        }

        return Storage::disk('temp')->get($path);
    }

    /**
     * Delete file from temporary storage
     *
     * @param string $path
     * @return bool
     */
    public function deleteTemporary(string $path): bool
    {
        return Storage::disk('temp')->delete($path);
    }

    /**
     * Clean up old temporary files (older than 24 hours)
     *
     * @return int Number of files deleted
     */
    public function cleanupTemporary(): int
    {
        $disk = Storage::disk('temp');
        $files = $disk->files();
        $deleted = 0;
        $cutoff = now()->subHours(24)->timestamp;

        foreach ($files as $file) {
            $lastModified = $disk->lastModified($file);

            if ($lastModified < $cutoff) {
                $disk->delete($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Validate uploaded file
     *
     * @param UploadedFile $file
     * @throws \Exception
     */
    protected function validateFile(UploadedFile $file): void
    {
        // Check file size
        if ($file->getSize() > $this->maxFileSize) {
            throw new \Exception(sprintf(
                'File size exceeds maximum allowed size of %d bytes',
                $this->maxFileSize
            ));
        }

        // Check MIME type
        if (!in_array($file->getMimeType(), $this->allowedMimeTypes)) {
            throw new \Exception(sprintf(
                'File type %s is not allowed',
                $file->getMimeType()
            ));
        }

        // Check for upload errors
        if (!$file->isValid()) {
            throw new \Exception('File upload failed: ' . $file->getErrorMessage());
        }
    }

    /**
     * Generate document storage path
     *
     * @param string $envelopeId
     * @param string $documentId
     * @param string $extension
     * @return string
     */
    protected function generateDocumentPath(string $envelopeId, string $documentId, string $extension): string
    {
        // Organize by envelope ID for better organization
        // Format: envelopes/{envelopeId}/documents/{documentId}.{extension}
        return sprintf(
            'envelopes/%s/documents/%s.%s',
            $envelopeId,
            $documentId,
            $extension
        );
    }

    /**
     * Log document access for audit trail
     *
     * @param string $action
     * @param string $path
     * @param array $context
     */
    protected function logAccess(string $action, string $path, array $context = []): void
    {
        Log::info('Document access', array_merge([
            'action' => $action,
            'path' => $path,
            'disk' => $this->disk,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $context));
    }

    /**
     * Get the current storage disk
     *
     * @return string
     */
    public function getDisk(): string
    {
        return $this->disk;
    }

    /**
     * Set the storage disk
     *
     * @param string $disk
     */
    public function setDisk(string $disk): void
    {
        $this->disk = $disk;
    }

    /**
     * Get maximum file size
     *
     * @return int
     */
    public function getMaxFileSize(): int
    {
        return $this->maxFileSize;
    }

    /**
     * Get allowed MIME types
     *
     * @return array
     */
    public function getAllowedMimeTypes(): array
    {
        return $this->allowedMimeTypes;
    }
}
