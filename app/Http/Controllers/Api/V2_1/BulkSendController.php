<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Exceptions\Custom\BusinessLogicException;
use App\Exceptions\Custom\ResourceNotFoundException;
use App\Exceptions\Custom\ValidationException;
use App\Http\Controllers\Controller;
use App\Services\BulkSendService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * BulkSendController
 *
 * Handles bulk envelope sending operations.
 * Manages bulk send batches and recipient lists.
 *
 * Endpoints:
 * - GET    /bulk_send_batch - List batches
 * - GET    /bulk_send_batch/{batchId} - Get batch
 * - PUT    /bulk_send_batch/{batchId} - Update batch
 * - GET    /bulk_send_batch/{batchId}/envelopes - Get batch envelopes
 * - PUT    /bulk_send_batch/{batchId}/{action} - Batch actions
 * - GET    /bulk_send_lists - List lists
 * - POST   /bulk_send_lists - Create list
 * - GET    /bulk_send_lists/{listId} - Get list
 * - PUT    /bulk_send_lists/{listId} - Update list
 * - DELETE /bulk_send_lists/{listId} - Delete list
 * - POST   /bulk_send_lists/{listId}/send - Send bulk envelopes
 * - POST   /bulk_send_lists/{listId}/test - Test bulk send
 */
class BulkSendController extends Controller
{
    /**
     * @var BulkSendService
     */
    protected BulkSendService $bulkSendService;

    /**
     * Constructor
     */
    public function __construct(BulkSendService $bulkSendService)
    {
        $this->bulkSendService = $bulkSendService;
    }

