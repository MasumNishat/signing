<?php

namespace App\Services;

use App\Exceptions\Custom\BusinessLogicException;
use App\Exceptions\Custom\ResourceNotFoundException;
use App\Exceptions\Custom\ValidationException;
use App\Models\Workspace;
use App\Models\WorkspaceFile;
use App\Models\WorkspaceFolder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * WorkspaceService
 *
 * Handles business logic for workspace operations including:
 * - Workspace CRUD
 * - Folder management (with hierarchical structure)
 * - File management and uploads
 * - File page retrieval
 */
class WorkspaceService
{
    /**
     * List workspaces for an account.
     */
    public function listWorkspaces(int $accountId, array $filters = []): LengthAwarePaginator
    {
        $query = Workspace::query()
            ->where('account_id', $accountId)
            ->with(['createdBy:id,name,email']);

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Search by name or description
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $filters['per_page'] ?? 20;
        return $query->paginate($perPage);
    }

    /**
     * Create a new workspace.
     */
    public function createWorkspace(int $accountId, array $data): Workspace
    {
        if (empty($data['workspace_name'])) {
            throw new ValidationException('Workspace name is required');
        }

        DB::beginTransaction();
        try {
            $workspace = Workspace::create([
                'account_id' => $accountId,
                'workspace_name' => $data['workspace_name'],
                'workspace_description' => $data['workspace_description'] ?? null,
                'workspace_uri' => $data['workspace_uri'] ?? null,
                'created_by_user_id' => $data['created_by_user_id'] ?? null,
                'status' => $data['status'] ?? Workspace::STATUS_ACTIVE,
            ]);

            // Create a default root folder
            WorkspaceFolder::create([
                'workspace_id' => $workspace->id,
                'folder_name' => 'Root',
                'parent_folder_id' => null,
            ]);

            DB::commit();
            return $workspace->fresh(['createdBy', 'folders']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw new BusinessLogicException('Failed to create workspace: ' . $e->getMessage());
        }
    }

    /**
     * Get a workspace by ID.
     */
    public function getWorkspace(int $accountId, string $workspaceId): Workspace
    {
        $workspace = Workspace::where('workspace_id', $workspaceId)
            ->where('account_id', $accountId)
            ->with(['createdBy:id,name,email', 'rootFolders'])
            ->first();

        if (!$workspace) {
            throw new ResourceNotFoundException('Workspace not found');
        }

        return $workspace;
    }

    /**
     * Update a workspace.
     */
    public function updateWorkspace(int $accountId, string $workspaceId, array $data): Workspace
    {
        $workspace = $this->getWorkspace($accountId, $workspaceId);

        $updateData = array_filter([
            'workspace_name' => $data['workspace_name'] ?? null,
            'workspace_description' => $data['workspace_description'] ?? null,
            'workspace_uri' => $data['workspace_uri'] ?? null,
            'status' => $data['status'] ?? null,
        ], fn($value) => $value !== null);

        if (empty($updateData)) {
            throw new ValidationException('No valid fields to update');
        }

        $workspace->update($updateData);

        return $workspace->fresh(['createdBy', 'rootFolders']);
    }

    /**
     * Delete a workspace.
     */
    public function deleteWorkspace(int $accountId, string $workspaceId): bool
    {
        $workspace = $this->getWorkspace($accountId, $workspaceId);

        DB::beginTransaction();
        try {
            // Delete all files in all folders
            foreach ($workspace->folders as $folder) {
                foreach ($folder->files as $file) {
                    $this->deleteFileStorage($file);
                }
            }

            // Cascade delete will handle folders and files records
            $workspace->delete();

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw new BusinessLogicException('Failed to delete workspace: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // FOLDER OPERATIONS
    // =========================================================================

    /**
     * List folder contents (subfolders and files).
     */
    public function listFolderContents(int $accountId, string $workspaceId, string $folderId): array
    {
        // Verify workspace exists and belongs to account
        $this->getWorkspace($accountId, $workspaceId);

        $folder = WorkspaceFolder::where('folder_id', $folderId)
            ->with([
                'subfolders' => function ($query) {
                    $query->orderBy('folder_name');
                },
                'files' => function ($query) {
                    $query->orderBy('file_name');
                }
            ])
            ->first();

        if (!$folder) {
            throw new ResourceNotFoundException('Folder not found');
        }

        return [
            'folder' => [
                'folder_id' => $folder->folder_id,
                'folder_name' => $folder->folder_name,
                'parent_folder_id' => $folder->parent_folder_id,
                'path' => $folder->getPath(),
            ],
            'subfolders' => $folder->subfolders->map(function ($subfolder) {
                return [
                    'folder_id' => $subfolder->folder_id,
                    'folder_name' => $subfolder->folder_name,
                    'created_at' => $subfolder->created_at->toIso8601String(),
                ];
            }),
            'files' => $folder->files->map(function ($file) {
                return [
                    'file_id' => $file->file_id,
                    'file_name' => $file->file_name,
                    'file_size' => $file->file_size,
                    'file_size_formatted' => $file->getFileSizeFormatted(),
                    'content_type' => $file->content_type,
                    'created_at' => $file->created_at->toIso8601String(),
                    'created_by' => $file->createdBy ? [
                        'id' => $file->createdBy->id,
                        'name' => $file->createdBy->name,
                    ] : null,
                ];
            }),
        ];
    }

    /**
     * Delete folder and its contents.
     */
    public function deleteFolder(int $accountId, string $workspaceId, string $folderId): bool
    {
        // Verify workspace exists
        $this->getWorkspace($accountId, $workspaceId);

        $folder = WorkspaceFolder::where('folder_id', $folderId)->first();

        if (!$folder) {
            throw new ResourceNotFoundException('Folder not found');
        }

        // Cannot delete root folder
        if ($folder->isRoot() && $folder->folder_name === 'Root') {
            throw new BusinessLogicException('Cannot delete the root folder');
        }

        DB::beginTransaction();
        try {
            // Recursively delete all files in this folder and subfolders
            $this->deleteFolderRecursive($folder);

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw new BusinessLogicException('Failed to delete folder: ' . $e->getMessage());
        }
    }

    /**
     * Recursively delete folder and all its contents.
     */
    private function deleteFolderRecursive(WorkspaceFolder $folder): void
    {
        // Delete files
        foreach ($folder->files as $file) {
            $this->deleteFileStorage($file);
            $file->delete();
        }

        // Recursively delete subfolders
        foreach ($folder->subfolders as $subfolder) {
            $this->deleteFolderRecursive($subfolder);
        }

        // Delete the folder itself
        $folder->delete();
    }

    // =========================================================================
    // FILE OPERATIONS
    // =========================================================================

    /**
     * Create a workspace file (upload).
     */
    public function createFile(
        int $accountId,
        string $workspaceId,
        string $folderId,
        UploadedFile $file,
        array $metadata = []
    ): WorkspaceFile {
        // Verify workspace exists
        $this->getWorkspace($accountId, $workspaceId);

        $folder = WorkspaceFolder::where('folder_id', $folderId)->first();

        if (!$folder) {
            throw new ResourceNotFoundException('Folder not found');
        }

        DB::beginTransaction();
        try {
            // Store file
            $path = $file->store('workspace_files', 'public');

            // Create file record
            $workspaceFile = WorkspaceFile::create([
                'folder_id' => $folder->id,
                'file_name' => $metadata['file_name'] ?? $file->getClientOriginalName(),
                'file_uri' => $path,
                'file_size' => $file->getSize(),
                'content_type' => $file->getMimeType(),
                'created_by_user_id' => $metadata['created_by_user_id'] ?? null,
            ]);

            DB::commit();
            return $workspaceFile->fresh(['createdBy']);

        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($path)) {
                Storage::disk('public')->delete($path);
            }
            throw new BusinessLogicException('Failed to create file: ' . $e->getMessage());
        }
    }

    /**
     * Get a workspace file.
     */
    public function getFile(int $accountId, string $workspaceId, string $folderId, string $fileId): WorkspaceFile
    {
        // Verify workspace exists
        $this->getWorkspace($accountId, $workspaceId);

        $file = WorkspaceFile::where('file_id', $fileId)
            ->whereHas('folder', function ($query) use ($folderId) {
                $query->where('folder_id', $folderId);
            })
            ->with(['createdBy:id,name,email', 'folder'])
            ->first();

        if (!$file) {
            throw new ResourceNotFoundException('File not found');
        }

        return $file;
    }

    /**
     * Update workspace file metadata.
     */
    public function updateFile(
        int $accountId,
        string $workspaceId,
        string $folderId,
        string $fileId,
        array $data
    ): WorkspaceFile {
        $file = $this->getFile($accountId, $workspaceId, $folderId, $fileId);

        $updateData = array_filter([
            'file_name' => $data['file_name'] ?? null,
        ], fn($value) => $value !== null);

        if (empty($updateData)) {
            throw new ValidationException('No valid fields to update');
        }

        $file->update($updateData);

        return $file->fresh(['createdBy', 'folder']);
    }

    /**
     * Get file pages (for preview/pagination).
     */
    public function getFilePages(
        int $accountId,
        string $workspaceId,
        string $folderId,
        string $fileId
    ): array {
        $file = $this->getFile($accountId, $workspaceId, $folderId, $fileId);

        // Placeholder implementation
        // In production, this would extract actual pages from PDF or images
        return [
            'file_id' => $file->file_id,
            'file_name' => $file->file_name,
            'total_pages' => $file->isPdf() ? 1 : 0, // Placeholder
            'pages' => [
                [
                    'page_number' => 1,
                    'width' => 612,
                    'height' => 792,
                    'thumbnail_url' => $file->getFileUrl(),
                ]
            ],
        ];
    }

    /**
     * Delete file storage from disk.
     */
    private function deleteFileStorage(WorkspaceFile $file): void
    {
        if ($file->file_uri && !filter_var($file->file_uri, FILTER_VALIDATE_URL)) {
            Storage::disk('public')->delete($file->file_uri);
        }
    }
}
