<?php

namespace App\Jobs;

use App\Models\BulkSendBatch;
use App\Models\BulkSendList;
use App\Models\Envelope;
use App\Models\Template;
use App\Services\EnvelopeService;
use App\Services\TemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ProcessBulkSendBatchJob
 *
 * Processes a bulk send batch by creating and sending envelopes
 * to all recipients in the bulk send list.
 *
 * This job:
 * - Retrieves the bulk send batch and list
 * - For each recipient with valid email:
 *   - Creates envelope from template or copies envelope
 *   - Substitutes recipient information
 *   - Sends the envelope
 * - Updates batch progress counters
 * - Marks batch as completed when done
 */
class ProcessBulkSendBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3600; // 1 hour

    /**
     * The bulk send batch ID
     *
     * @var int
     */
    protected int $batchId;

    /**
     * The bulk send list ID
     *
     * @var int
     */
    protected int $listId;

    /**
     * Create a new job instance.
     *
     * @param int $batchId
     * @param int $listId
     */
    public function __construct(int $batchId, int $listId)
    {
        $this->batchId = $batchId;
        $this->listId = $listId;
        $this->onQueue('document-processing'); // Use document-processing queue
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(
        EnvelopeService $envelopeService,
        TemplateService $templateService
    ): void {
        try {
            // Get batch
            $batch = BulkSendBatch::find($this->batchId);
            if (!$batch) {
                Log::error('Bulk send batch not found', ['batch_id' => $this->batchId]);
                return;
            }

            // Mark batch as processing
            $batch->markAsProcessing();

            // Get list
            $list = BulkSendList::with(['recipients' => function ($query) {
                $query->withValidEmail();
            }])->find($this->listId);

            if (!$list) {
                $batch->markAsFailed('Bulk send list not found');
                Log::error('Bulk send list not found', ['list_id' => $this->listId]);
                return;
            }

            // Get template or envelope
            $template = null;
            $sourceEnvelope = null;

            if ($batch->template_id) {
                $template = Template::find($batch->template_id);
                if (!$template) {
                    $batch->markAsFailed('Template not found');
                    Log::error('Template not found', ['template_id' => $batch->template_id]);
                    return;
                }
            } elseif ($batch->envelope_id) {
                $sourceEnvelope = Envelope::find($batch->envelope_id);
                if (!$sourceEnvelope) {
                    $batch->markAsFailed('Source envelope not found');
                    Log::error('Source envelope not found', ['envelope_id' => $batch->envelope_id]);
                    return;
                }
            } else {
                $batch->markAsFailed('No template or envelope specified');
                Log::error('No template or envelope specified for batch', ['batch_id' => $this->batchId]);
                return;
            }

            // Process each recipient
            $sentCount = 0;
            $failedCount = 0;

            foreach ($list->recipients as $recipient) {
                try {
                    DB::beginTransaction();

                    // Create envelope from template or copy envelope
                    if ($template) {
                        $envelope = $this->createEnvelopeFromTemplate(
                            $template,
                            $recipient,
                            $templateService
                        );
                    } else {
                        $envelope = $this->createEnvelopeFromSource(
                            $sourceEnvelope,
                            $recipient,
                            $envelopeService
                        );
                    }

                    // Send envelope
                    $envelopeService->sendEnvelope($envelope->envelope_id, $batch->account_id);

                    DB::commit();
                    $sentCount++;
                    $batch->incrementSentCount();

                    Log::info('Bulk send envelope sent', [
                        'batch_id' => $batch->batch_id,
                        'envelope_id' => $envelope->envelope_id,
                        'recipient_email' => $recipient->recipient_email,
                    ]);

                } catch (\Exception $e) {
                    DB::rollBack();
                    $failedCount++;
                    $batch->incrementFailedCount();

                    Log::error('Failed to send bulk envelope', [
                        'batch_id' => $batch->batch_id,
                        'recipient_email' => $recipient->recipient_email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Mark batch as completed
            if ($failedCount === 0) {
                $batch->markAsSent();
            } else {
                $batch->markAsFailed("Sent {$sentCount}, failed {$failedCount}");
            }

            Log::info('Bulk send batch completed', [
                'batch_id' => $batch->batch_id,
                'sent' => $sentCount,
                'failed' => $failedCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process bulk send batch', [
                'batch_id' => $this->batchId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if (isset($batch)) {
                $batch->markAsFailed($e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Create envelope from template for a recipient
     *
     * @param Template $template
     * @param \App\Models\BulkSendRecipient $recipient
     * @param TemplateService $templateService
     * @return Envelope
     */
    protected function createEnvelopeFromTemplate(
        Template $template,
        $recipient,
        TemplateService $templateService
    ): Envelope {
        $recipientData = [
            'name' => $recipient->recipient_name,
            'email' => $recipient->recipient_email,
            'role_name' => 'Signer', // Default role, could be customized
        ];

        // Merge custom fields if present
        if (!empty($recipient->custom_fields)) {
            $recipientData = array_merge($recipientData, $recipient->custom_fields);
        }

        $envelopeData = [
            'sender_user_id' => $template->owner_user_id,
            'email_subject' => $template->template_name,
            'recipients' => [$recipientData],
        ];

        $envelope = $templateService->createEnvelopeFromTemplate($template, $envelopeData);

        // Link envelope to batch
        $envelope->update(['bulk_batch_id' => $this->batchId]);

        return $envelope;
    }

    /**
     * Create envelope by copying source envelope for a recipient
     *
     * @param Envelope $sourceEnvelope
     * @param \App\Models\BulkSendRecipient $recipient
     * @param EnvelopeService $envelopeService
     * @return Envelope
     */
    protected function createEnvelopeFromSource(
        Envelope $sourceEnvelope,
        $recipient,
        EnvelopeService $envelopeService
    ): Envelope {
        // Load source envelope with all relationships
        $sourceEnvelope->load(['documents', 'recipients', 'tabs', 'customFields']);

        $recipientData = [
            'name' => $recipient->recipient_name,
            'email' => $recipient->recipient_email,
            'role_name' => 'Signer',
        ];

        // Merge custom fields
        if (!empty($recipient->custom_fields)) {
            $recipientData = array_merge($recipientData, $recipient->custom_fields);
        }

        $envelopeData = [
            'email_subject' => $sourceEnvelope->email_subject,
            'email_message' => $sourceEnvelope->email_message,
            'status' => Envelope::STATUS_DRAFT,
            'recipients' => [$recipientData],
        ];

        // Create new envelope
        $envelope = $envelopeService->createEnvelope($sourceEnvelope->account_id, $envelopeData);

        // Link envelope to batch
        $envelope->update(['bulk_batch_id' => $this->batchId]);

        return $envelope;
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Bulk send batch job failed permanently', [
            'batch_id' => $this->batchId,
            'list_id' => $this->listId,
            'error' => $exception->getMessage(),
        ]);

        // Mark batch as failed
        $batch = BulkSendBatch::find($this->batchId);
        if ($batch) {
            $batch->markAsFailed('Job failed: ' . $exception->getMessage());
        }
    }
}
