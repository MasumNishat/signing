<?php

namespace App\Services;

use App\Exceptions\Custom\BusinessLogicException;
use App\Exceptions\Custom\ResourceNotFoundException;
use App\Exceptions\Custom\ValidationException;
use App\Jobs\ProcessBulkSendBatchJob;
use App\Models\BulkSendBatch;
use App\Models\BulkSendList;
use App\Models\BulkSendRecipient;
use App\Models\Envelope;
use App\Models\Template;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * BulkSendService
 *
 * Handles business logic for bulk envelope sending operations.
 * Manages bulk send batches, recipient lists, and queue-based processing.
 */
class BulkSendService
{
    /**
     * Create a new bulk send batch
     *
     * @param int $accountId
     * @param array $data
     * @return BulkSendBatch
     * @throws ValidationException
     * @throws BusinessLogicException
     */
    public function createBatch(int $accountId, array $data): BulkSendBatch
    {
        DB::beginTransaction();
        try {
            // Validate template or envelope
            if (empty($data['template_id']) && empty($data['envelope_id'])) {
                throw new ValidationException('Either template_id or envelope_id is required');
            }

            if (!empty($data['template_id']) && !empty($data['envelope_id'])) {
                throw new ValidationException('Cannot specify both template_id and envelope_id');
            }

            // Validate template exists if provided
            if (!empty($data['template_id'])) {
                $template = Template::where('template_id', $data['template_id'])
                    ->where('account_id', $accountId)
                    ->first();

                if (!$template) {
                    throw new ResourceNotFoundException('Template not found');
                }
            }

            // Validate envelope exists if provided
            if (!empty($data['envelope_id'])) {
                $envelope = Envelope::where('envelope_id', $data['envelope_id'])
                    ->where('account_id', $accountId)
                    ->first();

                if (!$envelope) {
                    throw new ResourceNotFoundException('Envelope not found');
                }

                if (!$envelope->isDraft()) {
                    throw new BusinessLogicException('Only draft envelopes can be used for bulk send');
                }
            }

            // Create batch
            $batch = BulkSendBatch::create([
                'account_id' => $accountId,
                'template_id' => $data['template_id'] ?? null,
                'envelope_id' => $data['envelope_id'] ?? null,
                'batch_name' => $data['batch_name'] ?? null,
                'batch_size' => $data['batch_size'] ?? 0,
                'status' => BulkSendBatch::STATUS_QUEUED,
                'envelopes_sent' => 0,
                'envelopes_failed' => 0,
                'submitted_date_time' => now(),
            ]);

            DB::commit();

            Log::info('Bulk send batch created', [
                'batch_id' => $batch->batch_id,
                'account_id' => $accountId,
            ]);

            return $batch->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create bulk send batch', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get a bulk send batch by ID
     *
     * @param int $accountId
     * @param string $batchId
     * @return BulkSendBatch
     * @throws ResourceNotFoundException
     */
    public function getBatch(int $accountId, string $batchId): BulkSendBatch
    {
        $batch = BulkSendBatch::where('batch_id', $batchId)
            ->where('account_id', $accountId)
            ->with(['template', 'envelope'])
            ->first();

        if (!$batch) {
            throw new ResourceNotFoundException('Bulk send batch not found');
        }

        return $batch;
    }

    /**
     * List bulk send batches with filters and pagination
     *
     * @param int $accountId
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function listBatches(int $accountId, array $filters = []): LengthAwarePaginator
    {
        $query = BulkSendBatch::where('account_id', $accountId);

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by date range
        if (!empty($filters['from_date'])) {
            $query->where('submitted_date_time', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->where('submitted_date_time', '<=', $filters['to_date']);
        }

        // Search by batch name
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'submitted_date_time';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Eager load relationships
        $query->with(['template', 'envelope']);

        // Paginate
        $perPage = $filters['per_page'] ?? 20;
        return $query->paginate($perPage);
    }

    /**
     * Update bulk send batch status
     *
     * @param int $accountId
     * @param string $batchId
     * @param array $data
     * @return BulkSendBatch
     * @throws ResourceNotFoundException
     * @throws BusinessLogicException
     */
    public function updateBatch(int $accountId, string $batchId, array $data): BulkSendBatch
    {
        $batch = $this->getBatch($accountId, $batchId);

        DB::beginTransaction();
        try {
            // Only allow updating certain fields
            $allowedFields = ['batch_name'];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $batch->$field = $data[$field];
                }
            }

            $batch->save();

            DB::commit();

            Log::info('Bulk send batch updated', [
                'batch_id' => $batchId,
                'account_id' => $accountId,
            ]);

            return $batch->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update bulk send batch', [
                'batch_id' => $batchId,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to update bulk send batch');
        }
    }

