<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Exceptions\Custom\BusinessLogicException;
use App\Exceptions\Custom\ResourceNotFoundException;
use App\Exceptions\Custom\ValidationException;
use App\Http\Controllers\Api\BaseController;
use App\Services\WorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * WorkspaceController
 *
 * Handles workspace operations including workspace CRUD, folder management,
 * and file operations.
 *
 * Endpoints: 11 total
 * - Workspace CRUD: 5 endpoints
 * - Folder operations: 2 endpoints
 * - File operations: 4 endpoints
 */
class WorkspaceController extends BaseController
{
    protected WorkspaceService $workspaceService;

    public function __construct(WorkspaceService $workspaceService)
    {
        $this->workspaceService = $workspaceService;
    }

    // =========================================================================
    // WORKSPACE CRUD (5 endpoints)
    // =========================================================================

    /**
     * GET /accounts/{accountId}/workspaces
     * List workspaces for account
     */
    public function index(Request $request, int $accountId): JsonResponse
    {
        try {
            $filters = [
                'status' => $request->input('status'),
                'search' => $request->input('search'),
                'sort_by' => $request->input('sort_by', 'created_at'),
                'sort_order' => $request->input('sort_order', 'desc'),
                'per_page' => $request->input('per_page', 20),
            ];

            $workspaces = $this->workspaceService->listWorkspaces($accountId, $filters);

            return $this->paginatedResponse($workspaces, 'Workspaces retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to list workspaces', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve workspaces', 500);
        }
    }

    /**
     * POST /accounts/{accountId}/workspaces
     * Create a workspace
     */
    public function store(Request $request, int $accountId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'workspace_name' => 'required|string|max:255',
                'workspace_description' => 'nullable|string',
                'workspace_uri' => 'nullable|string|max:500',
                'created_by_user_id' => 'nullable|integer|exists:users,id',
                'status' => 'nullable|string|in:active,archived',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $workspace = $this->workspaceService->createWorkspace($accountId, $request->all());

            return $this->createdResponse($workspace, 'Workspace created successfully');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Failed to create workspace', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to create workspace', 500);
        }
    }

    /**
     * GET /accounts/{accountId}/workspaces/{workspaceId}
     * Get a workspace
     */
    public function show(int $accountId, string $workspaceId): JsonResponse
    {
        try {
            $workspace = $this->workspaceService->getWorkspace($accountId, $workspaceId);

            return $this->successResponse($workspace, 'Workspace retrieved successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Failed to get workspace', [
                'account_id' => $accountId,
                'workspace_id' => $workspaceId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve workspace', 500);
        }
    }

    /**
     * PUT /accounts/{accountId}/workspaces/{workspaceId}
     * Update a workspace
     */
    public function update(Request $request, int $accountId, string $workspaceId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'workspace_name' => 'sometimes|string|max:255',
                'workspace_description' => 'sometimes|string|nullable',
                'workspace_uri' => 'sometimes|string|max:500|nullable',
                'status' => 'sometimes|string|in:active,archived',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $workspace = $this->workspaceService->updateWorkspace($accountId, $workspaceId, $request->all());

            return $this->successResponse($workspace, 'Workspace updated successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Failed to update workspace', [
                'account_id' => $accountId,
                'workspace_id' => $workspaceId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to update workspace', 500);
        }
    }

    /**
     * DELETE /accounts/{accountId}/workspaces/{workspaceId}
     * Delete a workspace
     */
    public function destroy(int $accountId, string $workspaceId): JsonResponse
    {
        try {
            $this->workspaceService->deleteWorkspace($accountId, $workspaceId);

            return $this->noContentResponse('Workspace deleted successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (BusinessLogicException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('Failed to delete workspace', [
                'account_id' => $accountId,
                'workspace_id' => $workspaceId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to delete workspace', 500);
        }
    }

    // =========================================================================
    // FOLDER OPERATIONS (2 endpoints)
    // =========================================================================

    /**
     * GET /accounts/{accountId}/workspaces/{workspaceId}/folders/{folderId}
     * List folder contents
     */
    public function showFolder(int $accountId, string $workspaceId, string $folderId): JsonResponse
    {
        try {
            $contents = $this->workspaceService->listFolderContents($accountId, $workspaceId, $folderId);

            return $this->successResponse($contents, 'Folder contents retrieved successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Failed to get folder contents', [
                'account_id' => $accountId,
                'workspace_id' => $workspaceId,
                'folder_id' => $folderId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve folder contents', 500);
        }
    }

    /**
     * DELETE /accounts/{accountId}/workspaces/{workspaceId}/folders/{folderId}
     * Delete folder and its contents
     */
    public function destroyFolder(int $accountId, string $workspaceId, string $folderId): JsonResponse
    {
        try {
            $this->workspaceService->deleteFolder($accountId, $workspaceId, $folderId);

            return $this->noContentResponse('Folder deleted successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (BusinessLogicException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('Failed to delete folder', [
                'account_id' => $accountId,
                'workspace_id' => $workspaceId,
                'folder_id' => $folderId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to delete folder', 500);
        }
    }

    // =========================================================================
    // FILE OPERATIONS (4 endpoints)
    // =========================================================================

    /**
     * POST /accounts/{accountId}/workspaces/{workspaceId}/folders/{folderId}/files
     * Create a workspace file (upload)
     */
    public function storeFile(Request $request, int $accountId, string $workspaceId, string $folderId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|max:51200', // 50MB max
                'file_name' => 'nullable|string|max:255',
                'created_by_user_id' => 'nullable|integer|exists:users,id',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $file = $this->workspaceService->createFile(
                $accountId,
                $workspaceId,
                $folderId,
                $request->file('file'),
                $request->only(['file_name', 'created_by_user_id'])
            );

            return $this->createdResponse($file, 'File uploaded successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Failed to upload file', [
                'account_id' => $accountId,
                'workspace_id' => $workspaceId,
                'folder_id' => $folderId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to upload file', 500);
        }
    }

    /**
     * GET /accounts/{accountId}/workspaces/{workspaceId}/folders/{folderId}/files/{fileId}
     * Get workspace file
     */
    public function showFile(int $accountId, string $workspaceId, string $folderId, string $fileId): JsonResponse
    {
        try {
            $file = $this->workspaceService->getFile($accountId, $workspaceId, $folderId, $fileId);

            $response = [
                'file_id' => $file->file_id,
                'file_name' => $file->file_name,
                'file_size' => $file->file_size,
                'file_size_formatted' => $file->getFileSizeFormatted(),
                'content_type' => $file->content_type,
                'file_url' => $file->getFileUrl(),
                'created_at' => $file->created_at->toIso8601String(),
                'updated_at' => $file->updated_at->toIso8601String(),
                'created_by' => $file->createdBy ? [
                    'id' => $file->createdBy->id,
                    'name' => $file->createdBy->name,
                    'email' => $file->createdBy->email,
                ] : null,
                'folder' => [
                    'folder_id' => $file->folder->folder_id,
                    'folder_name' => $file->folder->folder_name,
                ],
            ];

            return $this->successResponse($response, 'File retrieved successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Failed to get file', [
                'account_id' => $accountId,
                'workspace_id' => $workspaceId,
                'folder_id' => $folderId,
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve file', 500);
        }
    }

    /**
     * PUT /accounts/{accountId}/workspaces/{workspaceId}/folders/{folderId}/files/{fileId}
     * Update workspace file metadata
     */
    public function updateFile(
        Request $request,
        int $accountId,
        string $workspaceId,
        string $folderId,
        string $fileId
    ): JsonResponse {
        try {
            $validator = Validator::make($request->all(), [
                'file_name' => 'sometimes|string|max:255',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $file = $this->workspaceService->updateFile(
                $accountId,
                $workspaceId,
                $folderId,
                $fileId,
                $request->all()
            );

            return $this->successResponse($file, 'File updated successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Failed to update file', [
                'account_id' => $accountId,
                'workspace_id' => $workspaceId,
                'folder_id' => $folderId,
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to update file', 500);
        }
    }

    /**
     * GET /accounts/{accountId}/workspaces/{workspaceId}/folders/{folderId}/files/{fileId}/pages
     * Get file pages (for preview)
     */
    public function showFilePages(
        int $accountId,
        string $workspaceId,
        string $folderId,
        string $fileId
    ): JsonResponse {
        try {
            $pages = $this->workspaceService->getFilePages($accountId, $workspaceId, $folderId, $fileId);

            return $this->successResponse($pages, 'File pages retrieved successfully');

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Failed to get file pages', [
                'account_id' => $accountId,
                'workspace_id' => $workspaceId,
                'folder_id' => $folderId,
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve file pages', 500);
        }
    }
}
