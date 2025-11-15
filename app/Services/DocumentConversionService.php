<?php

namespace App\Services;

use App\Models\EnvelopeDocument;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

/**
 * Document Conversion Service
 *
 * Handles conversion of various document formats to PDF.
 * Supports multiple conversion backends:
 * - LibreOffice (unoconv)
 * - Cloud-based conversion (future)
 * - Mock converter (development/testing)
 */
class DocumentConversionService
{
    /**
     * Document storage service
     */
    protected DocumentStorageService $storageService;

    /**
     * Conversion backend
     */
    protected string $backend;

    /**
     * Supported source formats for conversion
     */
    protected array $convertibleFormats = [
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'text/plain' => 'txt',
        'text/html' => 'html',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
    ];

    /**
     * Initialize the service
     */
    public function __construct(DocumentStorageService $storageService)
    {
        $this->storageService = $storageService;
        $this->backend = config('documents.conversion_backend', 'mock');
    }

    /**
     * Check if a document needs conversion
     *
     * @param string $mimeType
     * @return bool
     */
    public function needsConversion(string $mimeType): bool
    {
        // PDF files don't need conversion
        if ($mimeType === 'application/pdf') {
            return false;
        }

        // Check if mime type is in our convertible list
        return isset($this->convertibleFormats[$mimeType]);
    }

