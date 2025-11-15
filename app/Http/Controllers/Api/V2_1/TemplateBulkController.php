<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Models\Account;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * TemplateBulkController
 *
 * Bulk operations for templates including batch creation,
 * updates, and batch envelope generation.
 *
 * Total Endpoints: 3
 */
class TemplateBulkController extends BaseController
{
    /**
     * POST /accounts/{accountId}/templates/bulk_create
     *
     * Create multiple templates in a single request
     */
    public function bulkCreate(Request $request, string $accountId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'templates' => 'required|array|min:1|max:50',
                'templates.*.name' => 'required|string|max:255',
                'templates.*.description' => 'sometimes|string',
                'templates.*.email_subject' => 'sometimes|string|max:255',
                'templates.*.email_blurb' => 'sometimes|string',
                'templates.*.shared' => 'sometimes|boolean',
                'templates.*.password' => 'sometimes|string|max:255',
            ]);

            $account = Account::where('account_id', $accountId)->firstOrFail();

            DB::beginTransaction();

            try {
                $createdTemplates = [];

                foreach ($validated['templates'] as $templateData) {
                    $template = Template::create([
                        'account_id' => $account->id,
                        'template_id' => 'tpl-' . \Illuminate\Support\Str::uuid(),
                        'name' => $templateData['name'],
                        'description' => $templateData['description'] ?? null,
                        'email_subject' => $templateData['email_subject'] ?? $templateData['name'],
                        'email_blurb' => $templateData['email_blurb'] ?? null,
                        'shared' => $templateData['shared'] ?? false,
                        'password' => $templateData['password'] ?? null,
                        'created_by_user_id' => auth()->id(),
                    ]);

                    $createdTemplates[] = $template;
                }

                DB::commit();

                return $this->createdResponse([
                    'templates' => collect($createdTemplates)->map(function ($template) {
                        return [
                            'template_id' => $template->template_id,
                            'name' => $template->name,
                            'description' => $template->description,
                            'created_at' => $template->created_at->toIso8601String(),
                        ];
                    }),
                    'total_created' => count($createdTemplates),
                ], 'Templates created successfully');
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * PUT /accounts/{accountId}/templates/bulk_update
     *
     * Update multiple templates in a single request
     */
    public function bulkUpdate(Request $request, string $accountId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'template_ids' => 'required|array|min:1|max:50',
                'updates' => 'required|array',
                'updates.shared' => 'sometimes|boolean',
                'updates.password' => 'sometimes|string|max:255',
                'updates.description' => 'sometimes|string',
            ]);

            $account = Account::where('account_id', $accountId)->firstOrFail();

            DB::beginTransaction();

            try {
                $updatedTemplates = [];

                foreach ($validated['template_ids'] as $templateId) {
                    $template = Template::where('account_id', $account->id)
                        ->where('template_id', $templateId)
                        ->firstOrFail();

                    $template->update(array_filter([
                        'shared' => $validated['updates']['shared'] ?? null,
                        'password' => $validated['updates']['password'] ?? null,
                        'description' => $validated['updates']['description'] ?? null,
                        'updated_by_user_id' => auth()->id(),
                    ], fn($value) => $value !== null));

                    $updatedTemplates[] = $template;
                }

                DB::commit();

                return $this->successResponse([
                    'templates' => collect($updatedTemplates)->map(function ($template) {
                        return [
                            'template_id' => $template->template_id,
                            'name' => $template->name,
                            'shared' => $template->shared,
                            'updated_at' => $template->updated_at->toIso8601String(),
                        ];
                    }),
                    'total_updated' => count($updatedTemplates),
                ], 'Templates updated successfully');
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * DELETE /accounts/{accountId}/templates/bulk_delete
     *
     * Delete multiple templates in a single request
     */
    public function bulkDelete(Request $request, string $accountId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'template_ids' => 'required|array|min:1|max:50',
            ]);

            $account = Account::where('account_id', $accountId)->firstOrFail();

            DB::beginTransaction();

            try {
                $deletedCount = 0;
                $deletedTemplates = [];

                foreach ($validated['template_ids'] as $templateId) {
                    $template = Template::where('account_id', $account->id)
                        ->where('template_id', $templateId)
                        ->first();

                    if ($template) {
                        $deletedTemplates[] = [
                            'template_id' => $template->template_id,
                            'name' => $template->name,
                        ];
                        $template->delete();
                        $deletedCount++;
                    }
                }

                DB::commit();

                return $this->successResponse([
                    'deleted_templates' => $deletedTemplates,
                    'total_deleted' => $deletedCount,
                    'deleted_at' => now()->toIso8601String(),
                ], 'Templates deleted successfully');
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