    /**
     * Perform action on bulk send batch
     *
     * @param int $accountId
     * @param string $batchId
     * @param string $action
     * @return BulkSendBatch
     * @throws ResourceNotFoundException
     * @throws BusinessLogicException
     */
    public function performBatchAction(int $accountId, string $batchId, string $action): BulkSendBatch
    {
        $batch = $this->getBatch($accountId, $batchId);

        DB::beginTransaction();
        try {
            switch ($action) {
                case 'pause':
                    if ($batch->status !== BulkSendBatch::STATUS_PROCESSING) {
                        throw new BusinessLogicException('Only processing batches can be paused');
                    }
                    // Implementation would pause queue processing
                    Log::info('Bulk send batch paused', ['batch_id' => $batchId]);
                    break;

                case 'resume':
                    if ($batch->status !== BulkSendBatch::STATUS_PROCESSING) {
                        throw new BusinessLogicException('Only processing batches can be resumed');
                    }
                    // Implementation would resume queue processing
                    Log::info('Bulk send batch resumed', ['batch_id' => $batchId]);
                    break;

                case 'cancel':
                    if ($batch->status === BulkSendBatch::STATUS_SENT) {
                        throw new BusinessLogicException('Cannot cancel completed batch');
                    }
                    $batch->markAsFailed('Cancelled by user');
                    Log::info('Bulk send batch cancelled', ['batch_id' => $batchId]);
                    break;

                case 'resend_failed':
                    if ($batch->envelopes_failed === 0) {
                        throw new BusinessLogicException('No failed envelopes to resend');
                    }
                    // Implementation would requeue failed envelopes
                    Log::info('Resending failed envelopes', ['batch_id' => $batchId]);
                    break;

                default:
                    throw new ValidationException("Invalid action: {$action}");
            }

            DB::commit();
            return $batch->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to perform batch action', [
                'batch_id' => $batchId,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get envelopes created in a batch
     *
     * @param int $accountId
     * @param string $batchId
     * @param array $filters
     * @return LengthAwarePaginator
     * @throws ResourceNotFoundException
     */
    public function getBatchEnvelopes(int $accountId, string $batchId, array $filters = []): LengthAwarePaginator
    {
        $batch = $this->getBatch($accountId, $batchId);

        $query = Envelope::where('account_id', $accountId)
            ->where('bulk_batch_id', $batch->id);

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Paginate
        $perPage = $filters['per_page'] ?? 50;
        return $query->paginate($perPage);
    }

    /**
     * Create a bulk send list
     *
     * @param int $accountId
     * @param int $createdByUserId
     * @param array $data
     * @return BulkSendList
     * @throws ValidationException
     */
    public function createList(int $accountId, int $createdByUserId, array $data): BulkSendList
    {
        DB::beginTransaction();
        try {
            // Validate required fields
            if (empty($data['list_name'])) {
                throw new ValidationException('list_name is required');
            }

            // Create list
            $list = BulkSendList::create([
                'account_id' => $accountId,
                'created_by_user_id' => $createdByUserId,
                'list_name' => $data['list_name'],
            ]);

            // Add recipients if provided
            if (!empty($data['recipients']) && is_array($data['recipients'])) {
                $this->addRecipientsToList($list, $data['recipients']);
            }

            DB::commit();

            Log::info('Bulk send list created', [
                'list_id' => $list->list_id,
                'account_id' => $accountId,
                'recipient_count' => count($data['recipients'] ?? []),
            ]);

            return $list->fresh(['recipients']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create bulk send list', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get a bulk send list by ID
     *
     * @param int $accountId
     * @param string $listId
     * @return BulkSendList
     * @throws ResourceNotFoundException
     */
    public function getList(int $accountId, string $listId): BulkSendList
    {
        $list = BulkSendList::where('list_id', $listId)
            ->where('account_id', $accountId)
            ->with(['recipients', 'createdBy'])
            ->first();

        if (!$list) {
            throw new ResourceNotFoundException('Bulk send list not found');
        }

        return $list;
    }

    /**
     * List bulk send lists with filters and pagination
     *
     * @param int $accountId
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function listLists(int $accountId, array $filters = []): LengthAwarePaginator
    {
        $query = BulkSendList::where('account_id', $accountId);

        // Search by list name
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Filter by creator
        if (!empty($filters['created_by'])) {
            $query->where('created_by_user_id', $filters['created_by']);
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Eager load relationships
        $query->with(['createdBy']);
        $query->withCount('recipients');

        // Paginate
        $perPage = $filters['per_page'] ?? 20;
        return $query->paginate($perPage);
    }

    /**
     * Update a bulk send list
     *
     * @param int $accountId
     * @param string $listId
     * @param array $data
     * @return BulkSendList
     * @throws ResourceNotFoundException
     * @throws ValidationException
     */
    public function updateList(int $accountId, string $listId, array $data): BulkSendList
    {
        $list = $this->getList($accountId, $listId);

        DB::beginTransaction();
        try {
            // Update list name if provided
            if (isset($data['list_name'])) {
                $list->list_name = $data['list_name'];
                $list->save();
            }

            // Update recipients if provided
            if (isset($data['recipients']) && is_array($data['recipients'])) {
                // Delete existing recipients
                BulkSendRecipient::where('list_id', $list->id)->delete();

                // Add new recipients
                $this->addRecipientsToList($list, $data['recipients']);
            }

            DB::commit();

            Log::info('Bulk send list updated', [
                'list_id' => $listId,
                'account_id' => $accountId,
            ]);

            return $list->fresh(['recipients']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update bulk send list', [
                'list_id' => $listId,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to update bulk send list');
        }
    }

    /**
     * Delete a bulk send list
     *
     * @param int $accountId
     * @param string $listId
     * @return bool
     * @throws ResourceNotFoundException
     */
    public function deleteList(int $accountId, string $listId): bool
    {
        $list = $this->getList($accountId, $listId);

        DB::beginTransaction();
        try {
            // Delete recipients first (cascade should handle this, but being explicit)
            BulkSendRecipient::where('list_id', $list->id)->delete();

            // Delete list
            $list->delete();

            DB::commit();

            Log::info('Bulk send list deleted', [
                'list_id' => $listId,
                'account_id' => $accountId,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete bulk send list', [
                'list_id' => $listId,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to delete bulk send list');
        }
    }

    /**
     * Send bulk envelopes using a list
     *
     * @param int $accountId
     * @param string $listId
     * @param array $data
     * @return BulkSendBatch
     * @throws ResourceNotFoundException
     * @throws ValidationException
     * @throws BusinessLogicException
     */
    public function sendBulkEnvelopes(int $accountId, string $listId, array $data): BulkSendBatch
    {
        $list = $this->getList($accountId, $listId);

        DB::beginTransaction();
        try {
            // Validate template or envelope
            if (empty($data['template_id']) && empty($data['envelope_id'])) {
                throw new ValidationException('Either template_id or envelope_id is required');
            }

            // Validate list has recipients
            $recipientCount = $list->recipients()->withValidEmail()->count();
            if ($recipientCount === 0) {
                throw new BusinessLogicException('List has no valid recipients');
            }

            // Create batch
            $batch = $this->createBatch($accountId, [
                'template_id' => $data['template_id'] ?? null,
                'envelope_id' => $data['envelope_id'] ?? null,
                'batch_name' => $data['batch_name'] ?? $list->list_name,
                'batch_size' => $recipientCount,
            ]);

            // Dispatch bulk send job to queue
            ProcessBulkSendBatchJob::dispatch($batch->id, $list->id);

            DB::commit();

            Log::info('Bulk send initiated', [
                'batch_id' => $batch->batch_id,
                'list_id' => $listId,
                'recipient_count' => $recipientCount,
            ]);

            return $batch->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to send bulk envelopes', [
                'list_id' => $listId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Test bulk send (validate without sending)
     *
     * @param int $accountId
     * @param string $listId
     * @param array $data
     * @return array
     * @throws ResourceNotFoundException
     * @throws ValidationException
     */
    public function testBulkSend(int $accountId, string $listId, array $data): array
    {
        $list = $this->getList($accountId, $listId);

        // Validate template or envelope
        if (empty($data['template_id']) && empty($data['envelope_id'])) {
            throw new ValidationException('Either template_id or envelope_id is required');
        }

        // Validate template exists if provided
        if (!empty($data['template_id'])) {
            $template = Template::where('template_id', $data['template_id'])
                ->where('account_id', $accountId)
                ->first();

            if (!$template) {
                throw new ResourceNotFoundException('Template not found');
            }
        }

        // Validate envelope exists if provided
        if (!empty($data['envelope_id'])) {
            $envelope = Envelope::where('envelope_id', $data['envelope_id'])
                ->where('account_id', $accountId)
                ->first();

            if (!$envelope) {
                throw new ResourceNotFoundException('Envelope not found');
            }
        }

        // Get recipient statistics
        $totalRecipients = $list->recipients()->count();
        $validRecipients = $list->recipients()->withValidEmail()->count();
        $invalidRecipients = $totalRecipients - $validRecipients;

        return [
            'can_send' => $validRecipients > 0,
            'total_recipients' => $totalRecipients,
            'valid_recipients' => $validRecipients,
            'invalid_recipients' => $invalidRecipients,
            'estimated_envelopes' => $validRecipients,
            'warnings' => $invalidRecipients > 0 ? [
                "{$invalidRecipients} recipient(s) have invalid email addresses and will be skipped"
            ] : [],
        ];
    }

    /**
     * Add recipients to a list
     *
     * @param BulkSendList $list
     * @param array $recipients
     * @return void
     * @throws ValidationException
     */
    protected function addRecipientsToList(BulkSendList $list, array $recipients): void
    {
        foreach ($recipients as $recipientData) {
            // Validate email if provided
            if (!empty($recipientData['recipient_email'])) {
                if (!filter_var($recipientData['recipient_email'], FILTER_VALIDATE_EMAIL)) {
                    throw new ValidationException("Invalid email address: {$recipientData['recipient_email']}");
                }
            }

            BulkSendRecipient::create([
                'list_id' => $list->id,
                'recipient_name' => $recipientData['recipient_name'] ?? null,
                'recipient_email' => $recipientData['recipient_email'] ?? null,
                'custom_fields' => $recipientData['custom_fields'] ?? null,
            ]);
        }
    }
}
