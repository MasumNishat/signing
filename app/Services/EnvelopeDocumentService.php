<?php

namespace App\Services;

use App\Models\Envelope;
use App\Exceptions\Custom\BusinessLogicException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Envelope Document Service
 *
 * Handles envelope document operations including PDF generation,
 * certificate of completion, and document downloads.
 */
class EnvelopeDocumentService
{
    /**
     * Download envelope as combined PDF
     *
     * @param Envelope $envelope
     * @param array $options Download options
     * @return array PDF data
     * @throws BusinessLogicException
     */
    public function downloadEnvelopePdf(Envelope $envelope, array $options = []): array
    {
        if (!$envelope->isCompleted()) {
            throw new BusinessLogicException('Can only download PDF for completed envelopes');
        }

        // Options
        $includeCertificate = $options['include_certificate'] ?? true;
        $watermark = $options['watermark'] ?? null;

        // In production, this would:
        // 1. Combine all documents into single PDF
        // 2. Add signature stamps to signed fields
        // 3. Add certificate of completion
        // 4. Apply watermark if specified
        // 5. Return PDF stream or file path

        // For now, return placeholder data
        $filename = sprintf(
            'envelope_%s_completed.pdf',
            $envelope->envelope_id
        );

        Log::info('Envelope PDF download requested', [
            'envelope_id' => $envelope->envelope_id,
            'include_certificate' => $includeCertificate,
            'filename' => $filename,
        ]);

        // Placeholder: In production, generate actual PDF
        return [
            'envelope_id' => $envelope->envelope_id,
            'filename' => $filename,
            'download_url' => sprintf('%s/api/v2.1/envelopes/%s/download/pdf', config('app.url'), $envelope->envelope_id),
            'file_size' => 0, // Would be actual PDF size
            'mime_type' => 'application/pdf',
            'generated_at' => now()->toIso8601String(),
            'expires_at' => now()->addHours(24)->toIso8601String(),
            'includes_certificate' => $includeCertificate,
            'watermark' => $watermark,
        ];
    }

    /**
     * Generate certificate of completion
     *
     * @param Envelope $envelope
     * @param array $options Certificate options
     * @return array Certificate data
     * @throws BusinessLogicException
     */
    public function generateCertificate(Envelope $envelope, array $options = []): array
    {
        if (!$envelope->isCompleted()) {
            throw new BusinessLogicException('Certificate can only be generated for completed envelopes');
        }

        // Get all audit events
        $auditEvents = $envelope->auditEvents()->orderBy('event_timestamp')->get();

        // Get all recipients with signed timestamps
        $recipients = $envelope->recipients()
            ->whereNotNull('signed_date_time')
            ->orderBy('routing_order')
            ->get();

        // Certificate data
        $certificateData = [
            'envelope_id' => $envelope->envelope_id,
            'subject' => $envelope->email_subject ?? 'Document Signing',
            'sender' => [
                'name' => $envelope->sender->first_name . ' ' . $envelope->sender->last_name,
                'email' => $envelope->sender->email,
            ],
            'sent_date_time' => $envelope->sent_date_time?->toIso8601String(),
            'completed_date_time' => $envelope->completed_date_time?->toIso8601String(),
            'status' => $envelope->status,
            'recipients' => $recipients->map(function ($recipient) {
                return [
                    'name' => $recipient->name,
                    'email' => $recipient->email,
                    'recipient_type' => $recipient->recipient_type,
                    'routing_order' => $recipient->routing_order,
                    'signed_date_time' => $recipient->signed_date_time?->toIso8601String(),
                    'ip_address' => $recipient->signed_ip_address ?? 'N/A',
                ];
            })->toArray(),
            'documents' => $envelope->documents->map(function ($document) {
                return [
                    'name' => $document->name,
                    'document_id' => $document->document_id,
                    'file_extension' => $document->file_extension,
                    'pages' => $document->pages ?? 0,
                ];
            })->toArray(),
            'audit_trail' => $auditEvents->map(function ($event) {
                return [
                    'event_type' => $event->event_type,
                    'timestamp' => $event->event_timestamp->toIso8601String(),
                    'user_name' => $event->user_name ?? 'System',
                    'user_email' => $event->user_email,
                    'ip_address' => $event->ip_address ?? 'N/A',
                ];
            })->toArray(),
            'security' => [
                'envelope_id_hash' => hash('sha256', $envelope->envelope_id),
                'document_hash' => $this->calculateEnvelopeHash($envelope),
                'timestamp' => now()->toIso8601String(),
            ],
            'certificate_id' => sprintf('CERT-%s-%d', $envelope->envelope_id, time()),
            'generated_at' => now()->toIso8601String(),
        ];

        // In production, this would generate a PDF certificate
        $filename = sprintf(
            'certificate_%s.pdf',
            $envelope->envelope_id
        );

        Log::info('Certificate of completion generated', [
            'envelope_id' => $envelope->envelope_id,
            'certificate_id' => $certificateData['certificate_id'],
            'recipients_count' => count($certificateData['recipients']),
        ]);

        return [
            'certificate' => $certificateData,
            'filename' => $filename,
            'download_url' => sprintf('%s/api/v2.1/envelopes/%s/certificate', config('app.url'), $envelope->envelope_id),
            'format' => 'pdf',
        ];
    }

