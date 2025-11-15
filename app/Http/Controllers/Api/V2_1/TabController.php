<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Http\Controllers\Api\BaseController;
use App\Models\Account;
use App\Models\Envelope;
use App\Models\EnvelopeRecipient;
use App\Models\EnvelopeTab;
use App\Services\TabService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Tab Controller
 *
 * Handles envelope tab (form field) management operations.
 * Supports signature fields, text fields, checkboxes, and all 27 tab types.
 *
 * Endpoints:
 * - GET    /recipients/{recipientId}/tabs           - List all tabs
 * - POST   /recipients/{recipientId}/tabs           - Add tabs
 * - GET    /recipients/{recipientId}/tabs/{tabId}   - Get specific tab
 * - PUT    /recipients/{recipientId}/tabs/{tabId}   - Update tab
 * - DELETE /recipients/{recipientId}/tabs/{tabId}   - Delete tab
 */
class TabController extends BaseController
{
    /**
     * Tab service
     */
    protected TabService $tabService;

    /**
     * Initialize controller
     */
    public function __construct(TabService $tabService)
    {
        $this->tabService = $tabService;
    }

    /**
     * List all tabs for a recipient
     *
     * GET /v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients/{recipientId}/tabs
     *
     * @param Request $request
     * @param string $accountId
     * @param string $envelopeId
     * @param string $recipientId
     * @return JsonResponse
     */
    public function index(
        Request $request,
        string $accountId,
        string $envelopeId,
        string $recipientId
    ): JsonResponse {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $recipient = EnvelopeRecipient::where('envelope_id', $envelope->id)
            ->where('recipient_id', $recipientId)
            ->firstOrFail();

        $tabs = $this->tabService->listTabs($recipient, [
            'document_id' => $request->query('document_id'),
            'type' => $request->query('type'),
            'page_number' => $request->query('page_number'),
            'status' => $request->query('status'),
            'required_only' => $request->query('required_only', false),
        ]);

        // Group tabs by type for better organization
        $groupedTabs = [];
        foreach ($tabs as $tab) {
            $type = $tab->type;
            if (!isset($groupedTabs[$type])) {
                $groupedTabs[$type] = [];
            }
            $groupedTabs[$type][] = $this->tabService->getMetadata($tab);
        }

        return $this->success([
            'envelope_id' => $envelope->envelope_id,
            'recipient_id' => $recipient->recipient_id,
            'total_tabs' => $tabs->count(),
            'tabs' => $groupedTabs,
        ], 'Tabs retrieved successfully');
    }

