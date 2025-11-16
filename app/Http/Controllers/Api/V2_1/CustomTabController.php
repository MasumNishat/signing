<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Http\Controllers\Api\BaseController;
use App\Models\Account;
use App\Services\CustomTabService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * CustomTabController
 *
 * Manages custom tab templates (reusable form field templates).
 * Custom tabs allow organizations to create standardized fields
 * for common data like Employee ID, Department, Project Code, etc.
 *
 * Total Endpoints: 8
 */
class CustomTabController extends BaseController
{
    protected CustomTabService $customTabService;

    public function __construct(CustomTabService $customTabService)
    {
        $this->customTabService = $customTabService;
    }

    /**
     * GET /accounts/{accountId}/custom_tabs
     *
     * List all custom tabs for an account
     */
    public function index(Request $request, string $accountId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'nullable|string',
            'shared' => 'nullable|boolean',
            'search' => 'nullable|string|max:255',
            'count' => 'nullable|integer|min:1|max:100',
            'start_position' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();

            $result = $this->customTabService->listCustomTabs(
                account: $account,
                type: $request->query('type'),
                shared: $request->query('shared') !== null ? filter_var($request->query('shared'), FILTER_VALIDATE_BOOLEAN) : null,
                userId: $request->user()?->id,
                search: $request->query('search'),
                limit: $request->query('count', 20),
                offset: $request->query('start_position', 0)
            );

            return $this->success([
                'custom_tabs' => $result['custom_tabs']->map(function ($tab) {
                    return $this->formatCustomTab($tab);
                }),
                'total_set_size' => $result['total'],
                'start_position' => $result['offset'],
                'result_set_size' => $result['custom_tabs']->count(),
                'end_position' => min($result['offset'] + $result['custom_tabs']->count(), $result['total']),
            ], 'Custom tabs retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve custom tabs: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /accounts/{accountId}/custom_tabs
     *
     * Create a new custom tab template
     */
    public function store(Request $request, string $accountId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:' . implode(',', \App\Models\CustomTab::TAB_TYPES),
            'label' => 'nullable|string',
            'required' => 'nullable|boolean',
            'value' => 'nullable|string',
            'font' => 'nullable|string|max:50',
            'font_size' => 'nullable|integer|min:6|max:72',
            'font_color' => 'nullable|string|max:20',
            'bold' => 'nullable|boolean',
            'italic' => 'nullable|boolean',
            'underline' => 'nullable|boolean',
            'width' => 'nullable|integer|min:1',
            'height' => 'nullable|integer|min:1',
            'validation_type' => 'nullable|string|in:email,phone,ssn,zip,date,number,url',
            'validation_pattern' => 'nullable|string|max:255',
            'validation_message' => 'nullable|string',
            'tooltip' => 'nullable|string',
            'list_items' => 'nullable|array',
            'list_items.*' => 'string',
            'shared' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();

            // Check name uniqueness
            if (!$this->customTabService->isNameUnique($account, $request->name)) {
                return $this->error('A custom tab with this name already exists', 422);
            }

            $data = $request->only([
                'name', 'type', 'label', 'required', 'value',
                'font', 'font_size', 'font_color', 'bold', 'italic', 'underline',
                'width', 'height', 'validation_type', 'validation_pattern',
                'validation_message', 'tooltip', 'list_items', 'shared'
            ]);
            $data['created_by'] = $request->user()?->id;

            $customTab = $this->customTabService->createCustomTab($account, $data);

            return $this->created(
                $this->formatCustomTab($customTab),
                'Custom tab created successfully'
            );

        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Exception $e) {
            return $this->error('Failed to create custom tab: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /accounts/{accountId}/custom_tabs/{customTabId}
     *
     * Get a specific custom tab
     */
    public function show(string $accountId, string $customTabId): JsonResponse
    {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();
            $customTab = $this->customTabService->getCustomTab($account, $customTabId);

            return $this->success(
                $this->formatCustomTab($customTab),
                'Custom tab retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve custom tab: ' . $e->getMessage(), 404);
        }
    }

    /**
     * PUT /accounts/{accountId}/custom_tabs/{customTabId}
     *
     * Update a custom tab
     */
    public function update(Request $request, string $accountId, string $customTabId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:' . implode(',', \App\Models\CustomTab::TAB_TYPES),
            'label' => 'nullable|string',
            'required' => 'nullable|boolean',
            'value' => 'nullable|string',
            'font' => 'nullable|string|max:50',
            'font_size' => 'nullable|integer|min:6|max:72',
            'font_color' => 'nullable|string|max:20',
            'bold' => 'nullable|boolean',
            'italic' => 'nullable|boolean',
            'underline' => 'nullable|boolean',
            'width' => 'nullable|integer|min:1',
            'height' => 'nullable|integer|min:1',
            'validation_type' => 'nullable|string|in:email,phone,ssn,zip,date,number,url',
            'validation_pattern' => 'nullable|string|max:255',
            'validation_message' => 'nullable|string',
            'tooltip' => 'nullable|string',
            'list_items' => 'nullable|array',
            'list_items.*' => 'string',
            'shared' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();
            $customTab = $this->customTabService->getCustomTab($account, $customTabId);

            // Check name uniqueness if name is being changed
            if ($request->has('name') && $request->name !== $customTab->name) {
                if (!$this->customTabService->isNameUnique($account, $request->name, $customTabId)) {
                    return $this->error('A custom tab with this name already exists', 422);
                }
            }

            $data = $request->only([
                'name', 'type', 'label', 'required', 'value',
                'font', 'font_size', 'font_color', 'bold', 'italic', 'underline',
                'width', 'height', 'validation_type', 'validation_pattern',
                'validation_message', 'tooltip', 'list_items', 'shared'
            ]);

            $customTab = $this->customTabService->updateCustomTab($customTab, $data);

            return $this->success(
                $this->formatCustomTab($customTab),
                'Custom tab updated successfully'
            );

        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Exception $e) {
            return $this->error('Failed to update custom tab: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /accounts/{accountId}/custom_tabs/{customTabId}
     *
     * Delete a custom tab
     */
    public function destroy(string $accountId, string $customTabId): JsonResponse
    {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();
            $customTab = $this->customTabService->getCustomTab($account, $customTabId);

            $this->customTabService->deleteCustomTab($customTab);

            return $this->noContent('Custom tab deleted successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to delete custom tab: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /accounts/{accountId}/custom_tabs/type/{type}
     *
     * Get custom tabs by type
     */
    public function getByType(string $accountId, string $type): JsonResponse
    {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();

            $customTabs = $this->customTabService->getCustomTabsByType($account, $type);

            return $this->success([
                'custom_tabs' => $customTabs->map(function ($tab) {
                    return $this->formatCustomTab($tab);
                }),
                'total' => $customTabs->count(),
                'type' => $type,
            ], 'Custom tabs retrieved successfully');

        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve custom tabs: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /accounts/{accountId}/custom_tabs/shared
     *
     * Get shared custom tabs
     */
    public function getShared(string $accountId): JsonResponse
    {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();

            $customTabs = $this->customTabService->getSharedCustomTabs($account);

            return $this->success([
                'custom_tabs' => $customTabs->map(function ($tab) {
                    return $this->formatCustomTab($tab);
                }),
                'total' => $customTabs->count(),
            ], 'Shared custom tabs retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve shared custom tabs: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /accounts/{accountId}/custom_tabs/personal
     *
     * Get personal (non-shared) custom tabs for the authenticated user
     */
    public function getPersonal(Request $request, string $accountId): JsonResponse
    {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();
            $userId = $request->user()->id;

            $customTabs = $this->customTabService->getPersonalCustomTabs($account, $userId);

            return $this->success([
                'custom_tabs' => $customTabs->map(function ($tab) {
                    return $this->formatCustomTab($tab);
                }),
                'total' => $customTabs->count(),
            ], 'Personal custom tabs retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve personal custom tabs: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Format custom tab for API response
     */
    protected function formatCustomTab($tab): array
    {
        return [
            'custom_tab_id' => $tab->custom_tab_id,
            'name' => $tab->name,
            'type' => $tab->type,
            'label' => $tab->label,
            'required' => $tab->required,
            'value' => $tab->value,
            'font' => $tab->font,
            'font_size' => $tab->font_size,
            'font_color' => $tab->font_color,
            'bold' => $tab->bold,
            'italic' => $tab->italic,
            'underline' => $tab->underline,
            'width' => $tab->width,
            'height' => $tab->height,
            'validation_type' => $tab->validation_type,
            'validation_pattern' => $tab->validation_pattern,
            'validation_message' => $tab->validation_message,
            'tooltip' => $tab->tooltip,
            'list_items' => $tab->list_items,
            'shared' => $tab->shared,
            'created_by' => $tab->created_by,
            'created_at' => $tab->created_at?->toIso8601String(),
            'updated_at' => $tab->updated_at?->toIso8601String(),
        ];
    }
}
