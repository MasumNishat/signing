<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Exceptions\Custom\BusinessLogicException;
use App\Exceptions\Custom\ResourceNotFoundException;
use App\Exceptions\Custom\ValidationException;
use App\Http\Controllers\Controller;
use App\Services\PowerFormService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * PowerFormController
 *
 * Handles PowerForm operations - public-facing forms for envelope creation.
 *
 * Endpoints:
 * - GET    /powerforms - List PowerForms
 * - POST   /powerforms - Create PowerForm
 * - GET    /powerforms/{powerformId} - Get PowerForm
 * - PUT    /powerforms/{powerformId} - Update PowerForm
 * - DELETE /powerforms/{powerformId} - Delete PowerForm
 * - GET    /powerforms/{powerformId}/submissions - Get submissions
 * - GET    /powerforms/{powerformId}/statistics - Get statistics
 * - POST   /public/powerforms/{powerformId}/submit - Submit PowerForm (public)
 */
class PowerFormController extends Controller
{
    /**
     * @var PowerFormService
     */
    protected PowerFormService $powerFormService;

    /**
     * Constructor
     */
    public function __construct(PowerFormService $powerFormService)
    {
        $this->powerFormService = $powerFormService;
    }

    /**
     * List PowerForms
     *
     * GET /api/v2.1/accounts/{accountId}/powerforms
     *
     * @param Request $request
     * @param string $accountId
     * @return JsonResponse
     */
    public function index(Request $request, string $accountId): JsonResponse
    {
        try {
            $account = $this->getAccountById($accountId);

            $filters = [
                'status' => $request->query('status'),
                'active_only' => $request->query('active_only', false),
                'search' => $request->query('search'),
                'sort_by' => $request->query('sort_by', 'created_at'),
                'sort_order' => $request->query('sort_order', 'desc'),
                'per_page' => $request->query('per_page', 20),
            ];

            $powerforms = $this->powerFormService->listPowerForms($account->id, $filters);

            return $this->paginatedResponse($powerforms, 'PowerForms retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Create PowerForm
     *
     * POST /api/v2.1/accounts/{accountId}/powerforms
     *
     * @param Request $request
     * @param string $accountId
     * @return JsonResponse
     */
    public function store(Request $request, string $accountId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'template_id' => 'required|string',
                'name' => 'required|string|max:255',
                'description' => 'sometimes|string|max:1000',
                'is_active' => 'sometimes|boolean',
                'email_subject' => 'sometimes|string|max:255',
                'email_message' => 'sometimes|string|max:2000',
                'send_email_to_sender' => 'sometimes|boolean',
                'sender_email' => 'sometimes|email|max:255',
                'sender_name' => 'sometimes|string|max:255',
                'max_uses' => 'sometimes|integer|min:1',
                'expiration_date' => 'sometimes|date|after:now',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $account = $this->getAccountById($accountId);
            $powerform = $this->powerFormService->createPowerForm($account->id, $request->all());

            return $this->successResponse([
                'powerform_id' => $powerform->powerform_id,
                'name' => $powerform->name,
                'status' => $powerform->status,
                'is_active' => $powerform->is_active,
                'public_url' => $powerform->getPublicUrl(),
                'template_id' => $powerform->template->template_id,
                'created_at' => $powerform->created_at->toIso8601String(),
            ], 'PowerForm created successfully', 201);

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get PowerForm
     *
     * GET /api/v2.1/accounts/{accountId}/powerforms/{powerformId}
     *
     * @param string $accountId
     * @param string $powerformId
     * @return JsonResponse
     */
    public function show(string $accountId, string $powerformId): JsonResponse
    {
        try {
            $account = $this->getAccountById($accountId);
            $powerform = $this->powerFormService->getPowerForm($account->id, $powerformId);

            $response = [
                'powerform_id' => $powerform->powerform_id,
                'name' => $powerform->name,
                'description' => $powerform->description,
                'status' => $powerform->status,
                'is_active' => $powerform->is_active,
                'template_id' => $powerform->template->template_id,
                'template_name' => $powerform->template->template_name,
                'email_subject' => $powerform->email_subject,
                'email_message' => $powerform->email_message,
                'send_email_to_sender' => $powerform->send_email_to_sender,
                'sender_email' => $powerform->sender_email,
                'sender_name' => $powerform->sender_name,
                'max_uses' => $powerform->max_uses,
                'times_used' => $powerform->times_used,
                'expiration_date' => $powerform->expiration_date?->toIso8601String(),
                'public_url' => $powerform->getPublicUrl(),
                'can_accept_submissions' => $powerform->canAcceptSubmissions(),
                'submission_count' => $powerform->submissions_count ?? 0,
                'created_at' => $powerform->created_at->toIso8601String(),
                'updated_at' => $powerform->updated_at->toIso8601String(),
            ];

            return $this->successResponse($response, 'PowerForm retrieved successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Update PowerForm
     *
     * PUT /api/v2.1/accounts/{accountId}/powerforms/{powerformId}
     *
     * @param Request $request
     * @param string $accountId
     * @param string $powerformId
     * @return JsonResponse
     */
    public function update(Request $request, string $accountId, string $powerformId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'description' => 'sometimes|string|max:1000',
                'is_active' => 'sometimes|boolean',
                'email_subject' => 'sometimes|string|max:255',
                'email_message' => 'sometimes|string|max:2000',
                'send_email_to_sender' => 'sometimes|boolean',
                'sender_email' => 'sometimes|email|max:255',
                'sender_name' => 'sometimes|string|max:255',
                'max_uses' => 'sometimes|integer|min:1',
                'expiration_date' => 'sometimes|date',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $account = $this->getAccountById($accountId);
            $powerform = $this->powerFormService->updatePowerForm(
                $account->id,
                $powerformId,
                $request->all()
            );

            return $this->successResponse([
                'powerform_id' => $powerform->powerform_id,
                'name' => $powerform->name,
                'status' => $powerform->status,
                'is_active' => $powerform->is_active,
            ], 'PowerForm updated successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Delete PowerForm
     *
     * DELETE /api/v2.1/accounts/{accountId}/powerforms/{powerformId}
     *
     * @param string $accountId
     * @param string $powerformId
     * @return JsonResponse
     */
    public function destroy(string $accountId, string $powerformId): JsonResponse
    {
        try {
            $account = $this->getAccountById($accountId);
            $this->powerFormService->deletePowerForm($account->id, $powerformId);

            return $this->noContentResponse();

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get PowerForm submissions
     *
     * GET /api/v2.1/accounts/{accountId}/powerforms/{powerformId}/submissions
     *
     * @param Request $request
     * @param string $accountId
     * @param string $powerformId
     * @return JsonResponse
     */
    public function submissions(Request $request, string $accountId, string $powerformId): JsonResponse
    {
        try {
            $account = $this->getAccountById($accountId);

            $filters = [
                'from_date' => $request->query('from_date'),
                'to_date' => $request->query('to_date'),
                'submitter_email' => $request->query('submitter_email'),
                'sort_by' => $request->query('sort_by', 'submitted_at'),
                'sort_order' => $request->query('sort_order', 'desc'),
                'per_page' => $request->query('per_page', 50),
            ];

            $submissions = $this->powerFormService->getPowerFormSubmissions(
                $account->id,
                $powerformId,
                $filters
            );

            return $this->paginatedResponse($submissions, 'PowerForm submissions retrieved successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get PowerForm statistics
     *
     * GET /api/v2.1/accounts/{accountId}/powerforms/{powerformId}/statistics
     *
     * @param string $accountId
     * @param string $powerformId
     * @return JsonResponse
     */
    public function statistics(string $accountId, string $powerformId): JsonResponse
    {
        try {
            $account = $this->getAccountById($accountId);
            $statistics = $this->powerFormService->getPowerFormStatistics($account->id, $powerformId);

            return $this->successResponse($statistics, 'PowerForm statistics retrieved successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Submit PowerForm (PUBLIC endpoint - no authentication required)
     *
     * POST /api/v2.1/public/powerforms/{powerformId}/submit
     *
     * @param Request $request
     * @param string $powerformId
     * @return JsonResponse
     */
    public function submit(Request $request, string $powerformId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'recipient_name' => 'required|string|max:255',
                'recipient_email' => 'required|email|max:255',
                'form_data' => 'sometimes|array',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $ipAddress = $request->ip();

            $submission = $this->powerFormService->submitPowerForm(
                $powerformId,
                $request->all(),
                $ipAddress
            );

            return $this->successResponse([
                'submission_id' => $submission->id,
                'envelope_id' => $submission->envelope->envelope_id,
                'submitter_name' => $submission->submitter_name,
                'submitter_email' => $submission->submitter_email,
                'submitted_at' => $submission->submitted_at->toIso8601String(),
                'message' => 'Your submission has been received. An envelope has been created and sent to your email.',
            ], 'PowerForm submitted successfully', 201);

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (ValidationException | BusinessLogicException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Helper: Get account by ID
     */
    protected function getAccountById(string $accountId): \App\Models\Account
    {
        $account = \App\Models\Account::where('account_id', $accountId)->first();

        if (!$account) {
            throw new ResourceNotFoundException('Account not found');
        }

        return $account;
    }

    /**
     * Helper: Success response
     */
    protected function successResponse($data, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], $code);
    }

    /**
     * Helper: Error response
     */
    protected function errorResponse(string $message, int $code = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $code);
    }

    /**
     * Helper: Not found response
     */
    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 404);
    }

    /**
     * Helper: Validation error response
     */
    protected function validationErrorResponse($errors): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors,
        ], 422);
    }

    /**
     * Helper: No content response
     */
    protected function noContentResponse(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * Helper: Paginated response
     */
    protected function paginatedResponse($paginator, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'message' => $message,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
