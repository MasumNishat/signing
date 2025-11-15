<?php

namespace App\Services;

use App\Exceptions\Custom\BusinessLogicException;
use App\Exceptions\Custom\ResourceNotFoundException;
use App\Models\Envelope;
use App\Models\EnvelopeAttachment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * EnvelopeAttachmentService
 *
 * Business logic for managing envelope attachments.
 * Supports base64 data and remote URLs.
 */
class EnvelopeAttachmentService
{
    /**
     * Get all attachments for an envelope
     *
     * @param Envelope $envelope
     * @return Collection
     */
    public function getAttachments(Envelope $envelope): Collection
    {
        return EnvelopeAttachment::where('envelope_id', $envelope->id)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get a specific attachment
     *
     * @param Envelope $envelope
     * @param string $attachmentId
     * @return EnvelopeAttachment
     * @throws ResourceNotFoundException
     */
    public function getAttachment(Envelope $envelope, string $attachmentId): EnvelopeAttachment
    {
        $attachment = EnvelopeAttachment::where('envelope_id', $envelope->id)
            ->where('attachment_id', $attachmentId)
            ->first();

        if (!$attachment) {
            throw new ResourceNotFoundException('Attachment not found');
        }

        return $attachment;
    }

    /**
     * Create attachments for an envelope
     *
     * @param Envelope $envelope
     * @param array $attachmentsData
     * @return Collection
     * @throws BusinessLogicException
     */
    public function createAttachments(Envelope $envelope, array $attachmentsData): Collection
    {
        DB::beginTransaction();

        try {
            $attachments = [];

            foreach ($attachmentsData as $data) {
                // Validate that either data_base64 or remote_url is provided
                if (empty($data['data_base64']) && empty($data['remote_url'])) {
                    throw new BusinessLogicException('Either data_base64 or remote_url must be provided');
                }

                // Calculate size if base64 data provided
                $sizeBytes = null;
                if (!empty($data['data_base64'])) {
                    // Approximate size from base64 (base64 is ~33% larger than original)
                    $sizeBytes = (int) (strlen($data['data_base64']) * 0.75);
                }

                $attachment = EnvelopeAttachment::create([
                    'envelope_id' => $envelope->id,
                    'attachment_id' => $data['attachment_id'] ?? null, // Auto-generated if null
                    'label' => $data['label'] ?? null,
                    'name' => $data['name'] ?? null,
                    'attachment_type' => $data['attachment_type'] ?? EnvelopeAttachment::TYPE_SENDER,
                    'data_base64' => $data['data_base64'] ?? null,
                    'remote_url' => $data['remote_url'] ?? null,
                    'file_extension' => $data['file_extension'] ?? null,
                    'access_control' => $data['access_control'] ?? EnvelopeAttachment::ACCESS_ALL,
                    'display' => $data['display'] ?? null,
                    'size_bytes' => $sizeBytes,
                ]);

                $attachments[] = $attachment;
            }

            DB::commit();

            return new Collection($attachments);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Envelope attachment creation failed', [
                'envelope_id' => $envelope->envelope_id,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to create envelope attachments: ' . $e->getMessage());
        }
    }

    /**
     * Update attachments for an envelope
     *
     * Replaces all existing attachments with new ones
     *
     * @param Envelope $envelope
     * @param array $attachmentsData
     * @return Collection
     * @throws BusinessLogicException
     */
    public function updateAttachments(Envelope $envelope, array $attachmentsData): Collection
    {
        DB::beginTransaction();

        try {
            // Delete existing attachments
            EnvelopeAttachment::where('envelope_id', $envelope->id)->delete();

            // Create new attachments
            $attachments = $this->createAttachments($envelope, $attachmentsData);

            DB::commit();

            return $attachments;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Envelope attachment update failed', [
                'envelope_id' => $envelope->envelope_id,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to update envelope attachments: ' . $e->getMessage());
        }
    }

    /**
     * Update a specific attachment
     *
     * @param EnvelopeAttachment $attachment
     * @param array $data
     * @return EnvelopeAttachment
     * @throws BusinessLogicException
     */
    public function updateAttachment(EnvelopeAttachment $attachment, array $data): EnvelopeAttachment
    {
        try {
            // Calculate size if base64 data updated
            if (!empty($data['data_base64'])) {
                $data['size_bytes'] = (int) (strlen($data['data_base64']) * 0.75);
            }

            $attachment->update([
                'label' => $data['label'] ?? $attachment->label,
                'name' => $data['name'] ?? $attachment->name,
                'attachment_type' => $data['attachment_type'] ?? $attachment->attachment_type,
                'data_base64' => $data['data_base64'] ?? $attachment->data_base64,
                'remote_url' => $data['remote_url'] ?? $attachment->remote_url,
                'file_extension' => $data['file_extension'] ?? $attachment->file_extension,
                'access_control' => $data['access_control'] ?? $attachment->access_control,
                'display' => $data['display'] ?? $attachment->display,
                'size_bytes' => $data['size_bytes'] ?? $attachment->size_bytes,
            ]);

            return $attachment->fresh();
        } catch (\Exception $e) {
            Log::error('Attachment update failed', [
                'attachment_id' => $attachment->attachment_id,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to update attachment: ' . $e->getMessage());
        }
    }

    /**
     * Delete all attachments for an envelope
     *
     * @param Envelope $envelope
     * @return int Number of attachments deleted
     * @throws BusinessLogicException
     */
    public function deleteAttachments(Envelope $envelope): int
    {
        try {
            return EnvelopeAttachment::where('envelope_id', $envelope->id)->delete();
        } catch (\Exception $e) {
            Log::error('Envelope attachments deletion failed', [
                'envelope_id' => $envelope->envelope_id,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to delete envelope attachments: ' . $e->getMessage());
        }
    }

    /**
     * Delete a specific attachment
     *
     * @param EnvelopeAttachment $attachment
     * @return bool
     * @throws BusinessLogicException
     */
    public function deleteAttachment(EnvelopeAttachment $attachment): bool
    {
        try {
            return $attachment->delete();
        } catch (\Exception $e) {
            Log::error('Attachment deletion failed', [
                'attachment_id' => $attachment->attachment_id,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to delete attachment: ' . $e->getMessage());
        }
    }
}