    /**
     * Calculate envelope hash for tamper detection
     *
     * @param Envelope $envelope
     * @return string
     */
    protected function calculateEnvelopeHash(Envelope $envelope): string
    {
        $data = [
            'envelope_id' => $envelope->envelope_id,
            'status' => $envelope->status,
            'sent_date_time' => $envelope->sent_date_time?->timestamp,
            'completed_date_time' => $envelope->completed_date_time?->timestamp,
            'recipients' => $envelope->recipients->map(function ($r) {
                return [
                    'email' => $r->email,
                    'signed_date_time' => $r->signed_date_time?->timestamp,
                ];
            })->toArray(),
        ];

        return hash('sha256', json_encode($data));
    }

    /**
     * Download specific document from envelope
     *
     * @param Envelope $envelope
     * @param string $documentId
     * @param array $options Download options
     * @return array Document data
     * @throws BusinessLogicException
     */
    public function downloadDocument(Envelope $envelope, string $documentId, array $options = []): array
    {
        $document = $envelope->documents()
            ->where('document_id', $documentId)
            ->firstOrFail();

        $includeCertificate = $options['include_certificate'] ?? false;
        $showChanges = $options['show_changes'] ?? false;

        // In production, this would retrieve the document file from storage
        $filename = sprintf(
            '%s.%s',
            $document->name,
            $document->file_extension ?? 'pdf'
        );

        Log::info('Document download requested', [
            'envelope_id' => $envelope->envelope_id,
            'document_id' => $documentId,
            'filename' => $filename,
        ]);

        return [
            'document_id' => $document->document_id,
            'filename' => $filename,
            'download_url' => sprintf(
                '%s/api/v2.1/envelopes/%s/documents/%s/download',
                config('app.url'),
                $envelope->envelope_id,
                $documentId
            ),
            'file_size' => 0, // Would be actual file size
            'mime_type' => $this->getMimeType($document->file_extension ?? 'pdf'),
            'generated_at' => now()->toIso8601String(),
            'expires_at' => now()->addHours(24)->toIso8601String(),
        ];
    }

    /**
     * Get MIME type from file extension
     *
     * @param string $extension
     * @return string
     */
    protected function getMimeType(string $extension): string
    {
        return match (strtolower($extension)) {
            'pdf' => 'application/pdf',
            'doc', 'docx' => 'application/msword',
            'xls', 'xlsx' => 'application/vnd.ms-excel',
            'ppt', 'pptx' => 'application/vnd.ms-powerpoint',
            'txt' => 'text/plain',
            'html', 'htm' => 'text/html',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            default => 'application/octet-stream',
        };
    }

    /**
     * Get envelope form data (extracted field values)
     *
     * @param Envelope $envelope
     * @return array Form data
     */
    public function getEnvelopeFormData(Envelope $envelope): array
    {
        if (!$envelope->isCompleted()) {
            throw new BusinessLogicException('Form data only available for completed envelopes');
        }

        // Get all tabs with values
        $formData = [];

        foreach ($envelope->recipients as $recipient) {
            $recipientData = [
                'recipient_id' => $recipient->recipient_id,
                'recipient_name' => $recipient->name,
                'recipient_email' => $recipient->email,
                'fields' => [],
            ];

            foreach ($recipient->tabs as $tab) {
                if ($tab->value !== null) {
                    $recipientData['fields'][] = [
                        'tab_label' => $tab->tab_label,
                        'tab_type' => $tab->type,
                        'value' => $tab->value,
                        'page_number' => $tab->page_number,
                        'document_id' => $tab->document?->document_id,
                    ];
                }
            }

            $formData[] = $recipientData;
        }

        return [
            'envelope_id' => $envelope->envelope_id,
            'status' => $envelope->status,
            'completed_date_time' => $envelope->completed_date_time?->toIso8601String(),
            'form_data' => $formData,
        ];
    }
}