    /**
     * List bulk send batches
     *
     * GET /api/v2.1/accounts/{accountId}/bulk_send_batch
     *
     * @param Request $request
     * @param string $accountId
     * @return JsonResponse
     */
    public function listBatches(Request $request, string $accountId): JsonResponse
    {
        try {
            $account = $this->getAccountById($accountId);

            $filters = [
                'status' => $request->query('status'),
                'from_date' => $request->query('from_date'),
                'to_date' => $request->query('to_date'),
                'search' => $request->query('search'),
                'sort_by' => $request->query('sort_by', 'submitted_date_time'),
                'sort_order' => $request->query('sort_order', 'desc'),
                'per_page' => $request->query('per_page', 20),
            ];

            $batches = $this->bulkSendService->listBatches($account->id, $filters);

            return $this->paginatedResponse($batches, 'Bulk send batches retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get bulk send batch
     *
     * GET /api/v2.1/accounts/{accountId}/bulk_send_batch/{batchId}
     *
     * @param string $accountId
     * @param string $batchId
     * @return JsonResponse
     */
    public function getBatch(string $accountId, string $batchId): JsonResponse
    {
        try {
            $account = $this->getAccountById($accountId);
            $batch = $this->bulkSendService->getBatch($account->id, $batchId);

            $response = [
                'batch_id' => $batch->batch_id,
                'batch_name' => $batch->batch_name,
                'status' => $batch->status,
                'batch_size' => $batch->batch_size,
                'envelopes_sent' => $batch->envelopes_sent,
                'envelopes_failed' => $batch->envelopes_failed,
                'progress_percentage' => $batch->getProgressPercentage(),
                'template_id' => $batch->template?->template_id,
                'envelope_id' => $batch->envelope?->envelope_id,
                'submitted_date_time' => $batch->submitted_date_time?->toIso8601String(),
                'completed_date_time' => $batch->completed_date_time?->toIso8601String(),
            ];

            return $this->successResponse($response, 'Bulk send batch retrieved successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Update bulk send batch
     *
     * PUT /api/v2.1/accounts/{accountId}/bulk_send_batch/{batchId}
     *
     * @param Request $request
     * @param string $accountId
     * @param string $batchId
     * @return JsonResponse
     */
    public function updateBatch(Request $request, string $accountId, string $batchId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'batch_name' => 'sometimes|string|max:255',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $account = $this->getAccountById($accountId);
            $batch = $this->bulkSendService->updateBatch($account->id, $batchId, $request->all());

            return $this->successResponse([
                'batch_id' => $batch->batch_id,
                'batch_name' => $batch->batch_name,
                'status' => $batch->status,
            ], 'Bulk send batch updated successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (BusinessLogicException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get batch envelopes
     *
     * GET /api/v2.1/accounts/{accountId}/bulk_send_batch/{batchId}/envelopes
     *
     * @param Request $request
     * @param string $accountId
     * @param string $batchId
     * @return JsonResponse
     */
    public function getBatchEnvelopes(Request $request, string $accountId, string $batchId): JsonResponse
    {
        try {
            $account = $this->getAccountById($accountId);

            $filters = [
                'status' => $request->query('status'),
                'sort_by' => $request->query('sort_by', 'created_at'),
                'sort_order' => $request->query('sort_order', 'desc'),
                'per_page' => $request->query('per_page', 50),
            ];

            $envelopes = $this->bulkSendService->getBatchEnvelopes($account->id, $batchId, $filters);

            return $this->paginatedResponse($envelopes, 'Batch envelopes retrieved successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Perform batch action
     *
     * PUT /api/v2.1/accounts/{accountId}/bulk_send_batch/{batchId}/{action}
     *
     * @param string $accountId
     * @param string $batchId
     * @param string $action
     * @return JsonResponse
     */
    public function performBatchAction(string $accountId, string $batchId, string $action): JsonResponse
    {
        try {
            $account = $this->getAccountById($accountId);
            $batch = $this->bulkSendService->performBatchAction($account->id, $batchId, $action);

            return $this->successResponse([
                'batch_id' => $batch->batch_id,
                'status' => $batch->status,
                'action' => $action,
            ], "Batch action '{$action}' performed successfully");

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (BusinessLogicException | ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * List bulk send lists
     *
     * GET /api/v2.1/accounts/{accountId}/bulk_send_lists
     *
     * @param Request $request
     * @param string $accountId
     * @return JsonResponse
     */
    public function listLists(Request $request, string $accountId): JsonResponse
    {
        try {
            $account = $this->getAccountById($accountId);

            $filters = [
                'search' => $request->query('search'),
                'created_by' => $request->query('created_by'),
                'sort_by' => $request->query('sort_by', 'created_at'),
                'sort_order' => $request->query('sort_order', 'desc'),
                'per_page' => $request->query('per_page', 20),
            ];

            $lists = $this->bulkSendService->listLists($account->id, $filters);

            return $this->paginatedResponse($lists, 'Bulk send lists retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Create bulk send list
     *
     * POST /api/v2.1/accounts/{accountId}/bulk_send_lists
     *
     * @param Request $request
     * @param string $accountId
     * @return JsonResponse
     */
    public function createList(Request $request, string $accountId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'list_name' => 'required|string|max:255',
                'recipients' => 'sometimes|array',
                'recipients.*.recipient_name' => 'required|string|max:255',
                'recipients.*.recipient_email' => 'required|email|max:255',
                'recipients.*.custom_fields' => 'sometimes|array',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $account = $this->getAccountById($accountId);
            $user = auth()->user();

            $list = $this->bulkSendService->createList(
                $account->id,
                $user->id,
                $request->all()
            );

            return $this->successResponse([
                'list_id' => $list->list_id,
                'list_name' => $list->list_name,
                'recipient_count' => $list->recipients->count(),
                'created_at' => $list->created_at->toIso8601String(),
            ], 'Bulk send list created successfully', 201);

        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get bulk send list
     *
     * GET /api/v2.1/accounts/{accountId}/bulk_send_lists/{listId}
     *
     * @param string $accountId
     * @param string $listId
     * @return JsonResponse
     */
    public function getList(string $accountId, string $listId): JsonResponse
    {
        try {
            $account = $this->getAccountById($accountId);
            $list = $this->bulkSendService->getList($account->id, $listId);

            $response = [
                'list_id' => $list->list_id,
                'list_name' => $list->list_name,
                'created_by' => [
                    'user_id' => $list->createdBy->id,
                    'name' => $list->createdBy->name,
                    'email' => $list->createdBy->email,
                ],
                'recipient_count' => $list->recipients->count(),
                'recipients' => $list->recipients->map(function ($recipient) {
                    return [
                        'id' => $recipient->id,
                        'recipient_name' => $recipient->recipient_name,
                        'recipient_email' => $recipient->recipient_email,
                        'custom_fields' => $recipient->custom_fields,
                    ];
                }),
                'created_at' => $list->created_at->toIso8601String(),
            ];

            return $this->successResponse($response, 'Bulk send list retrieved successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Update bulk send list
     *
     * PUT /api/v2.1/accounts/{accountId}/bulk_send_lists/{listId}
     *
     * @param Request $request
     * @param string $accountId
     * @param string $listId
     * @return JsonResponse
     */
    public function updateList(Request $request, string $accountId, string $listId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'list_name' => 'sometimes|string|max:255',
                'recipients' => 'sometimes|array',
                'recipients.*.recipient_name' => 'required|string|max:255',
                'recipients.*.recipient_email' => 'required|email|max:255',
                'recipients.*.custom_fields' => 'sometimes|array',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $account = $this->getAccountById($accountId);
            $list = $this->bulkSendService->updateList($account->id, $listId, $request->all());

            return $this->successResponse([
                'list_id' => $list->list_id,
                'list_name' => $list->list_name,
                'recipient_count' => $list->recipients->count(),
            ], 'Bulk send list updated successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Delete bulk send list
     *
     * DELETE /api/v2.1/accounts/{accountId}/bulk_send_lists/{listId}
     *
     * @param string $accountId
     * @param string $listId
     * @return JsonResponse
     */
    public function deleteList(string $accountId, string $listId): JsonResponse
    {
        try {
            $account = $this->getAccountById($accountId);
            $this->bulkSendService->deleteList($account->id, $listId);

            return $this->noContentResponse();

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Send bulk envelopes using a list
     *
     * POST /api/v2.1/accounts/{accountId}/bulk_send_lists/{listId}/send
     *
     * @param Request $request
     * @param string $accountId
     * @param string $listId
     * @return JsonResponse
     */
    public function sendBulkEnvelopes(Request $request, string $accountId, string $listId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'template_id' => 'required_without:envelope_id|string',
                'envelope_id' => 'required_without:template_id|string',
                'batch_name' => 'sometimes|string|max:255',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $account = $this->getAccountById($accountId);
            $batch = $this->bulkSendService->sendBulkEnvelopes($account->id, $listId, $request->all());

            return $this->successResponse([
                'batch_id' => $batch->batch_id,
                'batch_name' => $batch->batch_name,
                'status' => $batch->status,
                'batch_size' => $batch->batch_size,
                'submitted_date_time' => $batch->submitted_date_time->toIso8601String(),
            ], 'Bulk send initiated successfully', 201);

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (ValidationException | BusinessLogicException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Test bulk send (validate without sending)
     *
     * POST /api/v2.1/accounts/{accountId}/bulk_send_lists/{listId}/test
     *
     * @param Request $request
     * @param string $accountId
     * @param string $listId
     * @return JsonResponse
     */
    public function testBulkSend(Request $request, string $accountId, string $listId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'template_id' => 'required_without:envelope_id|string',
                'envelope_id' => 'required_without:template_id|string',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $account = $this->getAccountById($accountId);
            $result = $this->bulkSendService->testBulkSend($account->id, $listId, $request->all());

            return $this->successResponse($result, 'Bulk send test completed successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Helper: Get account by ID
     *
     * @param string $accountId
     * @return \App\Models\Account
     * @throws ResourceNotFoundException
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
