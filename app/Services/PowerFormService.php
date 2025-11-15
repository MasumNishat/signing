<?php

namespace App\Services;

use App\Exceptions\Custom\BusinessLogicException;
use App\Exceptions\Custom\ResourceNotFoundException;
use App\Exceptions\Custom\ValidationException;
use App\Models\PowerForm;
use App\Models\PowerFormSubmission;
use App\Models\Template;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PowerFormService
 *
 * Handles business logic for PowerForms - public-facing forms
 * that allow envelope creation without authentication.
 */
class PowerFormService
{
    /**
     * @var TemplateService
     */
    protected TemplateService $templateService;

    /**
     * Constructor
     */
    public function __construct(TemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * Create a new PowerForm
     *
     * @param int $accountId
     * @param array $data
     * @return PowerForm
     * @throws ValidationException
     * @throws ResourceNotFoundException
     */
    public function createPowerForm(int $accountId, array $data): PowerForm
    {
        DB::beginTransaction();
        try {
            // Validate required fields
            if (empty($data['template_id'])) {
                throw new ValidationException('template_id is required');
            }

            if (empty($data['name'])) {
                throw new ValidationException('name is required');
            }

            // Validate template exists and belongs to account
            $template = Template::where('template_id', $data['template_id'])
                ->where('account_id', $accountId)
                ->first();

            if (!$template) {
                throw new ResourceNotFoundException('Template not found');
            }

            // Create PowerForm
            $powerform = PowerForm::create([
                'account_id' => $accountId,
                'template_id' => $template->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'email_subject' => $data['email_subject'] ?? null,
                'email_message' => $data['email_message'] ?? null,
                'send_email_to_sender' => $data['send_email_to_sender'] ?? false,
                'sender_email' => $data['sender_email'] ?? null,
                'sender_name' => $data['sender_name'] ?? null,
                'max_uses' => $data['max_uses'] ?? null,
                'expiration_date' => $data['expiration_date'] ?? null,
            ]);

            DB::commit();

            Log::info('PowerForm created', [
                'powerform_id' => $powerform->powerform_id,
                'account_id' => $accountId,
            ]);

            return $powerform->fresh(['template']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create PowerForm', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get a PowerForm by ID
     *
     * @param int $accountId
     * @param string $powerformId
     * @return PowerForm
     * @throws ResourceNotFoundException
     */
    public function getPowerForm(int $accountId, string $powerformId): PowerForm
    {
        $powerform = PowerForm::where('powerform_id', $powerformId)
            ->where('account_id', $accountId)
            ->with(['template', 'submissions'])
            ->withCount('submissions')
            ->first();

        if (!$powerform) {
            throw new ResourceNotFoundException('PowerForm not found');
        }

        return $powerform;
    }

    /**
     * Get a PowerForm by ID (public access - no account check)
     *
     * @param string $powerformId
     * @return PowerForm
     * @throws ResourceNotFoundException
     * @throws BusinessLogicException
     */
    public function getPowerFormPublic(string $powerformId): PowerForm
    {
        $powerform = PowerForm::where('powerform_id', $powerformId)
            ->with(['template'])
            ->first();

        if (!$powerform) {
            throw new ResourceNotFoundException('PowerForm not found');
        }

        if (!$powerform->canAcceptSubmissions()) {
            if ($powerform->isExpired()) {
                throw new BusinessLogicException('This PowerForm has expired');
            } elseif ($powerform->hasReachedMaxUses()) {
                throw new BusinessLogicException('This PowerForm has reached its maximum number of submissions');
            } else {
                throw new BusinessLogicException('This PowerForm is not currently accepting submissions');
            }
        }

        return $powerform;
    }

    /**
     * List PowerForms with filters and pagination
     *
     * @param int $accountId
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function listPowerForms(int $accountId, array $filters = []): LengthAwarePaginator
    {
        $query = PowerForm::where('account_id', $accountId);

        // Filter by status
        if (!empty($filters['status'])) {
            $query->withStatus($filters['status']);
        }

        // Filter active only
        if (!empty($filters['active_only'])) {
            $query->active();
        }

        // Search by name/description
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Eager load relationships
        $query->with(['template']);
        $query->withCount('submissions');

        // Paginate
        $perPage = $filters['per_page'] ?? 20;
        return $query->paginate($perPage);
    }

    /**
     * Update a PowerForm
     *
     * @param int $accountId
     * @param string $powerformId
     * @param array $data
     * @return PowerForm
     * @throws ResourceNotFoundException
     * @throws ValidationException
     */
    public function updatePowerForm(int $accountId, string $powerformId, array $data): PowerForm
    {
        $powerform = $this->getPowerForm($accountId, $powerformId);

        DB::beginTransaction();
        try {
            // Update allowed fields
            $allowedFields = [
                'name',
                'description',
                'is_active',
                'email_subject',
                'email_message',
                'send_email_to_sender',
                'sender_email',
                'sender_name',
                'max_uses',
                'expiration_date',
            ];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $powerform->$field = $data[$field];
                }
            }

            // Update status based on is_active
            if (isset($data['is_active'])) {
                if ($data['is_active']) {
                    $powerform->activate();
                } else {
                    $powerform->markAsDisabled();
                }
            }

            $powerform->save();

            DB::commit();

            Log::info('PowerForm updated', [
                'powerform_id' => $powerformId,
                'account_id' => $accountId,
            ]);

            return $powerform->fresh(['template']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update PowerForm', [
                'powerform_id' => $powerformId,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to update PowerForm');
        }
    }

    /**
     * Delete a PowerForm
     *
     * @param int $accountId
     * @param string $powerformId
     * @return bool
     * @throws ResourceNotFoundException
     */
    public function deletePowerForm(int $accountId, string $powerformId): bool
    {
        $powerform = $this->getPowerForm($accountId, $powerformId);

        DB::beginTransaction();
        try {
            $powerform->delete();

            DB::commit();

            Log::info('PowerForm deleted', [
                'powerform_id' => $powerformId,
                'account_id' => $accountId,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete PowerForm', [
                'powerform_id' => $powerformId,
                'error' => $e->getMessage(),
            ]);
            throw new BusinessLogicException('Failed to delete PowerForm');
        }
    }

    /**
     * Submit a PowerForm (public endpoint)
     *
     * @param string $powerformId
     * @param array $data
     * @param string|null $ipAddress
     * @return PowerFormSubmission
     * @throws ResourceNotFoundException
     * @throws BusinessLogicException
     * @throws ValidationException
     */
    public function submitPowerForm(string $powerformId, array $data, ?string $ipAddress = null): PowerFormSubmission
    {
        $powerform = $this->getPowerFormPublic($powerformId);

        DB::beginTransaction();
        try {
            // Validate recipient data
            if (empty($data['recipient_email'])) {
                throw new ValidationException('recipient_email is required');
            }

            if (empty($data['recipient_name'])) {
                throw new ValidationException('recipient_name is required');
            }

            // Create envelope from template
            $envelopeData = [
                'sender_user_id' => $powerform->template->owner_user_id,
                'email_subject' => $powerform->email_subject ?? $powerform->template->template_name,
                'email_message' => $powerform->email_message ?? null,
                'recipients' => [
                    [
                        'name' => $data['recipient_name'],
                        'email' => $data['recipient_email'],
                        'role_name' => 'Signer',
                    ],
                ],
            ];

            $envelope = $this->templateService->createEnvelopeFromTemplate(
                $powerform->template,
                $envelopeData
            );

            // Create submission record
            $submission = PowerFormSubmission::create([
                'powerform_id' => $powerform->id,
                'envelope_id' => $envelope->id,
                'submitter_name' => $data['recipient_name'],
                'submitter_email' => $data['recipient_email'],
                'submitter_ip_address' => $ipAddress,
                'form_data' => $data['form_data'] ?? null,
                'submitted_at' => now(),
            ]);

            // Increment PowerForm usage count
            $powerform->incrementUsageCount();

            // Send email notification if configured
            if ($powerform->send_email_to_sender && $powerform->sender_email) {
                // In production, dispatch email notification job
                Log::info('PowerForm submission email notification', [
                    'powerform_id' => $powerformId,
                    'sender_email' => $powerform->sender_email,
                ]);
            }

            DB::commit();

            Log::info('PowerForm submitted', [
                'powerform_id' => $powerformId,
                'envelope_id' => $envelope->envelope_id,
                'submitter_email' => $data['recipient_email'],
            ]);

            return $submission->fresh(['envelope', 'powerform']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to submit PowerForm', [
                'powerform_id' => $powerformId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get PowerForm submissions
     *
     * @param int $accountId
     * @param string $powerformId
     * @param array $filters
     * @return LengthAwarePaginator
     * @throws ResourceNotFoundException
     */
    public function getPowerFormSubmissions(
        int $accountId,
        string $powerformId,
        array $filters = []
    ): LengthAwarePaginator {
        $powerform = $this->getPowerForm($accountId, $powerformId);

        $query = PowerFormSubmission::where('powerform_id', $powerform->id);

        // Filter by date range
        if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
            $query->submittedBetween($filters['from_date'], $filters['to_date']);
        }

        // Filter by submitter email
        if (!empty($filters['submitter_email'])) {
            $query->bySubmitter($filters['submitter_email']);
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'submitted_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Eager load relationships
        $query->with(['envelope']);

        // Paginate
        $perPage = $filters['per_page'] ?? 50;
        return $query->paginate($perPage);
    }

    /**
     * Get PowerForm statistics
     *
     * @param int $accountId
     * @param string $powerformId
     * @return array
     * @throws ResourceNotFoundException
     */
    public function getPowerFormStatistics(int $accountId, string $powerformId): array
    {
        $powerform = $this->getPowerForm($accountId, $powerformId);

        $totalSubmissions = $powerform->submissions()->count();
        $submissionsLast24h = $powerform->submissions()
            ->where('submitted_at', '>=', now()->subDay())
            ->count();
        $submissionsLast7Days = $powerform->submissions()
            ->where('submitted_at', '>=', now()->subDays(7))
            ->count();
        $submissionsLast30Days = $powerform->submissions()
            ->where('submitted_at', '>=', now()->subDays(30))
            ->count();

        return [
            'powerform_id' => $powerform->powerform_id,
            'name' => $powerform->name,
            'status' => $powerform->status,
            'is_active' => $powerform->isActive(),
            'can_accept_submissions' => $powerform->canAcceptSubmissions(),
            'times_used' => $powerform->times_used,
            'max_uses' => $powerform->max_uses,
            'usage_percentage' => $powerform->max_uses
                ? round(($powerform->times_used / $powerform->max_uses) * 100, 2)
                : null,
            'total_submissions' => $totalSubmissions,
            'submissions_last_24h' => $submissionsLast24h,
            'submissions_last_7_days' => $submissionsLast7Days,
            'submissions_last_30_days' => $submissionsLast30Days,
            'expiration_date' => $powerform->expiration_date?->toIso8601String(),
            'is_expired' => $powerform->isExpired(),
            'public_url' => $powerform->getPublicUrl(),
        ];
    }
}