    /**
     * Add tabs to a recipient
     *
     * POST /v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients/{recipientId}/tabs
     *
     * @param Request $request
     * @param string $accountId
     * @param string $envelopeId
     * @param string $recipientId
     * @return JsonResponse
     */
    public function store(
        Request $request,
        string $accountId,
        string $envelopeId,
        string $recipientId
    ): JsonResponse {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $recipient = EnvelopeRecipient::where('envelope_id', $envelope->id)
            ->where('recipient_id', $recipientId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'tabs' => 'required|array|min:1',
            'tabs.*.tab_id' => 'nullable|string|max:100',
            'tabs.*.type' => 'required|string|in:' . implode(',', EnvelopeTab::getSupportedTypes()),
            'tabs.*.tab_label' => 'nullable|string|max:255',
            'tabs.*.value' => 'nullable|string',
            'tabs.*.required' => 'nullable|boolean',
            'tabs.*.locked' => 'nullable|boolean',

            // Absolute positioning
            'tabs.*.page_number' => 'nullable|integer|min:1',
            'tabs.*.x_position' => 'nullable|integer|min:0',
            'tabs.*.y_position' => 'nullable|integer|min:0',
            'tabs.*.width' => 'nullable|integer|min:1',
            'tabs.*.height' => 'nullable|integer|min:1',

            // Anchor positioning
            'tabs.*.anchor_string' => 'nullable|string|max:255',
            'tabs.*.anchor_x_offset' => 'nullable|integer',
            'tabs.*.anchor_y_offset' => 'nullable|integer',
            'tabs.*.anchor_units' => 'nullable|string|in:pixels,mms,cms',
            'tabs.*.anchor_ignore_if_not_present' => 'nullable|boolean',

            // Conditional logic
            'tabs.*.conditional_parent_label' => 'nullable|string|max:255',
            'tabs.*.conditional_parent_value' => 'nullable|string|max:255',

            // Formatting
            'tabs.*.font' => 'nullable|string|max:50',
            'tabs.*.font_size' => 'nullable|integer|min:6|max:72',
            'tabs.*.font_color' => 'nullable|string|max:50',
            'tabs.*.bold' => 'nullable|boolean',
            'tabs.*.italic' => 'nullable|boolean',
            'tabs.*.underline' => 'nullable|boolean',

            // List items
            'tabs.*.list_items' => 'nullable|array',
            'tabs.*.list_items.*' => 'string',

            // Validation
            'tabs.*.validation_pattern' => 'nullable|string|max:500',
            'tabs.*.validation_message' => 'nullable|string|max:255',

            // Additional
            'tabs.*.tooltip' => 'nullable|string|max:500',
            'tabs.*.tab_group_label' => 'nullable|string|max:255',
            'tabs.*.document_id' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $tabs = $this->tabService->addTabs(
                $recipient,
                $request->input('tabs', [])
            );

            $formattedTabs = array_map(function ($tab) {
                return $this->tabService->getMetadata($tab);
            }, $tabs);

            return $this->created([
                'envelope_id' => $envelope->envelope_id,
                'recipient_id' => $recipient->recipient_id,
                'tabs' => $formattedTabs,
            ], 'Tabs added successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get a specific tab
     *
     * GET /v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients/{recipientId}/tabs/{tabId}
     *
     * @param string $accountId
     * @param string $envelopeId
     * @param string $recipientId
     * @param string $tabId
     * @return JsonResponse
     */
    public function show(
        string $accountId,
        string $envelopeId,
        string $recipientId,
        string $tabId
    ): JsonResponse {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $recipient = EnvelopeRecipient::where('envelope_id', $envelope->id)
            ->where('recipient_id', $recipientId)
            ->firstOrFail();

        try {
            $tab = $this->tabService->getTab($recipient, $tabId);

            return $this->success(
                $this->tabService->getMetadata($tab),
                'Tab retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    /**
     * Update a tab
     *
     * PUT /v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients/{recipientId}/tabs/{tabId}
     *
     * @param Request $request
     * @param string $accountId
     * @param string $envelopeId
     * @param string $recipientId
     * @param string $tabId
     * @return JsonResponse
     */
    public function update(
        Request $request,
        string $accountId,
        string $envelopeId,
        string $recipientId,
        string $tabId
    ): JsonResponse {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $recipient = EnvelopeRecipient::where('envelope_id', $envelope->id)
            ->where('recipient_id', $recipientId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'tab_label' => 'nullable|string|max:255',
            'value' => 'nullable|string',
            'required' => 'nullable|boolean',
            'locked' => 'nullable|boolean',

            // Positioning
            'page_number' => 'nullable|integer|min:1',
            'x_position' => 'nullable|integer|min:0',
            'y_position' => 'nullable|integer|min:0',
            'width' => 'nullable|integer|min:1',
            'height' => 'nullable|integer|min:1',

            // Anchor positioning
            'anchor_string' => 'nullable|string|max:255',
            'anchor_x_offset' => 'nullable|integer',
            'anchor_y_offset' => 'nullable|integer',

            // Formatting
            'font' => 'nullable|string|max:50',
            'font_size' => 'nullable|integer|min:6|max:72',
            'font_color' => 'nullable|string|max:50',
            'bold' => 'nullable|boolean',
            'italic' => 'nullable|boolean',
            'underline' => 'nullable|boolean',

            // Validation
            'validation_pattern' => 'nullable|string|max:500',
            'validation_message' => 'nullable|string|max:255',

            // Additional
            'tooltip' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $tab = $this->tabService->getTab($recipient, $tabId);

            $updatedTab = $this->tabService->updateTab($tab, $request->all());

            return $this->success(
                $this->tabService->getMetadata($updatedTab),
                'Tab updated successfully'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Delete a tab
     *
     * DELETE /v2.1/accounts/{accountId}/envelopes/{envelopeId}/recipients/{recipientId}/tabs/{tabId}
     *
     * @param string $accountId
     * @param string $envelopeId
     * @param string $recipientId
     * @param string $tabId
     * @return JsonResponse
     */
    public function destroy(
        string $accountId,
        string $envelopeId,
        string $recipientId,
        string $tabId
    ): JsonResponse {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $recipient = EnvelopeRecipient::where('envelope_id', $envelope->id)
            ->where('recipient_id', $recipientId)
            ->firstOrFail();

        try {
            $tab = $this->tabService->getTab($recipient, $tabId);

            $this->tabService->deleteTab($tab);

            return $this->noContent('Tab deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
