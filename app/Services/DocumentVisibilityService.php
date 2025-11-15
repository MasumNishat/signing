<?php

namespace App\Services;

use App\Exceptions\Custom\BusinessLogicException;
use App\Exceptions\Custom\ResourceNotFoundException;
use App\Models\Envelope;
use App\Models\EnvelopeRecipient;
use App\Models\RecipientDocumentVisibility;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * DocumentVisibilityService
 *
 * Business logic for managing document visibility per recipient.
 * Controls which documents each recipient can see in an envelope.
 */
class DocumentVisibilityService
{
    /**
     * Get document visibility settings for a recipient
     *
     * @param Envelope $envelope
     * @param string $recipientId
     * @return array
     * @throws ResourceNotFoundException
     */
    public function getDocumentVisibility(Envelope $envelope, string $recipientId): array
    {
        $recipient = EnvelopeRecipient::where('envelope_id', $envelope->id)
            ->where('recipient_id', $recipientId)
            ->first();

        if (!$recipient) {
            throw new ResourceNotFoundException('Recipient not found');
        }

        // Get all documents for the envelope
        $documents = $envelope->documents;

        // Get visibility settings for this recipient
        $visibilitySettings = RecipientDocumentVisibility::where('envelope_id', $envelope->id)
            ->where('recipient_id', $recipient->id)
            ->get()
            ->keyBy('document_id');

        // Build response
        $documentVisibility = [];
        foreach ($documents as $document) {
            $visibility = $visibilitySettings->get($document->id);

            $documentVisibility[] = [
                'document_id' => $document->document_id,
                'document_name' => $document->name,
                'visible' => $visibility ? $visibility->visible : true, // Default to visible
            ];
        }

        return [
            'recipient_id' => $recipientId,
            'recipient_name' => $recipient->name,
            'documents' => $documentVisibility,
        ];
    }

    /**
     * Update document visibility settings for a recipient
     *
     * @param Envelope $envelope
     * @param string $recipientId
     * @param array $documentsData
     * @return array
     * @throws BusinessLogicException
     */
    public function updateDocumentVisibility(Envelope $envelope, string $recipientId, array $documentsData): array
    {
        DB::beginTransaction();

        try {
            $recipient = EnvelopeRecipient::where('envelope_id', $envelope->id)
                ->where('recipient_id', $recipientId)
                ->first();

            if (!$recipient) {
                throw new ResourceNotFoundException('Recipient not found');
            }

            // Validate that all document IDs exist in this envelope
            $envelopeDocumentIds = $envelope->documents->pluck('document_id')->toArray();

            foreach ($documentsData as $docData) {
                if (!in_array($docData['document_id'], $envelopeDocumentIds)) {
                    throw new BusinessLogicException("Document {$docData['document_id']} not found in envelope");
                }

                // Get the actual document database ID
                $document = $envelope->documents->where('document_id', $docData['document_id'])->first();

                // Update or create visibility setting
                RecipientDocumentVisibility::updateOrCreate(
                    [
                        'envelope_id' => $envelope->id,
                        'recipient_id' => $recipient->id,
                        'document_id' => $document->id,
                    ],
                    [
                        'visible' => $docData['visible'] ?? true,
                    ]
                );
            }

            DB::commit();

            // Return updated visibility settings
            return $this->getDocumentVisibility($envelope, $recipientId);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Document visibility update failed', [
                'envelope_id' => $envelope->envelope_id,
                'recipient_id' => $recipientId,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to update document visibility: ' . $e->getMessage());
        }
    }

    /**
     * Check if a recipient can see a specific document
     *
     * @param Envelope $envelope
     * @param string $recipientId
     * @param string $documentId
     * @return bool
     */
    public function canRecipientSeeDocument(Envelope $envelope, string $recipientId, string $documentId): bool
    {
        $recipient = EnvelopeRecipient::where('envelope_id', $envelope->id)
            ->where('recipient_id', $recipientId)
            ->first();

        if (!$recipient) {
            return false;
        }

        $document = $envelope->documents->where('document_id', $documentId)->first();
        if (!$document) {
            return false;
        }

        $visibility = RecipientDocumentVisibility::where('envelope_id', $envelope->id)
            ->where('recipient_id', $recipient->id)
            ->where('document_id', $document->id)
            ->first();

        // Default to visible if no explicit setting
        return $visibility ? $visibility->visible : true;
    }
}
