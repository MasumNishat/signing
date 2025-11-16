<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Http\Controllers\Api\BaseController;
use App\Models\Account;
use App\Services\BulkEnvelopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Bulk Envelope Controller
 *
 * Handles bulk envelope operations for efficiency and batch processing.
 *
 * Endpoints:
 * - PUT    /envelopes/bulk/status          - Bulk status update
 * - POST   /envelopes/bulk/void            - Bulk void envelopes
 * - POST   /envelopes/bulk/resend          - Bulk resend envelopes
 * - PUT    /envelopes/bulk/recipients      - Bulk recipient update
 * - POST   /envelopes/bulk/recipients/resend - Bulk resend to recipients
 * - DELETE /envelopes/bulk/recipients      - Bulk recipient remove
 * - POST   /envelopes/bulk/documents       - Bulk add documents
 * - PUT    /envelopes/bulk/documents       - Bulk replace documents
 * - DELETE /envelopes/bulk/documents       - Bulk delete documents
 * - POST   /envelopes/bulk/download        - Bulk document download
 */
class BulkEnvelopeController extends BaseController
{
    /**
     * Bulk envelope service
     */
    protected BulkEnvelopeService $bulkService;

    /**
     * Initialize controller
     */
    public function __construct(BulkEnvelopeService $bulkService)
    {
        $this->bulkService = $bulkService;
    }

