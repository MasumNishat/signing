<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Models\Account;
use App\Models\SharedAccess;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SharedAccessController
 *
 * Manages shared access to envelopes and templates with other users.
 * Users can share their envelopes/templates or view items shared with them.
 *
 * Total Endpoints: 2
 * - GET /accounts/{accountId}/shared_access - Gets shared item status
 * - PUT /accounts/{accountId}/shared_access - Sets shared access information
 */
class SharedAccessController extends BaseController
{
    /**
     * GET /accounts/{accountId}/shared_access
     *
     * Reserved: Gets the shared item status for one or more users.
     * Retrieves shared item status for one or more users and types of items.
     *
     * Users with account administration privileges can retrieve shared access information
     * for all account users. Users without account administrator privileges can only
     * retrieve shared access information for themselves.
     */
    public function index(Request $request, string $accountId): JsonResponse
    {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();
            $currentUser = $request->user();

            $validated = $request->validate([
                'count' => 'sometimes|integer|min:1|max:100',
                'start_position' => 'sometimes|integer|min:0',
                'item_type' => 'sometimes|string|in:envelopes,templates',
                'shared' => 'sometimes|string|in:shared_to,shared_from,shared_to_and_from',
                'user_ids' => 'sometimes|string', // Comma-separated user IDs
                'folder_ids' => 'sometimes|string', // Comma-separated folder IDs
                'search_text' => 'sometimes|string|max:255',
            ]);

            $query = SharedAccess::where('account_id', $account->id);

            // Item type filter
            if (isset($validated['item_type'])) {
                $itemType = $validated['item_type'] === 'envelopes'
                    ? SharedAccess::ITEM_TYPE_ENVELOPE
                    : SharedAccess::ITEM_TYPE_TEMPLATE;
                $query->where('item_type', $itemType);
            }

            // Shared direction filter
            if (isset($validated['shared'])) {
                switch ($validated['shared']) {
                    case 'shared_to':
                        // Items current user has shared to others
                        $query->where('user_id', $currentUser->id);
                        break;
                    case 'shared_from':
                        // Items shared to current user by others
                        $query->where('shared_with_user_id', $currentUser->id);
                        break;
                    case 'shared_to_and_from':
                        // Both directions
                        $query->where(function ($q) use ($currentUser) {
                            $q->where('user_id', $currentUser->id)
                              ->orWhere('shared_with_user_id', $currentUser->id);
                        });
                        break;
                }
            }

            // User IDs filter
            if (isset($validated['user_ids'])) {
                $userIds = explode(',', $validated['user_ids']);
                $users = User::where('account_id', $account->id)
                    ->whereIn('user_name', $userIds)
                    ->pluck('id');

                $query->where(function ($q) use ($users) {
                    $q->whereIn('user_id', $users)
                      ->orWhereIn('shared_with_user_id', $users);
                });
            }

            $count = $validated['count'] ?? 20;
            $startPosition = $validated['start_position'] ?? 0;

            $totalRecords = $query->count();
            $sharedAccess = $query
                ->with(['user:id,user_name,email,name', 'sharedWithUser:id,user_name,email,name'])
                ->orderBy('created_at', 'desc')
                ->skip($startPosition)
                ->take($count)
                ->get();

            // Group by item type
            $envelopes = $sharedAccess->where('item_type', SharedAccess::ITEM_TYPE_ENVELOPE)->map(function ($share) {
                return [
                    'envelope_id' => $share->item_id,
                    'shared' => $share->user_id === auth()->id() ? 'shared_to' : 'shared_from',
                    'owner' => [
                        'user_name' => $share->user->user_name,
                        'email' => $share->user->email,
                        'name' => $share->user->name,
                    ],
                    'shared_with' => $share->sharedWithUser ? [
                        'user_name' => $share->sharedWithUser->user_name,
                        'email' => $share->sharedWithUser->email,
                        'name' => $share->sharedWithUser->name,
                    ] : null,
                ];
            })->values();

            $templates = $sharedAccess->where('item_type', SharedAccess::ITEM_TYPE_TEMPLATE)->map(function ($share) {
                return [
                    'template_id' => $share->item_id,
                    'shared' => $share->user_id === auth()->id() ? 'shared_to' : 'shared_from',
                    'owner' => [
                        'user_name' => $share->user->user_name,
                        'email' => $share->user->email,
                        'name' => $share->user->name,
                    ],
                    'shared_with' => $share->sharedWithUser ? [
                        'user_name' => $share->sharedWithUser->user_name,
                        'email' => $share->sharedWithUser->email,
                        'name' => $share->sharedWithUser->name,
                    ] : null,
                ];
            })->values();

            return $this->successResponse([
                'shared_access' => [
                    'envelopes' => $envelopes,
                    'templates' => $templates,
                ],
                'result_set_size' => $sharedAccess->count(),
                'start_position' => $startPosition,
                'total_set_size' => $totalRecords,
                'end_position' => min($startPosition + $sharedAccess->count(), $totalRecords),
            ], 'Shared access information retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve shared access', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve shared access information', 500);
        }
    }

    /**
     * PUT /accounts/{accountId}/shared_access
     *
     * Reserved: Sets the shared access information for users.
     * Sets the shared access information for one or more users.
     */
    public function update(Request $request, string $accountId): JsonResponse
    {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();
            $currentUser = $request->user();

            $validated = $request->validate([
                'item_type' => 'sometimes|string|in:envelopes,templates',
                'preserve_existing_shared_access' => 'sometimes|boolean',
                'user_ids' => 'sometimes|string', // Comma-separated user IDs
                'shared_access' => 'required|array',
                'shared_access.*.item_id' => 'required|string',
                'shared_access.*.item_type' => 'required|string|in:envelope,template',
                'shared_access.*.shared_with_user_names' => 'required|array',
                'shared_access.*.shared_with_user_names.*' => 'string|exists:users,user_name',
            ]);

            $preserveExisting = $validated['preserve_existing_shared_access'] ?? false;

            DB::beginTransaction();

            try {
                $createdShares = [];

                foreach ($validated['shared_access'] as $shareData) {
                    $itemId = $shareData['item_id'];
                    $itemType = $shareData['item_type'];

                    // If not preserving, delete existing shares for this item
                    if (!$preserveExisting) {
                        SharedAccess::where('account_id', $account->id)
                            ->where('user_id', $currentUser->id)
                            ->where('item_id', $itemId)
                            ->where('item_type', $itemType)
                            ->delete();
                    }

                    // Create new shares
                    foreach ($shareData['shared_with_user_names'] as $userName) {
                        $sharedWithUser = User::where('user_name', $userName)
                            ->where('account_id', $account->id)
                            ->firstOrFail();

                        $share = SharedAccess::updateOrCreate(
                            [
                                'account_id' => $account->id,
                                'user_id' => $currentUser->id,
                                'item_type' => $itemType,
                                'item_id' => $itemId,
                                'shared_with_user_id' => $sharedWithUser->id,
                            ]
                        );

                        $createdShares[] = [
                            'item_id' => $itemId,
                            'item_type' => $itemType,
                            'shared_with' => [
                                'user_name' => $sharedWithUser->user_name,
                                'email' => $sharedWithUser->email,
                                'name' => $sharedWithUser->name,
                            ],
                        ];
                    }
                }

                DB::commit();

                return $this->successResponse([
                    'shared_access' => $createdShares,
                    'total_count' => count($createdShares),
                ], 'Shared access information updated successfully');

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Failed to update shared access', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to update shared access information', 500);
        }
    }
}
