<?php

namespace App\Services;

use App\Models\CustomTab;
use App\Models\Account;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CustomTabService
 *
 * Handles business logic for custom tab (reusable field template) operations.
 * Custom tabs allow organizations to create standardized form fields that can
 * be reused across multiple envelopes and templates.
 */
class CustomTabService
{
    /**
     * List custom tabs for an account
     */
    public function listCustomTabs(
        Account $account,
        ?string $type = null,
        ?bool $shared = null,
        ?int $userId = null,
        ?string $search = null,
        int $limit = 20,
        int $offset = 0
    ): array {
        $query = CustomTab::where('account_id', $account->id);

        // Filter by type
        if ($type) {
            $query->ofType($type);
        }

        // Filter by shared status
        if ($shared !== null) {
            if ($shared) {
                $query->shared();
            } elseif ($userId) {
                $query->personal($userId);
            }
        }

        // Search by name
        if ($search) {
            $query->search($search);
        }

        $total = $query->count();

        $customTabs = $query
            ->orderBy('name')
            ->skip($offset)
            ->take($limit)
            ->get();

        return [
            'custom_tabs' => $customTabs,
            'total' => $total,
            'offset' => $offset,
            'limit' => $limit,
        ];
    }

    /**
     * Get a specific custom tab
     */
    public function getCustomTab(Account $account, string $customTabId): CustomTab
    {
        return CustomTab::where('account_id', $account->id)
            ->where('custom_tab_id', $customTabId)
            ->firstOrFail();
    }

    /**
     * Create a new custom tab
     */
    public function createCustomTab(Account $account, array $data): CustomTab
    {
        // Validate tab type
        if (isset($data['type']) && !in_array($data['type'], CustomTab::TAB_TYPES)) {
            throw new \InvalidArgumentException('Invalid tab type: ' . $data['type']);
        }

        // Ensure list_items are provided for list types
        $listTypes = ['list', 'radio_group', 'dropdown'];
        if (isset($data['type']) && in_array($data['type'], $listTypes)) {
            if (empty($data['list_items'])) {
                throw new \InvalidArgumentException('list_items are required for type: ' . $data['type']);
            }
        }

        return DB::transaction(function () use ($account, $data) {
            $customTab = new CustomTab();
            $customTab->account_id = $account->id;
            $customTab->name = $data['name'];
            $customTab->type = $data['type'];
            $customTab->label = $data['label'] ?? null;
            $customTab->required = $data['required'] ?? false;
            $customTab->value = $data['value'] ?? null;
            $customTab->font = $data['font'] ?? null;
            $customTab->font_size = $data['font_size'] ?? null;
            $customTab->font_color = $data['font_color'] ?? null;
            $customTab->bold = $data['bold'] ?? false;
            $customTab->italic = $data['italic'] ?? false;
            $customTab->underline = $data['underline'] ?? false;
            $customTab->width = $data['width'] ?? null;
            $customTab->height = $data['height'] ?? null;
            $customTab->validation_type = $data['validation_type'] ?? null;
            $customTab->validation_pattern = $data['validation_pattern'] ?? null;
            $customTab->validation_message = $data['validation_message'] ?? null;
            $customTab->tooltip = $data['tooltip'] ?? null;
            $customTab->list_items = $data['list_items'] ?? null;
            $customTab->shared = $data['shared'] ?? false;
            $customTab->created_by = $data['created_by'] ?? null;
            $customTab->save();

            return $customTab;
        });
    }

    /**
     * Update a custom tab
     */
    public function updateCustomTab(CustomTab $customTab, array $data): CustomTab
    {
        // Validate tab type if being changed
        if (isset($data['type']) && !in_array($data['type'], CustomTab::TAB_TYPES)) {
            throw new \InvalidArgumentException('Invalid tab type: ' . $data['type']);
        }

        // Ensure list_items are provided for list types
        $listTypes = ['list', 'radio_group', 'dropdown'];
        $type = $data['type'] ?? $customTab->type;
        if (in_array($type, $listTypes)) {
            if (!isset($data['list_items']) && !$customTab->list_items) {
                throw new \InvalidArgumentException('list_items are required for type: ' . $type);
            }
        }

        return DB::transaction(function () use ($customTab, $data) {
            $customTab->update($data);
            return $customTab->fresh();
        });
    }

    /**
     * Delete a custom tab
     */
    public function deleteCustomTab(CustomTab $customTab): bool
    {
        return DB::transaction(function () use ($customTab) {
            return $customTab->delete();
        });
    }

    /**
     * Get custom tabs by type
     */
    public function getCustomTabsByType(Account $account, string $type): Collection
    {
        if (!in_array($type, CustomTab::TAB_TYPES)) {
            throw new \InvalidArgumentException('Invalid tab type: ' . $type);
        }

        return CustomTab::where('account_id', $account->id)
            ->ofType($type)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get shared custom tabs
     */
    public function getSharedCustomTabs(Account $account): Collection
    {
        return CustomTab::where('account_id', $account->id)
            ->shared()
            ->orderBy('name')
            ->get();
    }

    /**
     * Get personal (non-shared) custom tabs for a user
     */
    public function getPersonalCustomTabs(Account $account, int $userId): Collection
    {
        return CustomTab::where('account_id', $account->id)
            ->personal($userId)
            ->orderBy('name')
            ->get();
    }

    /**
     * Check if a custom tab name is unique within the account
     */
    public function isNameUnique(Account $account, string $name, ?string $excludeCustomTabId = null): bool
    {
        $query = CustomTab::where('account_id', $account->id)
            ->where('name', $name);

        if ($excludeCustomTabId) {
            $query->where('custom_tab_id', '!=', $excludeCustomTabId);
        }

        return $query->doesntExist();
    }
}