    /**
     * Bulk status update
     *
     * PUT /v2.1/accounts/{accountId}/envelopes/bulk/status
     *
     * @param Request $request
     * @param string $accountId
     * @return JsonResponse
     */
    public function bulkStatusUpdate(Request $request, string $accountId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'envelope_ids' => 'required|array|min:1',
            'envelope_ids.*' => 'required|string',
            'status' => 'required|string|in:sent,voided,completed,declined',
            'void_reason' => 'required_if:status,voided|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $account = Account::findOrFail($accountId);

            $result = $this->bulkService->bulkStatusUpdate(
                $account,
                $request->input('envelope_ids'),
                $request->input('status'),
                $request->input('void_reason')
            );

            return $this->success($result, 'Bulk status update initiated');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Bulk void envelopes
     *
     * POST /v2.1/accounts/{accountId}/envelopes/bulk/void
     *
     * @param Request $request
     * @param string $accountId
     * @return JsonResponse
     */
    public function bulkVoid(Request $request, string $accountId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'envelope_ids' => 'required|array|min:1',
            'envelope_ids.*' => 'required|string',
            'void_reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $account = Account::findOrFail($accountId);

            $result = $this->bulkService->bulkVoid(
                $account,
                $request->input('envelope_ids'),
                $request->input('void_reason')
            );

            return $this->success($result, 'Bulk void operation initiated');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Bulk resend envelopes
     *
     * POST /v2.1/accounts/{accountId}/envelopes/bulk/resend
     *
     * @param Request $request
     * @param string $accountId
     * @return JsonResponse
     */
    public function bulkResend(Request $request, string $accountId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'envelope_ids' => 'required|array|min:1',
            'envelope_ids.*' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $account = Account::findOrFail($accountId);

            $result = $this->bulkService->bulkResend(
                $account,
                $request->input('envelope_ids')
            );

            return $this->success($result, 'Bulk resend operation initiated');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Bulk recipient update
     *
     * PUT /v2.1/accounts/{accountId}/envelopes/bulk/recipients
     *
     * @param Request $request
     * @param string $accountId
     * @return JsonResponse
     */
    public function bulkRecipientUpdate(Request $request, string $accountId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'updates' => 'required|array|min:1',
            'updates.*.envelope_id' => 'required|string',
            'updates.*.recipient_id' => 'required|string',
            'updates.*.name' => 'nullable|string|max:255',
            'updates.*.email' => 'nullable|email|max:255',
            'updates.*.routing_order' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $account = Account::findOrFail($accountId);

            $result = $this->bulkService->bulkRecipientUpdate(
                $account,
                $request->input('updates')
            );

            return $this->success($result, 'Bulk recipient update initiated');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Bulk resend to recipients
     *
     * POST /v2.1/accounts/{accountId}/envelopes/bulk/recipients/resend
     *
     * @param Request $request
     * @param string $accountId
     * @return JsonResponse
     */
    public function bulkRecipientResend(Request $request, string $accountId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'resends' => 'required|array|min:1',
            'resends.*.envelope_id' => 'required|string',
            'resends.*.recipient_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $account = Account::findOrFail($accountId);

            $result = $this->bulkService->bulkRecipientResend(
                $account,
                $request->input('resends')
            );

            return $this->success($result, 'Bulk recipient resend initiated');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Bulk recipient remove
     *
     * DELETE /v2.1/accounts/{accountId}/envelopes/bulk/recipients
     *
     * @param Request $request
     * @param string $accountId
     * @return JsonResponse
     */
    public function bulkRecipientRemove(Request $request, string $accountId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'removals' => 'required|array|min:1',
            'removals.*.envelope_id' => 'required|string',
            'removals.*.recipient_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $account = Account::findOrFail($accountId);

            $result = $this->bulkService->bulkRecipientRemove(
                $account,
                $request->input('removals')
            );

            return $this->success($result, 'Bulk recipient removal initiated');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Bulk add documents
     *
     * POST /v2.1/accounts/{accountId}/envelopes/bulk/documents
     *
     * @param Request $request
     * @param string $accountId
     * @return JsonResponse
     */
    public function bulkDocumentAdd(Request $request, string $accountId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'operations' => 'required|array|min:1',
            'operations.*.envelope_id' => 'required|string',
            'operations.*.documents' => 'required|array|min:1',
            'operations.*.documents.*.document_id' => 'nullable|string',
            'operations.*.documents.*.name' => 'required|string|max:255',
            'operations.*.documents.*.file_extension' => 'required|string|max:10',
            'operations.*.documents.*.order' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $account = Account::findOrFail($accountId);

            $result = $this->bulkService->bulkDocumentAdd(
                $account,
                $request->input('operations')
            );

            return $this->success($result, 'Bulk document add initiated');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Bulk replace documents
     *
     * PUT /v2.1/accounts/{accountId}/envelopes/bulk/documents
     *
     * @param Request $request
     * @param string $accountId
     * @return JsonResponse
     */
    public function bulkDocumentReplace(Request $request, string $accountId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'operations' => 'required|array|min:1',
            'operations.*.envelope_id' => 'required|string',
            'operations.*.document_id' => 'required|string',
            'operations.*.name' => 'nullable|string|max:255',
            'operations.*.order' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $account = Account::findOrFail($accountId);

            $result = $this->bulkService->bulkDocumentReplace(
                $account,
                $request->input('operations')
            );

            return $this->success($result, 'Bulk document replace initiated');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Bulk delete documents
     *
     * DELETE /v2.1/accounts/{accountId}/envelopes/bulk/documents
     *
     * @param Request $request
     * @param string $accountId
     * @return JsonResponse
     */
    public function bulkDocumentDelete(Request $request, string $accountId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'deletions' => 'required|array|min:1',
            'deletions.*.envelope_id' => 'required|string',
            'deletions.*.document_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $account = Account::findOrFail($accountId);

            $result = $this->bulkService->bulkDocumentDelete(
                $account,
                $request->input('deletions')
            );

            return $this->success($result, 'Bulk document deletion initiated');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Bulk document download
     *
     * POST /v2.1/accounts/{accountId}/envelopes/bulk/download
     *
     * @param Request $request
     * @param string $accountId
     * @return JsonResponse
     */
    public function bulkDocumentDownload(Request $request, string $accountId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'envelope_ids' => 'required|array|min:1',
            'envelope_ids.*' => 'required|string',
            'document_ids' => 'nullable|array',
            'document_ids.*' => 'string',
            'include_certificate' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $account = Account::findOrFail($accountId);

            $result = $this->bulkService->bulkDocumentDownload(
                $account,
                $request->input('envelope_ids'),
                $request->input('document_ids', []),
                $request->input('include_certificate', false)
            );

            return $this->success($result, 'Bulk download prepared successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
