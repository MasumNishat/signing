<?php

namespace App\Services;

use App\Models\Account;
use App\Models\ChunkedUpload;
use App\Exceptions\Custom\BusinessLogicException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Chunked Upload Service
 *
 * Handles large file uploads by breaking them into chunks.
 * Supports resumable uploads and integrity verification.
 */
class ChunkedUploadService
{
    /**
     * Default chunk size (5MB)
     */
    protected const DEFAULT_CHUNK_SIZE = 5 * 1024 * 1024;

    /**
     * Maximum chunk size (25MB)
     */
    protected const MAX_CHUNK_SIZE = 25 * 1024 * 1024;

    /**
     * Default expiration time (48 hours)
     */
    protected const DEFAULT_EXPIRATION_HOURS = 48;

    /**
     * Initiate a new chunked upload
     *
     * @param Account $account
     * @param UploadedFile $firstChunk
     * @param array $options
     * @return ChunkedUpload
     */
    public function initiateUpload(Account $account, UploadedFile $firstChunk, array $options = []): ChunkedUpload
    {
        DB::beginTransaction();

        try {
            // Generate unique upload ID
            $uploadId = 'chu_' . Str::uuid()->toString();

            // Create chunked upload record
            $upload = ChunkedUpload::create([
                'account_id' => $account->id,
                'chunked_upload_id' => $uploadId,
                'chunked_upload_uri' => null, // Will be set after first chunk
                'committed' => false,
                'expires_date_time' => now()->addHours(
                    $options['expiration_hours'] ?? self::DEFAULT_EXPIRATION_HOURS
                ),
                'max_chunk_size' => $options['chunk_size'] ?? self::DEFAULT_CHUNK_SIZE,
                'max_chunks' => $options['max_chunks'] ?? 1000,
                'total_parts' => 0,
            ]);

            // Store first chunk
            $this->storeChunk($upload, 0, $firstChunk);

            // Update total parts
            $upload->total_parts = 1;
            $upload->save();

            DB::commit();

            Log::info('Chunked upload initiated', [
                'upload_id' => $upload->chunked_upload_id,
                'account_id' => $account->account_id,
            ]);

            return $upload;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to initiate chunked upload', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Add a chunk to an existing upload
     *
     * @param ChunkedUpload $upload
     * @param int $partSeq Part sequence number
     * @param UploadedFile $chunk
     * @return ChunkedUpload
     */
    public function addChunk(ChunkedUpload $upload, int $partSeq, UploadedFile $chunk): ChunkedUpload
    {
        // Validate upload state
        $this->validateUploadState($upload);

        // Validate chunk size
        if ($chunk->getSize() > $upload->max_chunk_size) {
            throw new BusinessLogicException(
                "Chunk size exceeds maximum allowed size of {$upload->max_chunk_size} bytes"
            );
        }

        // Validate sequence number
        if ($partSeq < 0 || $partSeq >= $upload->max_chunks) {
            throw new BusinessLogicException(
                "Invalid part sequence number. Must be between 0 and {$upload->max_chunks}"
            );
        }

        DB::beginTransaction();

        try {
            // Store chunk
            $this->storeChunk($upload, $partSeq, $chunk);

            // Update total parts if this is a new part
            if ($partSeq >= $upload->total_parts) {
                $upload->total_parts = $partSeq + 1;
                $upload->save();
            }

            DB::commit();

            Log::info('Chunk added to upload', [
                'upload_id' => $upload->chunked_upload_id,
                'part_seq' => $partSeq,
                'size' => $chunk->getSize(),
            ]);

            return $upload->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to add chunk', [
                'upload_id' => $upload->chunked_upload_id,
                'part_seq' => $partSeq,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Commit a chunked upload (integrity check and finalize)
     *
     * @param ChunkedUpload $upload
     * @return ChunkedUpload
     */
    public function commitUpload(ChunkedUpload $upload): ChunkedUpload
    {
        // Validate upload state
        $this->validateUploadState($upload);

        if ($upload->isCommitted()) {
            throw new BusinessLogicException('Upload is already committed');
        }

        DB::beginTransaction();

        try {
            // Verify all parts are present
            $missingParts = $this->findMissingParts($upload);
            if (!empty($missingParts)) {
                throw new BusinessLogicException(
                    'Cannot commit upload: missing parts ' . implode(', ', $missingParts)
                );
            }

            // Combine all chunks into final file
            $finalPath = $this->combineChunks($upload);

            // Update upload record
            $upload->chunked_upload_uri = $finalPath;
            $upload->markAsCommitted();

            DB::commit();

            Log::info('Chunked upload committed', [
                'upload_id' => $upload->chunked_upload_id,
                'final_path' => $finalPath,
                'total_parts' => $upload->total_parts,
            ]);

            return $upload->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to commit chunked upload', [
                'upload_id' => $upload->chunked_upload_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete a chunked upload
     *
     * @param ChunkedUpload $upload
     * @return bool
     */
    public function deleteUpload(ChunkedUpload $upload): bool
    {
        if ($upload->isCommitted()) {
            throw new BusinessLogicException('Cannot delete committed upload');
        }

        DB::beginTransaction();

        try {
            // Delete all chunks from storage
            $this->deleteAllChunks($upload);

            // Delete final file if it exists
            if ($upload->chunked_upload_uri) {
                Storage::disk('temp')->delete($upload->chunked_upload_uri);
            }

            // Delete database record
            $upload->delete();

            DB::commit();

            Log::info('Chunked upload deleted', [
                'upload_id' => $upload->chunked_upload_id,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete chunked upload', [
                'upload_id' => $upload->chunked_upload_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get metadata for a chunked upload
     *
     * @param ChunkedUpload $upload
     * @return array
     */
    public function getMetadata(ChunkedUpload $upload): array
    {
        return [
            'chunked_upload_id' => $upload->chunked_upload_id,
            'chunked_upload_uri' => $upload->chunked_upload_uri,
            'committed' => $upload->committed,
            'expires_date_time' => $upload->expires_date_time?->toIso8601String(),
            'max_chunk_size' => $upload->max_chunk_size,
            'max_chunks' => $upload->max_chunks,
            'total_parts' => $upload->total_parts,
            'created_at' => $upload->created_at->toIso8601String(),
        ];
    }

    /**
     * Store a chunk to temporary storage
     *
     * @param ChunkedUpload $upload
     * @param int $partSeq
     * @param UploadedFile $chunk
     */
    protected function storeChunk(ChunkedUpload $upload, int $partSeq, UploadedFile $chunk): void
    {
        $directory = $upload->getPartsDirectory();
        $filename = "part_{$partSeq}.chunk";
        $path = "{$directory}/{$filename}";

        // Store chunk
        Storage::disk('temp')->putFileAs(
            $directory,
            $chunk,
            $filename
        );
    }

    /**
     * Combine all chunks into a single file
     *
     * @param ChunkedUpload $upload
     * @return string Final file path
     */
    protected function combineChunks(ChunkedUpload $upload): string
    {
        $directory = $upload->getPartsDirectory();
        $finalFilename = "upload_{$upload->chunked_upload_id}";
        $finalPath = "{$directory}/{$finalFilename}";

        $disk = Storage::disk('temp');

        // Create final file
        $finalHandle = fopen($disk->path($finalPath), 'wb');

        try {
            // Append each chunk in order
            for ($i = 0; $i < $upload->total_parts; $i++) {
                $chunkPath = "{$directory}/part_{$i}.chunk";

                if (!$disk->exists($chunkPath)) {
                    throw new BusinessLogicException("Missing chunk part {$i}");
                }

                $chunkContent = $disk->get($chunkPath);
                fwrite($finalHandle, $chunkContent);

                // Delete chunk after combining
                $disk->delete($chunkPath);
            }

            return $finalPath;
        } finally {
            fclose($finalHandle);
        }
    }

    /**
     * Find missing parts in upload
     *
     * @param ChunkedUpload $upload
     * @return array Missing part numbers
     */
    protected function findMissingParts(ChunkedUpload $upload): array
    {
        $missing = [];
        $disk = Storage::disk('temp');
        $directory = $upload->getPartsDirectory();

        for ($i = 0; $i < $upload->total_parts; $i++) {
            $chunkPath = "{$directory}/part_{$i}.chunk";
            if (!$disk->exists($chunkPath)) {
                $missing[] = $i;
            }
        }

        return $missing;
    }

    /**
     * Delete all chunks for an upload
     *
     * @param ChunkedUpload $upload
     */
    protected function deleteAllChunks(ChunkedUpload $upload): void
    {
        $directory = $upload->getPartsDirectory();
        Storage::disk('temp')->deleteDirectory($directory);
    }

    /**
     * Validate upload state
     *
     * @param ChunkedUpload $upload
     * @throws BusinessLogicException
     */
    protected function validateUploadState(ChunkedUpload $upload): void
    {
        if ($upload->hasExpired()) {
            throw new BusinessLogicException('Chunked upload has expired');
        }

        if ($upload->isCommitted()) {
            throw new BusinessLogicException('Cannot modify committed upload');
        }
    }

    /**
     * Clean up expired uploads
     *
     * @return int Number of uploads cleaned up
     */
    public function cleanupExpiredUploads(): int
    {
        $expiredUploads = ChunkedUpload::where('expires_date_time', '<', now())
            ->where('committed', false)
            ->get();

        $count = 0;

        foreach ($expiredUploads as $upload) {
            try {
                $this->deleteAllChunks($upload);
                $upload->delete();
                $count++;
            } catch (\Exception $e) {
                Log::error('Failed to cleanup expired upload', [
                    'upload_id' => $upload->chunked_upload_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }
}
