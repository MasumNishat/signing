<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Http\Controllers\Controller;
use App\Services\FolderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class FolderController extends Controller
{
    protected FolderService $folderService;

    public function __construct(FolderService $folderService)
    {
        $this->folderService = $folderService;
    }

    /**
     * Get a list of folders for the account.
     *
     * GET /v2.1/accounts/{accountId}/folders
     *
     * Retrieves a list of the folders for the account, including the folder hierarchy.
     *
     * @param Request $request
     * @param string $accountId
     * @return JsonResponse
     */
    public function index(Request $request, string $accountId): JsonResponse
    {
        try {
            $options = $request->only([
                'count',
                'include',
                'include_items',
                'start_position',
                'sub_folder_depth',
                'template',
                'user_filter',
            ]);

            $folders = $this->folderService->getFolders((int) $accountId, $options);

            return $this->success([
                'folders' => $folders->map(function ($folder) {
                    return $this->formatFolderResponse($folder);
                })->toArray(),
                'resultSetSize' => $folders->count(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Get a list of the envelopes in the specified folder.
     *
     * GET /v2.1/accounts/{accountId}/folders/{folderId}
     *
     * Retrieves a list of the envelopes in the specified folder.
     *
     * @param Request $request
     * @param string $accountId
     * @param string $folderId
     * @return JsonResponse
     */
    public function show(Request $request, string $accountId, string $folderId): JsonResponse
    {
        try {
            $filters = $request->only([
                'from_date',
                'to_date',
                'search_text',
                'status',
                'owner_name',
                'owner_email',
                'start_position',
                'count',
                'include_items',
            ]);

            $envelopes = $this->folderService->getFolderItems((int) $accountId, $folderId, $filters);

            $folder = $this->folderService->getFolder((int) $accountId, $folderId);

            if (!$folder) {
                return $this->notFound('Folder not found');
            }

            return $this->success([
                'folderItems' => $envelopes->map(function ($envelope) {
                    return [
                        'envelopeId' => $envelope->envelope_id,
                        'envelopeUri' => '/envelopes/' . $envelope->envelope_id,
                        'status' => $envelope->status,
                        'emailSubject' => $envelope->email_subject,
                        'senderName' => $envelope->sender_name,
                        'senderEmail' => $envelope->sender_email,
                        'createdDateTime' => $envelope->created_date_time?->toIso8601String(),
                        'sentDateTime' => $envelope->sent_date_time?->toIso8601String(),
                        'completedDateTime' => $envelope->completed_date_time?->toIso8601String(),
                    ];
                })->toArray(),
                'folderInfo' => $this->formatFolderResponse($folder),
                'resultSetSize' => $envelopes->count(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Move envelopes to the specified folder.
     *
     * PUT /v2.1/accounts/{accountId}/folders/{folderId}
     *
     * Moves envelopes to the specified folder.
     *
     * @param Request $request
     * @param string $accountId
     * @param string $folderId
     * @return JsonResponse
     */
    public function update(Request $request, string $accountId, string $folderId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'envelopeIds' => 'required|array',
            'envelopeIds.*' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $results = $this->folderService->moveEnvelopesToFolder(
                (int) $accountId,
                $folderId,
                $request->input('envelopeIds')
            );

            $folder = $this->folderService->getFolder((int) $accountId, $folderId);

            return $this->success([
                'folders' => [
                    $this->formatFolderResponse($folder),
                ],
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Search for envelopes in folders matching the specified criteria.
     *
     * GET /v2.1/accounts/{accountId}/search_folders/{searchFolderId}
     *
     * Note: This is a placeholder endpoint - full search implementation would require
     * more complex search criteria and filters.
     *
     * @param Request $request
     * @param string $accountId
     * @param string $searchFolderId
     * @return JsonResponse
     */
    public function searchFolders(Request $request, string $accountId, string $searchFolderId): JsonResponse
    {
        try {
            // For now, return folders matching the search criteria
            // In production, this would use complex search filters
            $folders = $this->folderService->getFolders((int) $accountId, [
                'include_items' => 'true',
            ]);

            return $this->success([
                'folders' => $folders->map(function ($folder) {
                    return $this->formatFolderResponse($folder);
                })->toArray(),
                'resultSetSize' => $folders->count(),
            ]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Format folder response.
     *
     * @param mixed $folder
     * @return array
     */
    private function formatFolderResponse($folder): array
    {
        $response = [
            'folderId' => $folder->folder_id,
            'name' => $folder->folder_name,
            'type' => $folder->folder_type,
            'uri' => "/folders/{$folder->folder_id}",
            'parentFolderId' => $folder->parent_folder_id,
            'parentFolderUri' => $folder->parent_folder_id ? "/folders/{$folder->parent_folder_id}" : null,
            'itemCount' => $folder->item_count ?? 0,
            'subFolderCount' => $folder->sub_folder_count ?? 0,
            'hasSubFolders' => $folder->has_sub_folders ?? false,
        ];

        // Include subfolders if loaded
        if ($folder->relationLoaded('children') && $folder->children->isNotEmpty()) {
            $response['folders'] = $folder->children->map(function ($child) {
                return $this->formatFolderResponse($child);
            })->toArray();
        }

        return $response;
    }
}
