<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Models\Template;
use App\Models\EnvelopeTab;
use App\Services\TabService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * TemplateTabController
 *
 * Handles template tab (form field) operations.
 * Templates reuse envelope_tabs table with template_id column.
 * Supports all 27 DocuSign tab types.
 */
class TemplateTabController extends BaseController
{
    protected TabService $tabService;

    public function __construct(TabService $tabService)
    {
        $this->tabService = $tabService;
    }

    /**
     * GET /accounts/{accountId}/templates/{templateId}/tabs
     *
     * Get all tabs for a template, grouped by type
     */
    public function index(string $accountId, string $templateId): JsonResponse
    {
        try {
            $template = Template::where('account_id', $accountId)
                ->where('template_id', $templateId)
                ->with('tabs')
                ->firstOrFail();

            // Group tabs by type
            $groupedTabs = $this->tabService->groupTabsByType($template->tabs);

            return $this->successResponse([
                'tabs' => $groupedTabs,
                'total_count' => $template->tabs->count(),
            ], 'Template tabs retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /accounts/{accountId}/templates/{templateId}/tabs
     *
     * Add tabs to a template
     */
    public function store(Request $request, string $accountId, string $templateId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'tabs' => 'required|array',
                'tabs.*.tab_type' => 'required|string',
                'tabs.*.recipient_id' => 'required|string',
                'tabs.*.document_id' => 'required|string',
                'tabs.*.page_number' => 'required|integer|min:1',
                'tabs.*.x_position' => 'sometimes|numeric',
                'tabs.*.y_position' => 'sometimes|numeric',
                'tabs.*.width' => 'sometimes|numeric',
                'tabs.*.height' => 'sometimes|numeric',
                'tabs.*.anchor_string' => 'sometimes|string',
                'tabs.*.anchor_x_offset' => 'sometimes|numeric',
                'tabs.*.anchor_y_offset' => 'sometimes|numeric',
                'tabs.*.label' => 'sometimes|string|max:255',
                'tabs.*.value' => 'sometimes|string',
                'tabs.*.required' => 'sometimes|boolean',
                'tabs.*.locked' => 'sometimes|boolean',
                'tabs.*.tab_order' => 'sometimes|integer',
                'tabs.*.font' => 'sometimes|string',
                'tabs.*.font_size' => 'sometimes|integer',
                'tabs.*.font_color' => 'sometimes|string',
                'tabs.*.bold' => 'sometimes|boolean',
                'tabs.*.italic' => 'sometimes|boolean',
                'tabs.*.underline' => 'sometimes|boolean',
                'tabs.*.tooltip' => 'sometimes|string',
                'tabs.*.validation_pattern' => 'sometimes|string',
                'tabs.*.validation_message' => 'sometimes|string',
                'tabs.*.list_items' => 'sometimes|array',
                'tabs.*.selected' => 'sometimes|boolean',
                'tabs.*.group_name' => 'sometimes|string',
                'tabs.*.shared' => 'sometimes|boolean',
            ]);

            $template = Template::where('account_id', $accountId)
                ->where('template_id', $templateId)
                ->firstOrFail();

            $createdTabs = $this->tabService->createTabs(
                $template->id,
                $validated['tabs'],
                'template'
            );

            $groupedTabs = $this->tabService->groupTabsByType($createdTabs);

            return $this->createdResponse([
                'tabs' => $groupedTabs,
                'total_count' => $createdTabs->count(),
            ], 'Template tabs created successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * PUT /accounts/{accountId}/templates/{templateId}/tabs
     *
     * Replace all tabs for a template
     */
    public function update(Request $request, string $accountId, string $templateId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'tabs' => 'required|array',
                'tabs.*.tab_type' => 'required|string',
                'tabs.*.recipient_id' => 'required|string',
                'tabs.*.document_id' => 'required|string',
                'tabs.*.page_number' => 'required|integer|min:1',
                'tabs.*.x_position' => 'sometimes|numeric',
                'tabs.*.y_position' => 'sometimes|numeric',
                'tabs.*.width' => 'sometimes|numeric',
                'tabs.*.height' => 'sometimes|numeric',
                'tabs.*.anchor_string' => 'sometimes|string',
                'tabs.*.label' => 'sometimes|string|max:255',
                'tabs.*.value' => 'sometimes|string',
                'tabs.*.required' => 'sometimes|boolean',
            ]);

            $template = Template::where('account_id', $accountId)
                ->where('template_id', $templateId)
                ->firstOrFail();

            // Delete existing tabs
            EnvelopeTab::where('template_id', $template->id)->delete();

            // Create new tabs
            $createdTabs = $this->tabService->createTabs(
                $template->id,
                $validated['tabs'],
                'template'
            );

            $groupedTabs = $this->tabService->groupTabsByType($createdTabs);

            return $this->successResponse([
                'tabs' => $groupedTabs,
                'total_count' => $createdTabs->count(),
            ], 'Template tabs updated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * DELETE /accounts/{accountId}/templates/{templateId}/tabs
     *
     * Delete all tabs from a template
     */
    public function destroy(string $accountId, string $templateId): JsonResponse
    {
        try {
            $template = Template::where('account_id', $accountId)
                ->where('template_id', $templateId)
                ->firstOrFail();

            $count = EnvelopeTab::where('template_id', $template->id)->delete();

            return $this->successResponse([
                'deleted_count' => $count,
            ], 'Template tabs deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /accounts/{accountId}/templates/{templateId}/tabs/{tabId}
     *
     * Get a specific template tab
     */
    public function show(string $accountId, string $templateId, string $tabId): JsonResponse
    {
        try {
            $template = Template::where('account_id', $accountId)
                ->where('template_id', $templateId)
                ->firstOrFail();

            $tab = EnvelopeTab::where('template_id', $template->id)
                ->where('tab_id', $tabId)
                ->firstOrFail();

            return $this->successResponse(
                $this->tabService->formatTab($tab),
                'Template tab retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * PUT /accounts/{accountId}/templates/{templateId}/tabs/{tabId}
     *
     * Update a specific template tab
     */
    public function updateSingle(Request $request, string $accountId, string $templateId, string $tabId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'x_position' => 'sometimes|numeric',
                'y_position' => 'sometimes|numeric',
                'width' => 'sometimes|numeric',
                'height' => 'sometimes|numeric',
                'label' => 'sometimes|string|max:255',
                'value' => 'sometimes|string',
                'required' => 'sometimes|boolean',
                'locked' => 'sometimes|boolean',
                'tab_order' => 'sometimes|integer',
                'tooltip' => 'sometimes|string',
                'validation_pattern' => 'sometimes|string',
                'validation_message' => 'sometimes|string',
            ]);

            $template = Template::where('account_id', $accountId)
                ->where('template_id', $templateId)
                ->firstOrFail();

            $tab = EnvelopeTab::where('template_id', $template->id)
                ->where('tab_id', $tabId)
                ->firstOrFail();

            $tab->update($validated);

            return $this->successResponse(
                $this->tabService->formatTab($tab->fresh()),
                'Template tab updated successfully'
            );
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
