<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Http\Controllers\Api\V2_1\BaseController;
use App\Models\Account;
use App\Models\ChunkedUpload;
use App\Services\ChunkedUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Chunked Upload Controller
 *
 * Handles large file uploads by splitting them into chunks.
 * Supports resumable uploads and integrity verification.
 *
 * Endpoints:
 * - POST   /chunked_uploads                          - Initiate new upload
 * - GET    /chunked_uploads/{chunkedUploadId}       - Get upload metadata
 * - PUT    /chunked_uploads/{chunkedUploadId}       - Commit upload
 * - DELETE /chunked_uploads/{chunkedUploadId}       - Delete upload
 * - PUT    /chunked_uploads/{chunkedUploadId}/{seq} - Add chunk
 */
class ChunkedUploadController extends BaseController
{
    /**
     * Chunked upload service
     */
    protected ChunkedUploadService $uploadService;

    /**
     * Initialize controller
     */
    public function __construct(ChunkedUploadService $uploadService)
    {
        $this->uploadService = $uploadService;
    }

    /**
     * Initiate a new chunked upload
     *
     * POST /v2.1/accounts/{accountId}/chunked_uploads
     *
     * @param Request $request
     * @param string $accountId
     * @return JsonResponse
     */
    public function store(Request $request, string $accountId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'chunked_upload' => 'required|file|max:25000', // 25MB max per chunk
            'chunk_size' => 'nullable|integer|min:1048576|max:26214400', // 1MB - 25MB
            'max_chunks' => 'nullable|integer|min:1|max:10000',
            'expiration_hours' => 'nullable|integer|min:1|max:168', // 1 hour - 1 week
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $upload = $this->uploadService->initiateUpload(
                $account,
                $request->file('chunked_upload'),
                [
                    'chunk_size' => $request->input('chunk_size'),
                    'max_chunks' => $request->input('max_chunks'),
                    'expiration_hours' => $request->input('expiration_hours'),
                ]
            );

            return $this->created(
                $this->uploadService->getMetadata($upload),
                'Chunked upload initiated successfully'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get metadata for a chunked upload
     *
     * GET /v2.1/accounts/{accountId}/chunked_uploads/{chunkedUploadId}
     *
     * @param string $accountId
     * @param string $chunkedUploadId
     * @return JsonResponse
     */
    public function show(string $accountId, string $chunkedUploadId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $upload = ChunkedUpload::where('account_id', $account->id)
            ->where('chunked_upload_id', $chunkedUploadId)
            ->firstOrFail();

        return $this->success(
            $this->uploadService->getMetadata($upload),
            'Chunked upload retrieved successfully'
        );
    }

    /**
     * Commit a chunked upload (integrity check and finalize)
     *
     * PUT /v2.1/accounts/{accountId}/chunked_uploads/{chunkedUploadId}
     *
     * @param Request $request
     * @param string $accountId
     * @param string $chunkedUploadId
     * @return JsonResponse
     */
    public function update(Request $request, string $accountId, string $chunkedUploadId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $upload = ChunkedUpload::where('account_id', $account->id)
            ->where('chunked_upload_id', $chunkedUploadId)
            ->firstOrFail();

        try {
            $upload = $this->uploadService->commitUpload($upload);

            return $this->success(
                $this->uploadService->getMetadata($upload),
                'Chunked upload committed successfully'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Delete a chunked upload
     *
     * DELETE /v2.1/accounts/{accountId}/chunked_uploads/{chunkedUploadId}
     *
     * @param string $accountId
     * @param string $chunkedUploadId
     * @return JsonResponse
     */
    public function destroy(string $accountId, string $chunkedUploadId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $upload = ChunkedUpload::where('account_id', $account->id)
            ->where('chunked_upload_id', $chunkedUploadId)
            ->firstOrFail();

        try {
            $this->uploadService->deleteUpload($upload);

            return $this->noContent('Chunked upload deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Add a chunk to an existing upload
     *
     * PUT /v2.1/accounts/{accountId}/chunked_uploads/{chunkedUploadId}/{chunkedUploadPartSeq}
     *
     * @param Request $request
     * @param string $accountId
     * @param string $chunkedUploadId
     * @param string $chunkedUploadPartSeq
     * @return JsonResponse
     */
    public function addPart(
        Request $request,
        string $accountId,
        string $chunkedUploadId,
        string $chunkedUploadPartSeq
    ): JsonResponse {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $upload = ChunkedUpload::where('account_id', $account->id)
            ->where('chunked_upload_id', $chunkedUploadId)
            ->firstOrFail();

        $validator = Validator::make([
            'chunked_upload' => $request->file('chunked_upload'),
            'part_seq' => $chunkedUploadPartSeq,
        ], [
            'chunked_upload' => 'required|file|max:25000', // 25MB
            'part_seq' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $partSeq = (int) $chunkedUploadPartSeq;

            $upload = $this->uploadService->addChunk(
                $upload,
                $partSeq,
                $request->file('chunked_upload')
            );

            return $this->success(
                $this->uploadService->getMetadata($upload),
                'Chunk added successfully'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
