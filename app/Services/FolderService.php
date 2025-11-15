<?php

namespace App\Services;

use App\Models\Folder;
use App\Models\Envelope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FolderService
{
    /**
     * Get all folders for an account.
     *
     * @param int $accountId
     * @param array $options
     * @return Collection
     */
    public function getFolders(int $accountId, array $options = []): Collection
    {
        $query = Folder::forAccount($accountId);

        // Filter by template folders
        if (isset($options['template']) && $options['template'] === 'true') {
            // Template folders - typically custom folders for templates
            $query->customFolders();
        }

        // Filter by user
        if (isset($options['user_filter'])) {
            $query->forUser((int) $options['user_filter']);
        }

        // Include root folders only or all
        if (!isset($options['sub_folder_depth']) || $options['sub_folder_depth'] === '0') {
            $query->rootFolders();
        }

        // Include item counts
        if (isset($options['include_items']) && $options['include_items'] === 'true') {
            $query->withCount('envelopes as item_count');
        }

        // Pagination
        if (isset($options['start_position'])) {
            $query->skip((int) $options['start_position']);
        }

        if (isset($options['count'])) {
            $query->limit((int) $options['count']);
        }

        $folders = $query->with('children')->get();

        // Load sub-folders recursively if requested
        if (isset($options['sub_folder_depth']) && (int) $options['sub_folder_depth'] > 0) {
            $this->loadSubFolders($folders, (int) $options['sub_folder_depth']);
        }

        return $folders;
    }

    /**
     * Get a specific folder by ID.
     *
     * @param int $accountId
     * @param string $folderId
     * @return Folder|null
     */
    public function getFolder(int $accountId, string $folderId): ?Folder
    {
        return Folder::forAccount($accountId)
            ->where('folder_id', $folderId)
            ->first();
    }

    /**
     * Get envelopes in a folder.
     *
     * @param int $accountId
     * @param string $folderId
     * @param array $filters
     * @return Collection
     */
    public function getFolderItems(int $accountId, string $folderId, array $filters = []): Collection
    {
        $folder = $this->getFolder($accountId, $folderId);

        if (!$folder) {
            return collect();
        }

        $query = $folder->envelopes();

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        if (isset($filters['search_text'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('email_subject', 'like', '%' . $filters['search_text'] . '%')
                  ->orWhere('envelope_id', 'like', '%' . $filters['search_text'] . '%');
            });
        }

        if (isset($filters['owner_name'])) {
            $query->whereHas('sender', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['owner_name'] . '%');
            });
        }

        if (isset($filters['owner_email'])) {
            $query->whereHas('sender', function ($q) use ($filters) {
                $q->where('email', $filters['owner_email']);
            });
        }

        // Pagination
        if (isset($filters['start_position'])) {
            $query->skip((int) $filters['start_position']);
        }

        $query->limit($filters['count'] ?? 100);

        return $query->get();
    }

    /**
     * Move envelopes to a folder.
     *
     * @param int $accountId
     * @param string $folderId
     * @param array $envelopeIds
     * @return array
     */
    public function moveEnvelopesToFolder(int $accountId, string $folderId, array $envelopeIds): array
    {
        $folder = $this->getFolder($accountId, $folderId);

        if (!$folder) {
            throw new \Exception("Folder not found");
        }

        $results = [];

        DB::beginTransaction();
        try {
            foreach ($envelopeIds as $envelopeId) {
                // Find the envelope
                $envelope = Envelope::forAccount($accountId)
                    ->where('envelope_id', $envelopeId)
                    ->first();

                if (!$envelope) {
                    $results[] = [
                        'envelope_id' => $envelopeId,
                        'success' => false,
                        'error' => 'Envelope not found',
                    ];
                    continue;
                }

                // Remove from all folders first
                $envelope->folders()->detach();

                // Add to new folder
                $envelope->folders()->attach($folder->id);

                $results[] = [
                    'envelope_id' => $envelopeId,
                    'success' => true,
                ];
            }

            // Update folder item count
            $folder->updateItemCount();

            DB::commit();

            return $results;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create a new folder.
     *
     * @param int $accountId
     * @param array $data
     * @return Folder
     */
    public function createFolder(int $accountId, array $data): Folder
    {
        $folderData = [
            'account_id' => $accountId,
            'folder_name' => $data['folder_name'],
            'folder_type' => $data['folder_type'] ?? Folder::TYPE_CUSTOM,
            'owner_user_id' => $data['owner_user_id'] ?? null,
            'parent_folder_id' => $data['parent_folder_id'] ?? null,
            'filter' => $data['filter'] ?? null,
        ];

        $folder = Folder::create($folderData);

        // Update parent folder's subfolder count
        if ($folder->parent_folder_id) {
            $parentFolder = Folder::find($folder->parent_folder_id);
            if ($parentFolder) {
                $parentFolder->updateSubFolderCount();
            }
        }

        return $folder;
    }

    /**
     * Update a folder.
     *
     * @param int $accountId
     * @param string $folderId
     * @param array $data
     * @return Folder
     */
    public function updateFolder(int $accountId, string $folderId, array $data): Folder
    {
        $folder = $this->getFolder($accountId, $folderId);

        if (!$folder) {
            throw new \Exception("Folder not found");
        }

        // Prevent updating system folders
        if ($folder->isSystemFolder()) {
            throw new \Exception("Cannot update system folder");
        }

        $updateData = [];

        if (isset($data['folder_name'])) {
            $updateData['folder_name'] = $data['folder_name'];
        }

        if (isset($data['parent_folder_id'])) {
            $updateData['parent_folder_id'] = $data['parent_folder_id'];
        }

        $folder->update($updateData);

        return $folder->fresh();
    }

    /**
     * Delete a folder.
     *
     * @param int $accountId
     * @param string $folderId
     * @return bool
     */
    public function deleteFolder(int $accountId, string $folderId): bool
    {
        $folder = $this->getFolder($accountId, $folderId);

        if (!$folder) {
            return false;
        }

        // Prevent deleting system folders
        if ($folder->isSystemFolder()) {
            throw new \Exception("Cannot delete system folder");
        }

        DB::beginTransaction();
        try {
            // Detach all envelopes
            $folder->envelopes()->detach();

            // Delete or move subfolders
            $folder->children()->each(function ($child) {
                $child->parent_folder_id = null;
                $child->save();
            });

            // Update parent subfolder count
            if ($folder->parent_folder_id) {
                $parentFolder = Folder::find($folder->parent_folder_id);
                if ($parentFolder) {
                    $parentFolder->updateSubFolderCount();
                }
            }

            $folder->delete();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Load sub-folders recursively.
     *
     * @param Collection $folders
     * @param int $depth
     * @param int $currentDepth
     */
    private function loadSubFolders(Collection $folders, int $depth, int $currentDepth = 1): void
    {
        if ($currentDepth >= $depth) {
            return;
        }

        foreach ($folders as $folder) {
            if ($folder->children) {
                $this->loadSubFolders($folder->children, $depth, $currentDepth + 1);
            }
        }
    }

    /**
     * Create default system folders for an account.
     *
     * @param int $accountId
     * @param int|null $userId
     * @return Collection
     */
    public function createDefaultFolders(int $accountId, ?int $userId = null): Collection
    {
        $systemFolders = [
            ['name' => 'Inbox', 'type' => Folder::TYPE_INBOX],
            ['name' => 'Sent Items', 'type' => Folder::TYPE_SENT_ITEMS],
            ['name' => 'Drafts', 'type' => Folder::TYPE_DRAFT],
            ['name' => 'Trash', 'type' => Folder::TYPE_TRASH],
            ['name' => 'Recycle Bin', 'type' => Folder::TYPE_RECYCLE_BIN],
        ];

        $created = collect();

        foreach ($systemFolders as $folderDef) {
            $folder = Folder::create([
                'account_id' => $accountId,
                'folder_name' => $folderDef['name'],
                'folder_type' => $folderDef['type'],
                'owner_user_id' => $userId,
            ]);

            $created->push($folder);
        }

        return $created;
    }
}
