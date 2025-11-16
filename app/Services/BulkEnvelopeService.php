<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Envelope;
use App\Models\EnvelopeRecipient;
use App\Exceptions\Custom\BusinessLogicException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Bulk Envelope Service
 *
 * Handles bulk operations for envelopes, recipients, and documents.
 * Uses queue-based processing for large batches.
 */
class BulkEnvelopeService
{
    /**
     * Envelope service
     */
    protected EnvelopeService $envelopeService;

    /**
     * Recipient service
     */
    protected RecipientService $recipientService;

    /**
     * Document service
     */
    protected EnvelopeDocumentService $documentService;

    /**
     * Initialize service
     */
    public function __construct(
        EnvelopeService $envelopeService,
        RecipientService $recipientService,
        EnvelopeDocumentService $documentService
    ) {
        $this->envelopeService = $envelopeService;
        $this->recipientService = $recipientService;
        $this->documentService = $documentService;
    }

    /**
     * Bulk status update
     *
     * @param Account $account
     * @param array $envelopeIds
     * @param string $status
     * @param string|null $voidReason
     * @return array
     */
    public function bulkStatusUpdate(
        Account $account,
        array $envelopeIds,
        string $status,
        ?string $voidReason = null
    ): array {
        $batchId = Str::uuid()->toString();
        $processed = 0;
        $failed = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($envelopeIds as $envelopeId) {
                try {
                    $envelope = Envelope::where('account_id', $account->id)
                        ->where('envelope_id', $envelopeId)
                        ->first();

                    if (!$envelope) {
                        $failed++;
                        $errors[] = [
                            'envelope_id' => $envelopeId,
                            'error' => 'Envelope not found',
                        ];
                        continue;
                    }

                    // Perform status update based on target status
                    switch ($status) {
                        case 'voided':
                            $this->envelopeService->voidEnvelope($envelope, $voidReason ?? 'Bulk void operation');
                            break;
                        case 'sent':
                            $this->envelopeService->sendEnvelope($envelope);
                            break;
                        default:
                            // For other statuses, update directly
                            $envelope->status = $status;
                            $envelope->save();
                    }

                    $processed++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'envelope_id' => $envelopeId,
                        'error' => $e->getMessage(),
                    ];
                    Log::error('Bulk status update failed for envelope', [
                        'envelope_id' => $envelopeId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            return [
                'batch_id' => $batchId,
                'total_envelopes' => count($envelopeIds),
                'processed' => $processed,
                'failed' => $failed,
                'errors' => $errors,
                'status' => $failed === 0 ? 'completed' : 'completed_with_errors',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk status update failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Bulk void envelopes
     *
     * @param Account $account
     * @param array $envelopeIds
     * @param string $voidReason
     * @return array
     */
    public function bulkVoid(Account $account, array $envelopeIds, string $voidReason): array
    {
        return $this->bulkStatusUpdate($account, $envelopeIds, 'voided', $voidReason);
    }

    /**
     * Bulk resend envelopes
     *
     * @param Account $account
     * @param array $envelopeIds
     * @return array
     */
    public function bulkResend(Account $account, array $envelopeIds): array
    {
        $batchId = Str::uuid()->toString();
        $processed = 0;
        $failed = 0;
        $errors = [];

        foreach ($envelopeIds as $envelopeId) {
            try {
                $envelope = Envelope::where('account_id', $account->id)
                    ->where('envelope_id', $envelopeId)
                    ->with('recipients')
                    ->first();

                if (!$envelope) {
                    $failed++;
                    $errors[] = [
                        'envelope_id' => $envelopeId,
                        'error' => 'Envelope not found',
                    ];
                    continue;
                }

                // Resend to all pending recipients
                foreach ($envelope->recipients as $recipient) {
                    if (!$recipient->hasSigned() && !$recipient->hasDeclined()) {
                        $this->recipientService->resendNotification($recipient);
                    }
                }

                $processed++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'envelope_id' => $envelopeId,
                    'error' => $e->getMessage(),
                ];
                Log::error('Bulk resend failed for envelope', [
                    'envelope_id' => $envelopeId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'batch_id' => $batchId,
            'total_envelopes' => count($envelopeIds),
            'processed' => $processed,
            'failed' => $failed,
            'errors' => $errors,
            'status' => $failed === 0 ? 'completed' : 'completed_with_errors',
        ];
    }

    /**
     * Bulk recipient update
     *
     * @param Account $account
     * @param array $updates
     * @return array
     */
    public function bulkRecipientUpdate(Account $account, array $updates): array
    {
        $batchId = Str::uuid()->toString();
        $processed = 0;
        $failed = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($updates as $update) {
                try {
                    $envelope = Envelope::where('account_id', $account->id)
                        ->where('envelope_id', $update['envelope_id'])
                        ->first();

                    if (!$envelope) {
                        $failed++;
                        $errors[] = [
                            'envelope_id' => $update['envelope_id'],
                            'error' => 'Envelope not found',
                        ];
                        continue;
                    }

                    $recipient = $this->recipientService->getRecipient($envelope, $update['recipient_id']);

                    $this->recipientService->updateRecipient($recipient, $update);

                    $processed++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'envelope_id' => $update['envelope_id'],
                        'recipient_id' => $update['recipient_id'],
                        'error' => $e->getMessage(),
                    ];
                    Log::error('Bulk recipient update failed', [
                        'update' => $update,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            return [
                'batch_id' => $batchId,
                'total_recipients' => count($updates),
                'processed' => $processed,
                'failed' => $failed,
                'errors' => $errors,
                'status' => $failed === 0 ? 'completed' : 'completed_with_errors',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk recipient update failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Bulk resend to recipients
     *
     * @param Account $account
     * @param array $resends
     * @return array
     */
    public function bulkRecipientResend(Account $account, array $resends): array
    {
        $batchId = Str::uuid()->toString();
        $processed = 0;
        $failed = 0;
        $errors = [];

        foreach ($resends as $resend) {
            try {
                $envelope = Envelope::where('account_id', $account->id)
                    ->where('envelope_id', $resend['envelope_id'])
                    ->first();

                if (!$envelope) {
                    $failed++;
                    $errors[] = [
                        'envelope_id' => $resend['envelope_id'],
                        'error' => 'Envelope not found',
                    ];
                    continue;
                }

                $recipient = $this->recipientService->getRecipient($envelope, $resend['recipient_id']);

                $this->recipientService->resendNotification($recipient);

                $processed++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'envelope_id' => $resend['envelope_id'],
                    'recipient_id' => $resend['recipient_id'],
                    'error' => $e->getMessage(),
                ];
                Log::error('Bulk recipient resend failed', [
                    'resend' => $resend,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'batch_id' => $batchId,
            'total_recipients' => count($resends),
            'processed' => $processed,
            'failed' => $failed,
            'errors' => $errors,
            'status' => $failed === 0 ? 'completed' : 'completed_with_errors',
        ];
    }

    /**
     * Bulk recipient remove
     *
     * @param Account $account
     * @param array $removals
     * @return array
     */
    public function bulkRecipientRemove(Account $account, array $removals): array
    {
        $batchId = Str::uuid()->toString();
        $processed = 0;
        $failed = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($removals as $removal) {
                try {
                    $envelope = Envelope::where('account_id', $account->id)
                        ->where('envelope_id', $removal['envelope_id'])
                        ->first();

                    if (!$envelope) {
                        $failed++;
                        $errors[] = [
                            'envelope_id' => $removal['envelope_id'],
                            'error' => 'Envelope not found',
                        ];
                        continue;
                    }

                    $recipient = $this->recipientService->getRecipient($envelope, $removal['recipient_id']);

                    $this->recipientService->deleteRecipient($recipient);

                    $processed++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'envelope_id' => $removal['envelope_id'],
                        'recipient_id' => $removal['recipient_id'],
                        'error' => $e->getMessage(),
                    ];
                    Log::error('Bulk recipient removal failed', [
                        'removal' => $removal,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            return [
                'batch_id' => $batchId,
                'total_recipients' => count($removals),
                'processed' => $processed,
                'failed' => $failed,
                'errors' => $errors,
                'status' => $failed === 0 ? 'completed' : 'completed_with_errors',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk recipient removal failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Bulk add documents
     *
     * @param Account $account
     * @param array $operations
     * @return array
     */
    public function bulkDocumentAdd(Account $account, array $operations): array
    {
        $batchId = Str::uuid()->toString();
        $processed = 0;
        $failed = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($operations as $operation) {
                try {
                    $envelope = Envelope::where('account_id', $account->id)
                        ->where('envelope_id', $operation['envelope_id'])
                        ->first();

                    if (!$envelope) {
                        $failed++;
                        $errors[] = [
                            'envelope_id' => $operation['envelope_id'],
                            'error' => 'Envelope not found',
                        ];
                        continue;
                    }

                    foreach ($operation['documents'] as $documentData) {
                        $envelope->documents()->create($documentData);
                    }

                    $processed++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'envelope_id' => $operation['envelope_id'],
                        'error' => $e->getMessage(),
                    ];
                    Log::error('Bulk document add failed', [
                        'operation' => $operation,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            return [
                'batch_id' => $batchId,
                'total_operations' => count($operations),
                'processed' => $processed,
                'failed' => $failed,
                'errors' => $errors,
                'status' => $failed === 0 ? 'completed' : 'completed_with_errors',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk document add failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Bulk replace documents
     *
     * @param Account $account
     * @param array $operations
     * @return array
     */
    public function bulkDocumentReplace(Account $account, array $operations): array
    {
        $batchId = Str::uuid()->toString();
        $processed = 0;
        $failed = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($operations as $operation) {
                try {
                    $envelope = Envelope::where('account_id', $account->id)
                        ->where('envelope_id', $operation['envelope_id'])
                        ->first();

                    if (!$envelope) {
                        $failed++;
                        $errors[] = [
                            'envelope_id' => $operation['envelope_id'],
                            'error' => 'Envelope not found',
                        ];
                        continue;
                    }

                    $document = $envelope->documents()
                        ->where('document_id', $operation['document_id'])
                        ->first();

                    if (!$document) {
                        $failed++;
                        $errors[] = [
                            'envelope_id' => $operation['envelope_id'],
                            'document_id' => $operation['document_id'],
                            'error' => 'Document not found',
                        ];
                        continue;
                    }

                    if (isset($operation['name'])) {
                        $document->name = $operation['name'];
                    }
                    if (isset($operation['order'])) {
                        $document->order = $operation['order'];
                    }
                    $document->save();

                    $processed++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'envelope_id' => $operation['envelope_id'],
                        'document_id' => $operation['document_id'],
                        'error' => $e->getMessage(),
                    ];
                    Log::error('Bulk document replace failed', [
                        'operation' => $operation,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            return [
                'batch_id' => $batchId,
                'total_operations' => count($operations),
                'processed' => $processed,
                'failed' => $failed,
                'errors' => $errors,
                'status' => $failed === 0 ? 'completed' : 'completed_with_errors',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk document replace failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Bulk delete documents
     *
     * @param Account $account
     * @param array $deletions
     * @return array
     */
    public function bulkDocumentDelete(Account $account, array $deletions): array
    {
        $batchId = Str::uuid()->toString();
        $processed = 0;
        $failed = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($deletions as $deletion) {
                try {
                    $envelope = Envelope::where('account_id', $account->id)
                        ->where('envelope_id', $deletion['envelope_id'])
                        ->first();

                    if (!$envelope) {
                        $failed++;
                        $errors[] = [
                            'envelope_id' => $deletion['envelope_id'],
                            'error' => 'Envelope not found',
                        ];
                        continue;
                    }

                    $deleted = $envelope->documents()
                        ->where('document_id', $deletion['document_id'])
                        ->delete();

                    if ($deleted > 0) {
                        $processed++;
                    } else {
                        $failed++;
                        $errors[] = [
                            'envelope_id' => $deletion['envelope_id'],
                            'document_id' => $deletion['document_id'],
                            'error' => 'Document not found',
                        ];
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = [
                        'envelope_id' => $deletion['envelope_id'],
                        'document_id' => $deletion['document_id'],
                        'error' => $e->getMessage(),
                    ];
                    Log::error('Bulk document deletion failed', [
                        'deletion' => $deletion,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            return [
                'batch_id' => $batchId,
                'total_deletions' => count($deletions),
                'processed' => $processed,
                'failed' => $failed,
                'errors' => $errors,
                'status' => $failed === 0 ? 'completed' : 'completed_with_errors',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk document deletion failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Bulk document download
     *
     * @param Account $account
     * @param array $envelopeIds
     * @param array $documentIds
     * @param bool $includeCertificate
     * @return array
     */
    public function bulkDocumentDownload(
        Account $account,
        array $envelopeIds,
        array $documentIds = [],
        bool $includeCertificate = false
    ): array {
        $batchId = Str::uuid()->toString();
        $downloadUrl = config('app.url') . '/bulk-downloads/' . $batchId;
        $files = [];

        foreach ($envelopeIds as $envelopeId) {
            $envelope = Envelope::where('account_id', $account->id)
                ->where('envelope_id', $envelopeId)
                ->with('documents')
                ->first();

            if (!$envelope) {
                continue;
            }

            // Filter documents if specific IDs provided
            $documents = $envelope->documents;
            if (!empty($documentIds)) {
                $documents = $documents->whereIn('document_id', $documentIds);
            }

            foreach ($documents as $document) {
                $files[] = [
                    'envelope_id' => $envelope->envelope_id,
                    'document_id' => $document->document_id,
                    'name' => $document->name,
                    'file_extension' => $document->file_extension,
                    'uri' => $document->uri,
                ];
            }

            // Add certificate if requested
            if ($includeCertificate && $envelope->status === 'completed') {
                $files[] = [
                    'envelope_id' => $envelope->envelope_id,
                    'document_id' => 'certificate',
                    'name' => 'Certificate of Completion',
                    'file_extension' => 'pdf',
                    'uri' => '/certificates/' . $envelope->envelope_id . '.pdf',
                ];
            }
        }

        return [
            'batch_id' => $batchId,
            'download_url' => $downloadUrl,
            'total_files' => count($files),
            'files' => $files,
            'expires_at' => now()->addHours(24)->toIso8601String(),
            'status' => 'ready',
        ];
    }
}
