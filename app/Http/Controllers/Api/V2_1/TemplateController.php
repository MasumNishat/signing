<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Http\Controllers\Api\BaseController;
use App\Models\Account;
use App\Models\Template;
use App\Services\TemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Template Controller
 *
 * Handles template management endpoints including CRUD operations,
 * envelope creation from templates, sharing, and favorites.
 *
 * Endpoints:
 * - GET    /templates                      - List templates
 * - POST   /templates                      - Create template
 * - GET    /templates/{id}                 - Get template
 * - PUT    /templates/{id}                 - Update template
 * - DELETE /templates/{id}                 - Delete template
 * - POST   /templates/{id}/envelopes       - Create envelope from template
 * - POST   /templates/{id}/share           - Share template with user
 * - DELETE /templates/{id}/share/{userId}  - Unshare template
 * - POST   /templates/{id}/favorites       - Add to favorites
 * - DELETE /templates/{id}/favorites       - Remove from favorites
 */
class TemplateController extends BaseController
{
    /**
     * Template service
     */
    protected TemplateService $templateService;

    /**
     * Initialize controller
     */
    public function __construct(TemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * List templates
     *
     * GET /v2.1/accounts/{accountId}/templates
     *
     * @param Request $request
     * @param string $accountId
     * @return JsonResponse
     */
    public function index(Request $request, string $accountId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'owner_user_id' => 'nullable|exists:users,id',
            'shared' => 'nullable|in:true,false',
            'accessible_by_user_id' => 'nullable|exists:users,id',
            'search' => 'nullable|string|max:255',
            'sort_by' => 'nullable|in:created_at,updated_at,template_name',
            'sort_order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $filters = [
                'owner_user_id' => $request->input('owner_user_id'),
                'shared' => $request->input('shared'),
                'accessible_by_user_id' => $request->input('accessible_by_user_id'),
                'search' => $request->input('search'),
                'sort_by' => $request->input('sort_by', 'created_at'),
                'sort_order' => $request->input('sort_order', 'desc'),
            ];

            $perPage = $request->input('per_page', 15);
            $templates = $this->templateService->listTemplates($account, $filters, $perPage);

            return $this->paginated($templates, 'Templates retrieved successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Create a new template
     *
     * POST /v2.1/accounts/{accountId}/templates
     *
     * @param Request $request
     * @param string $accountId
     * @return JsonResponse
     */
    public function store(Request $request, string $accountId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $validator = Validator::make($request->all(), [
            'template_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'owner_user_id' => 'nullable|exists:users,id',
            'shared' => 'nullable|in:private,shared',
            'documents' => 'nullable|array',
            'documents.*.name' => 'required_with:documents|string|max:255',
            'documents.*.document_base64' => 'nullable|string',
            'documents.*.file_extension' => 'nullable|string|max:20',
            'documents.*.order' => 'nullable|integer|min:1',
            'recipients' => 'nullable|array',
            'recipients.*.recipient_type' => 'required_with:recipients|string',
            'recipients.*.role_name' => 'nullable|string|max:100',
            'recipients.*.name' => 'required_with:recipients|string|max:255',
            'recipients.*.email' => 'required_with:recipients|email|max:255',
            'recipients.*.routing_order' => 'nullable|integer|min:1',
            'tabs' => 'nullable|array',
            'custom_fields' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $template = $this->templateService->createTemplate($account, $request->all());
            return $this->created($template, 'Template created successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get a specific template
     *
     * GET /v2.1/accounts/{accountId}/templates/{templateId}
     *
     * @param string $accountId
     * @param string $templateId
     * @return JsonResponse
     */
    public function show(string $accountId, string $templateId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        try {
            $template = $this->templateService->getTemplate($account, $templateId);
            return $this->success($template, 'Template retrieved successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * Update a template
     *
     * PUT /v2.1/accounts/{accountId}/templates/{templateId}
     *
     * @param Request $request
     * @param string $accountId
     * @param string $templateId
     * @return JsonResponse
     */
    public function update(Request $request, string $accountId, string $templateId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $template = Template::where('account_id', $account->id)
            ->where('template_id', $templateId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'template_name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'shared' => 'nullable|in:private,shared',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $updatedTemplate = $this->templateService->updateTemplate($template, $request->all());
            return $this->success($updatedTemplate, 'Template updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Delete a template
     *
     * DELETE /v2.1/accounts/{accountId}/templates/{templateId}
     *
     * @param string $accountId
     * @param string $templateId
     * @return JsonResponse
     */
    public function destroy(string $accountId, string $templateId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $template = Template::where('account_id', $account->id)
            ->where('template_id', $templateId)
            ->firstOrFail();

        try {
            $this->templateService->deleteTemplate($template);
            return $this->noContent('Template deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Create an envelope from a template
     *
     * POST /v2.1/accounts/{accountId}/templates/{templateId}/envelopes
     *
     * @param Request $request
     * @param string $accountId
     * @param string $templateId
     * @return JsonResponse
     */
    public function createEnvelope(Request $request, string $accountId, string $templateId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $template = Template::where('account_id', $account->id)
            ->where('template_id', $templateId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'sender_user_id' => 'required|exists:users,id',
            'email_subject' => 'nullable|string|max:255',
            'email_message' => 'nullable|string|max:10000',
            'recipients' => 'nullable|array',
            'recipients.*.role_name' => 'required_with:recipients|string|max:100',
            'recipients.*.name' => 'required_with:recipients|string|max:255',
            'recipients.*.email' => 'required_with:recipients|email|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $envelope = $this->templateService->createEnvelopeFromTemplate($template, $request->all());
            return $this->created($envelope, 'Envelope created from template successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Share template with a user
     *
     * POST /v2.1/accounts/{accountId}/templates/{templateId}/share
     *
     * @param Request $request
     * @param string $accountId
     * @param string $templateId
     * @return JsonResponse
     */
    public function share(Request $request, string $accountId, string $templateId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $template = Template::where('account_id', $account->id)
            ->where('template_id', $templateId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'shared_with_user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $sharedAccess = $this->templateService->shareTemplate(
                $template,
                $request->input('shared_with_user_id')
            );
            return $this->created($sharedAccess, 'Template shared successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Unshare template from a user
     *
     * DELETE /v2.1/accounts/{accountId}/templates/{templateId}/share/{userId}
     *
     * @param string $accountId
     * @param string $templateId
     * @param int $userId
     * @return JsonResponse
     */
    public function unshare(string $accountId, string $templateId, int $userId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $template = Template::where('account_id', $account->id)
            ->where('template_id', $templateId)
            ->firstOrFail();

        try {
            $unshared = $this->templateService->unshareTemplate($template, $userId);

            if ($unshared) {
                return $this->noContent('Template unshared successfully');
            } else {
                return $this->error('Template was not shared with this user', 404);
            }
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Add template to favorites
     *
     * POST /v2.1/accounts/{accountId}/templates/{templateId}/favorites
     *
     * @param Request $request
     * @param string $accountId
     * @param string $templateId
     * @return JsonResponse
     */
    public function addFavorite(Request $request, string $accountId, string $templateId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $template = Template::where('account_id', $account->id)
            ->where('template_id', $templateId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $favorite = $this->templateService->addToFavorites(
                $template,
                $request->input('user_id')
            );
            return $this->created($favorite, 'Template added to favorites');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Remove template from favorites
     *
     * DELETE /v2.1/accounts/{accountId}/templates/{templateId}/favorites
     *
     * @param Request $request
     * @param string $accountId
     * @param string $templateId
     * @return JsonResponse
     */
    public function removeFavorite(Request $request, string $accountId, string $templateId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $template = Template::where('account_id', $account->id)
            ->where('template_id', $templateId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $removed = $this->templateService->removeFromFavorites(
                $template,
                $request->input('user_id')
            );

            if ($removed) {
                return $this->noContent('Template removed from favorites');
            } else {
                return $this->error('Template was not in favorites', 404);
            }
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
