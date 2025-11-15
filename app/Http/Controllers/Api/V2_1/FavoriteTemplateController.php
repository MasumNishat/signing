<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Models\Account;
use App\Models\FavoriteTemplate;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * FavoriteTemplateController
 *
 * Handles favorite template operations for quick access to frequently used templates.
 *
 * Endpoints: 3 total
 * - GET /accounts/{accountId}/favorite_templates - List favorite templates
 * - PUT /accounts/{accountId}/favorite_templates - Add template to favorites
 * - DELETE /accounts/{accountId}/favorite_templates - Remove template from favorites
 */
class FavoriteTemplateController extends BaseController
{
    /**
     * GET /accounts/{accountId}/favorite_templates
     * Retrieves the list of favorited templates for this caller
     */
    public function index(Request $request, string $accountId): JsonResponse
    {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();
            $userId = $request->user()->id;

            $favorites = FavoriteTemplate::where('account_id', $account->id)
                ->where('user_id', $userId)
                ->with(['template' => function ($query) {
                    $query->select('id', 'template_id', 'name', 'description', 'created_date_time', 'last_modified_date_time');
                }])
                ->get()
                ->map(function ($favorite) {
                    return [
                        'template_id' => $favorite->template->template_id,
                        'name' => $favorite->template->name,
                        'description' => $favorite->template->description,
                        'favorited_date' => $favorite->created_at?->toIso8601String(),
                    ];
                });

            return $this->successResponse([
                'favorite_templates' => $favorites,
            ], 'Favorite templates retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve favorite templates', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to retrieve favorite templates', 500);
        }
    }

    /**
     * PUT /accounts/{accountId}/favorite_templates
     * Favorites a template (adds to favorites list)
     */
    public function store(Request $request, string $accountId): JsonResponse
    {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();
            $userId = $request->user()->id;

            $validator = Validator::make($request->all(), [
                'template_id' => 'required|string|exists:templates,template_id',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            // Find the template
            $template = Template::where('template_id', $request->template_id)
                ->where('account_id', $account->id)
                ->firstOrFail();

            // Check if already favorited
            $existing = FavoriteTemplate::where('account_id', $account->id)
                ->where('user_id', $userId)
                ->where('template_id', $template->id)
                ->first();

            if ($existing) {
                return $this->successResponse([
                    'template_id' => $template->template_id,
                    'message' => 'Template is already in favorites',
                ], 'Template already favorited');
            }

            // Create favorite
            FavoriteTemplate::create([
                'account_id' => $account->id,
                'user_id' => $userId,
                'template_id' => $template->id,
            ]);

            return $this->successResponse([
                'template_id' => $template->template_id,
                'message' => 'Template added to favorites',
            ], 'Template favorited successfully');

        } catch (\Exception $e) {
            Log::error('Failed to favorite template', [
                'account_id' => $accountId,
                'template_id' => $request->input('template_id'),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to favorite template', 500);
        }
    }

    /**
     * DELETE /accounts/{accountId}/favorite_templates
     * Unfavorite a template (removes from favorites list)
     */
    public function destroy(Request $request, string $accountId): JsonResponse
    {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();
            $userId = $request->user()->id;

            $validator = Validator::make($request->all(), [
                'template_id' => 'required|string|exists:templates,template_id',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            // Find the template
            $template = Template::where('template_id', $request->template_id)
                ->where('account_id', $account->id)
                ->first();

            if (!$template) {
                return $this->notFoundResponse('Template not found');
            }

            // Delete the favorite
            $deleted = FavoriteTemplate::where('account_id', $account->id)
                ->where('user_id', $userId)
                ->where('template_id', $template->id)
                ->delete();

            if ($deleted) {
                return $this->successResponse([
                    'template_id' => $template->template_id,
                    'message' => 'Template removed from favorites',
                ], 'Template unfavorited successfully');
            }

            return $this->successResponse([
                'template_id' => $template->template_id,
                'message' => 'Template was not in favorites',
            ], 'Template not favorited');

        } catch (\Exception $e) {
            Log::error('Failed to unfavorite template', [
                'account_id' => $accountId,
                'template_id' => $request->input('template_id'),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to unfavorite template', 500);
        }
    }
}