    /**
     * Convert a document to PDF
     *
     * @param string $sourcePath Path to source document in storage
     * @param string $mimeType Source document MIME type
     * @return string Path to converted PDF
     * @throws \Exception
     */
    public function convertToPdf(string $sourcePath, string $mimeType): string
    {
        if (!$this->needsConversion($mimeType)) {
            // Already PDF or not convertible
            return $sourcePath;
        }

        // Get source file content
        $sourceContent = $this->storageService->getDocument($sourcePath);

        // Create temporary source file
        $tempDir = storage_path('app/temp');
        $sourceExt = $this->convertibleFormats[$mimeType] ?? 'bin';
        $sourceTempPath = $tempDir . '/' . Str::uuid() . '.' . $sourceExt;
        $pdfTempPath = $tempDir . '/' . Str::uuid() . '.pdf';

        try {
            // Write source to temp file
            file_put_contents($sourceTempPath, $sourceContent);

            // Convert based on backend
            switch ($this->backend) {
                case 'libreoffice':
                    $this->convertWithLibreOffice($sourceTempPath, $pdfTempPath);
                    break;

                case 'mock':
                    $this->convertWithMock($sourceTempPath, $pdfTempPath, $mimeType);
                    break;

                default:
                    throw new \Exception('Unknown conversion backend: ' . $this->backend);
            }

            // Generate PDF storage path
            $pdfStoragePath = str_replace(
                '.' . $sourceExt,
                '.pdf',
                $sourcePath
            );

            // Store converted PDF
            $pdfContent = file_get_contents($pdfTempPath);
            $disk = $this->storageService->getDisk();
            \Storage::disk($disk)->put($pdfStoragePath, $pdfContent);

            // Log conversion
            Log::info('Document converted to PDF', [
                'source_path' => $sourcePath,
                'pdf_path' => $pdfStoragePath,
                'mime_type' => $mimeType,
                'backend' => $this->backend,
            ]);

            return $pdfStoragePath;
        } catch (\Exception $e) {
            Log::error('Document conversion failed', [
                'source_path' => $sourcePath,
                'mime_type' => $mimeType,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } finally {
            // Clean up temporary files
            if (file_exists($sourceTempPath)) {
                unlink($sourceTempPath);
            }
            if (file_exists($pdfTempPath)) {
                unlink($pdfTempPath);
            }
        }
    }

    /**
     * Convert document using LibreOffice
     *
     * @param string $sourcePath
     * @param string $outputPath
     * @throws \Exception
     */
    protected function convertWithLibreOffice(string $sourcePath, string $outputPath): void
    {
        // Check if LibreOffice is available
        $result = Process::run('which soffice');

        if (!$result->successful()) {
            throw new \Exception('LibreOffice is not installed');
        }

        // Convert using LibreOffice headless mode
        $outputDir = dirname($outputPath);
        $result = Process::run([
            'soffice',
            '--headless',
            '--convert-to',
            'pdf',
            '--outdir',
            $outputDir,
            $sourcePath,
        ]);

        if (!$result->successful()) {
            throw new \Exception('LibreOffice conversion failed: ' . $result->errorOutput());
        }

        // LibreOffice outputs with the same name but .pdf extension
        $sourceBasename = pathinfo($sourcePath, PATHINFO_FILENAME);
        $generatedPdf = $outputDir . '/' . $sourceBasename . '.pdf';

        if (!file_exists($generatedPdf)) {
            throw new \Exception('Converted PDF not found');
        }

        // Move to desired output path
        rename($generatedPdf, $outputPath);
    }

    /**
     * Mock converter for development/testing
     * Creates a placeholder PDF with document info
     *
     * @param string $sourcePath
     * @param string $outputPath
     * @param string $mimeType
     */
    protected function convertWithMock(string $sourcePath, string $outputPath, string $mimeType): void
    {
        // Create a simple text-based "PDF" for testing
        $sourceBasename = basename($sourcePath);
        $sourceSize = filesize($sourcePath);

        $mockPdfContent = <<<PDF
%PDF-1.4
1 0 obj
<<
/Type /Catalog
/Pages 2 0 R
>>
endobj
2 0 obj
<<
/Type /Pages
/Kids [3 0 R]
/Count 1
>>
endobj
3 0 obj
<<
/Type /Page
/Parent 2 0 R
/Resources <<
/Font <<
/F1 <<
/Type /Font
/Subtype /Type1
/BaseFont /Helvetica
>>
>>
>>
/MediaBox [0 0 612 792]
/Contents 4 0 R
>>
endobj
4 0 obj
<<
/Length 200
>>
stream
BT
/F1 24 Tf
50 700 Td
(Mock Converted Document) Tj
0 -30 Td
/F1 12 Tf
(Original: {$sourceBasename}) Tj
0 -20 Td
(MIME Type: {$mimeType}) Tj
0 -20 Td
(Size: {$sourceSize} bytes) Tj
0 -20 Td
(Conversion: MOCK) Tj
ET
endstream
endobj
xref
0 5
0000000000 65535 f
0000000009 00000 n
0000000058 00000 n
0000000115 00000 n
0000000317 00000 n
trailer
<<
/Size 5
/Root 1 0 R
>>
startxref
568
%%EOF
PDF;

        file_put_contents($outputPath, $mockPdfContent);

        Log::warning('Using mock PDF converter (development mode)', [
            'source' => $sourceBasename,
            'mime_type' => $mimeType,
        ]);
    }

    /**
     * Queue a document for conversion
     * (To be used with Laravel Queue system)
     *
     * @param int $documentId
     * @param string $sourcePath
     * @param string $mimeType
     */
    public function queueConversion(int $documentId, string $sourcePath, string $mimeType): void
    {
        // This would dispatch a job to the queue
        // For now, we'll do synchronous conversion
        // In production, use: ConvertDocumentJob::dispatch($documentId, $sourcePath, $mimeType);

        Log::info('Document conversion queued', [
            'document_id' => $documentId,
            'source_path' => $sourcePath,
            'mime_type' => $mimeType,
        ]);

        // Synchronous conversion for now
        try {
            $pdfPath = $this->convertToPdf($sourcePath, $mimeType);

            // Update document record with PDF path
            $document = EnvelopeDocument::find($documentId);
            if ($document) {
                $document->pdf_path = $pdfPath;
                $document->conversion_status = 'completed';
                $document->save();
            }
        } catch (\Exception $e) {
            // Update document with error
            $document = EnvelopeDocument::find($documentId);
            if ($document) {
                $document->conversion_status = 'failed';
                $document->conversion_error = $e->getMessage();
                $document->save();
            }
        }
    }

    /**
     * Get supported file formats for conversion
     *
     * @return array
     */
    public function getSupportedFormats(): array
    {
        return $this->convertibleFormats;
    }

    /**
     * Check if conversion is available
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        if ($this->backend === 'mock') {
            return true;
        }

        if ($this->backend === 'libreoffice') {
            $result = Process::run('which soffice');
            return $result->successful();
        }

        return false;
    }

    /**
     * Get conversion backend
     *
     * @return string
     */
    public function getBackend(): string
    {
        return $this->backend;
    }

    /**
     * Set conversion backend
     *
     * @param string $backend
     */
    public function setBackend(string $backend): void
    {
        $this->backend = $backend;
    }
}
