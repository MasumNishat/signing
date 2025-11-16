<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Http\Controllers\Api\BaseController;
use App\Models\Account;
use App\Models\Template;
use App\Models\EnvelopeDocument;
use App\Models\EnvelopeRecipient;
use App\Models\EnvelopeTab;
use App\Services\TabService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Template Tab Controller
 *
 * Handles template tab (form field) management operations.
 * Enables per-recipient and per-document tab assignment in templates.
 *
 * Endpoints:
 * - GET    /templates/{id}/documents/{docId}/tabs           - Get document tabs
 * - POST   /templates/{id}/documents/{docId}/tabs           - Add document tabs
 * - PUT    /templates/{id}/documents/{docId}/tabs           - Update document tabs
 * - DELETE /templates/{id}/documents/{docId}/tabs           - Delete document tabs
 * - GET    /templates/{id}/recipients/{recipId}/tabs        - Get recipient tabs
 * - POST   /templates/{id}/recipients/{recipId}/tabs        - Add recipient tabs
 * - PUT    /templates/{id}/recipients/{recipId}/tabs        - Update recipient tabs
 * - DELETE /templates/{id}/recipients/{recipId}/tabs        - Delete recipient tabs
 */
class TemplateTabController extends BaseController
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
     * Get all tabs for a template document
     *
     * GET /v2.1/accounts/{accountId}/templates/{templateId}/documents/{documentId}/tabs
     *
     * @param Request $request
     * @param string $accountId
     * @param string $templateId
     * @param string $documentId
     * @return JsonResponse
     */
    public function getDocumentTabs(
        Request $request,
        string $accountId,
        string $templateId,
        string $documentId
    ): JsonResponse {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $template = Template::where('account_id', $account->id)
            ->where('template_id', $templateId)
            ->firstOrFail();

        $document = EnvelopeDocument::where('template_id', $template->id)
            ->where('document_id', $documentId)
            ->firstOrFail();

        // Get all tabs for this document across all recipients
        $tabs = EnvelopeTab::where('template_id', $template->id)
            ->where('document_id', $document->id)
            ->when($request->query('type'), function ($query, $type) {
                return $query->where('type', $type);
            })
            ->when($request->query('page_number'), function ($query, $pageNumber) {
                return $query->where('page_number', $pageNumber);
            })
            ->get();

        // Group tabs by recipient and type
        $groupedTabs = [];
        foreach ($tabs as $tab) {
            $recipientId = $tab->recipient_id ?? 'unassigned';
            if (!isset($groupedTabs[$recipientId])) {
                $groupedTabs[$recipientId] = [];
            }

            $type = $tab->type;
            if (!isset($groupedTabs[$recipientId][$type])) {
                $groupedTabs[$recipientId][$type] = [];
            }

            $groupedTabs[$recipientId][$type][] = $this->tabService->getMetadata($tab);
        }

        return $this->success([
            'template_id' => $template->template_id,
            'document_id' => $document->document_id,
            'total_tabs' => $tabs->count(),
            'tabs_by_recipient' => $groupedTabs,
        ], 'Document tabs retrieved successfully');
    }

    /**
     * Add tabs to a template document
     *
     * POST /v2.1/accounts/{accountId}/templates/{templateId}/documents/{documentId}/tabs
     *
     * @param Request $request
     * @param string $accountId
     * @param string $templateId
     * @param string $documentId
     * @return JsonResponse
     */
    public function addDocumentTabs(
        Request $request,
        string $accountId,
        string $templateId,
        string $documentId
    ): JsonResponse {
        $validator = Validator::make($request->all(), [
            'tabs' => 'required|array|min:1',
            'tabs.*.type' => 'required|string|in:' . implode(',', $this->tabService->getTabTypes()),
            'tabs.*.recipient_id' => 'nullable|string',
            'tabs.*.label' => 'nullable|string|max:255',
            'tabs.*.page_number' => 'required|integer|min:1',
            'tabs.*.x_position' => 'required|numeric',
            'tabs.*.y_position' => 'required|numeric',
            'tabs.*.width' => 'nullable|numeric',
            'tabs.*.height' => 'nullable|numeric',
            'tabs.*.required' => 'nullable|boolean',
            'tabs.*.value' => 'nullable|string',
            'tabs.*.validation_pattern' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $account = Account::where('account_id', $accountId)->firstOrFail();

        $template = Template::where('account_id', $account->id)
            ->where('template_id', $templateId)
            ->firstOrFail();

        $document = EnvelopeDocument::where('template_id', $template->id)
            ->where('document_id', $documentId)
            ->firstOrFail();

        $createdTabs = [];
        foreach ($request->input('tabs') as $tabData) {
            // If recipient_id provided, verify it exists in template
            $recipientId = null;
            if (!empty($tabData['recipient_id'])) {
                $recipient = EnvelopeRecipient::where('template_id', $template->id)
                    ->where('recipient_id', $tabData['recipient_id'])
                    ->first();
                if ($recipient) {
                    $recipientId = $recipient->id;
                }
            }

            $tab = $this->tabService->createTab([
                'template_id' => $template->id,
                'document_id' => $document->id,
                'recipient_id' => $recipientId,
                'type' => $tabData['type'],
                'label' => $tabData['label'] ?? null,
                'page_number' => $tabData['page_number'],
                'x_position' => $tabData['x_position'],
                'y_position' => $tabData['y_position'],
                'width' => $tabData['width'] ?? $this->tabService->getDefaultWidth($tabData['type']),
                'height' => $tabData['height'] ?? $this->tabService->getDefaultHeight($tabData['type']),
                'required' => $tabData['required'] ?? false,
                'value' => $tabData['value'] ?? null,
                'validation_pattern' => $tabData['validation_pattern'] ?? null,
            ]);

            $createdTabs[] = $this->tabService->getMetadata($tab);
        }

        return $this->created([
            'template_id' => $template->template_id,
            'document_id' => $document->document_id,
            'tabs_added' => count($createdTabs),
            'tabs' => $createdTabs,
        ], 'Tabs added to document successfully');
    }

    /**
     * Update tabs on a template document
     *
     * PUT /v2.1/accounts/{accountId}/templates/{templateId}/documents/{documentId}/tabs
     *
     * @param Request $request
     * @param string $accountId
     * @param string $templateId
     * @param string $documentId
     * @return JsonResponse
     */
    public function updateDocumentTabs(
        Request $request,
        string $accountId,
        string $templateId,
        string $documentId
    ): JsonResponse {
        $validator = Validator::make($request->all(), [
            'tabs' => 'required|array|min:1',
            'tabs.*.tab_id' => 'required|string',
            'tabs.*.recipient_id' => 'nullable|string',
            'tabs.*.label' => 'nullable|string|max:255',
            'tabs.*.page_number' => 'nullable|integer|min:1',
            'tabs.*.x_position' => 'nullable|numeric',
            'tabs.*.y_position' => 'nullable|numeric',
            'tabs.*.width' => 'nullable|numeric',
            'tabs.*.height' => 'nullable|numeric',
            'tabs.*.required' => 'nullable|boolean',
            'tabs.*.value' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $account = Account::where('account_id', $accountId)->firstOrFail();

        $template = Template::where('account_id', $account->id)
            ->where('template_id', $templateId)
            ->firstOrFail();

        $document = EnvelopeDocument::where('template_id', $template->id)
            ->where('document_id', $documentId)
            ->firstOrFail();

        $updatedTabs = [];
        foreach ($request->input('tabs') as $tabData) {
            $tab = EnvelopeTab::where('template_id', $template->id)
                ->where('document_id', $document->id)
                ->where('tab_id', $tabData['tab_id'])
                ->firstOrFail();

            // Update recipient assignment if provided
            if (isset($tabData['recipient_id'])) {
                $recipient = EnvelopeRecipient::where('template_id', $template->id)
                    ->where('recipient_id', $tabData['recipient_id'])
                    ->first();
                $tab->recipient_id = $recipient ? $recipient->id : null;
            }

            // Update tab properties
            if (isset($tabData['label'])) $tab->label = $tabData['label'];
            if (isset($tabData['page_number'])) $tab->page_number = $tabData['page_number'];
            if (isset($tabData['x_position'])) $tab->x_position = $tabData['x_position'];
            if (isset($tabData['y_position'])) $tab->y_position = $tabData['y_position'];
            if (isset($tabData['width'])) $tab->width = $tabData['width'];
            if (isset($tabData['height'])) $tab->height = $tabData['height'];
            if (isset($tabData['required'])) $tab->required = $tabData['required'];
            if (isset($tabData['value'])) $tab->value = $tabData['value'];

            $tab->save();

            $updatedTabs[] = $this->tabService->getMetadata($tab);
        }

        return $this->success([
            'template_id' => $template->template_id,
            'document_id' => $document->document_id,
            'tabs_updated' => count($updatedTabs),
            'tabs' => $updatedTabs,
        ], 'Document tabs updated successfully');
    }

    /**
     * Delete tabs from a template document
     *
     * DELETE /v2.1/accounts/{accountId}/templates/{templateId}/documents/{documentId}/tabs
     *
     * @param Request $request
     * @param string $accountId
     * @param string $templateId
     * @param string $documentId
     * @return JsonResponse
     */
    public function deleteDocumentTabs(
        Request $request,
        string $accountId,
        string $templateId,
        string $documentId
    ): JsonResponse {
        $validator = Validator::make($request->all(), [
            'tab_ids' => 'required|array|min:1',
            'tab_ids.*' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $account = Account::where('account_id', $accountId)->firstOrFail();

        $template = Template::where('account_id', $account->id)
            ->where('template_id', $templateId)
            ->firstOrFail();

        $document = EnvelopeDocument::where('template_id', $template->id)
            ->where('document_id', $documentId)
            ->firstOrFail();

        $deletedCount = EnvelopeTab::where('template_id', $template->id)
            ->where('document_id', $document->id)
            ->whereIn('tab_id', $request->input('tab_ids'))
            ->delete();

        return $this->success([
            'template_id' => $template->template_id,
            'document_id' => $document->document_id,
            'tabs_deleted' => $deletedCount,
        ], 'Document tabs deleted successfully');
    }

    /**
     * Get all tabs for a template recipient
     *
     * GET /v2.1/accounts/{accountId}/templates/{templateId}/recipients/{recipientId}/tabs
     *
     * @param Request $request
     * @param string $accountId
     * @param string $templateId
     * @param string $recipientId
     * @return JsonResponse
     */
    public function getRecipientTabs(
        Request $request,
        string $accountId,
        string $templateId,
        string $recipientId
    ): JsonResponse {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $template = Template::where('account_id', $account->id)
            ->where('template_id', $templateId)
            ->firstOrFail();

        $recipient = EnvelopeRecipient::where('template_id', $template->id)
            ->where('recipient_id', $recipientId)
            ->firstOrFail();

        $tabs = EnvelopeTab::where('template_id', $template->id)
            ->where('recipient_id', $recipient->id)
            ->when($request->query('document_id'), function ($query, $documentId) use ($template) {
                $document = EnvelopeDocument::where('template_id', $template->id)
                    ->where('document_id', $documentId)
                    ->firstOrFail();
                return $query->where('document_id', $document->id);
            })
            ->when($request->query('type'), function ($query, $type) {
                return $query->where('type', $type);
            })
            ->get();

        // Group tabs by document and type
        $groupedTabs = [];
        foreach ($tabs as $tab) {
            $docId = $tab->document->document_id ?? 'unknown';
            if (!isset($groupedTabs[$docId])) {
                $groupedTabs[$docId] = [];
            }

            $type = $tab->type;
            if (!isset($groupedTabs[$docId][$type])) {
                $groupedTabs[$docId][$type] = [];
            }

            $groupedTabs[$docId][$type][] = $this->tabService->getMetadata($tab);
        }

        return $this->success([
            'template_id' => $template->template_id,
            'recipient_id' => $recipient->recipient_id,
            'total_tabs' => $tabs->count(),
            'tabs_by_document' => $groupedTabs,
        ], 'Recipient tabs retrieved successfully');
    }

    /**
     * Add tabs to a template recipient
     *
     * POST /v2.1/accounts/{accountId}/templates/{templateId}/recipients/{recipientId}/tabs
     *
     * @param Request $request
     * @param string $accountId
     * @param string $templateId
     * @param string $recipientId
     * @return JsonResponse
     */
    public function addRecipientTabs(
        Request $request,
        string $accountId,
        string $templateId,
        string $recipientId
    ): JsonResponse {
        $validator = Validator::make($request->all(), [
            'tabs' => 'required|array|min:1',
            'tabs.*.type' => 'required|string|in:' . implode(',', $this->tabService->getTabTypes()),
            'tabs.*.document_id' => 'required|string',
            'tabs.*.label' => 'nullable|string|max:255',
            'tabs.*.page_number' => 'required|integer|min:1',
            'tabs.*.x_position' => 'required|numeric',
            'tabs.*.y_position' => 'required|numeric',
            'tabs.*.width' => 'nullable|numeric',
            'tabs.*.height' => 'nullable|numeric',
            'tabs.*.required' => 'nullable|boolean',
            'tabs.*.value' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $account = Account::where('account_id', $accountId)->firstOrFail();

        $template = Template::where('account_id', $account->id)
            ->where('template_id', $templateId)
            ->firstOrFail();

        $recipient = EnvelopeRecipient::where('template_id', $template->id)
            ->where('recipient_id', $recipientId)
            ->firstOrFail();

        $createdTabs = [];
        foreach ($request->input('tabs') as $tabData) {
            $document = EnvelopeDocument::where('template_id', $template->id)
                ->where('document_id', $tabData['document_id'])
                ->firstOrFail();

            $tab = $this->tabService->createTab([
                'template_id' => $template->id,
                'document_id' => $document->id,
                'recipient_id' => $recipient->id,
                'type' => $tabData['type'],
                'label' => $tabData['label'] ?? null,
                'page_number' => $tabData['page_number'],
                'x_position' => $tabData['x_position'],
                'y_position' => $tabData['y_position'],
                'width' => $tabData['width'] ?? $this->tabService->getDefaultWidth($tabData['type']),
                'height' => $tabData['height'] ?? $this->tabService->getDefaultHeight($tabData['type']),
                'required' => $tabData['required'] ?? false,
                'value' => $tabData['value'] ?? null,
            ]);

            $createdTabs[] = $this->tabService->getMetadata($tab);
        }

        return $this->created([
            'template_id' => $template->template_id,
            'recipient_id' => $recipient->recipient_id,
            'tabs_added' => count($createdTabs),
            'tabs' => $createdTabs,
        ], 'Tabs added to recipient successfully');
    }

    /**
     * Update tabs for a template recipient
     *
     * PUT /v2.1/accounts/{accountId}/templates/{templateId}/recipients/{recipientId}/tabs
     *
     * @param Request $request
     * @param string $accountId
     * @param string $templateId
     * @param string $recipientId
     * @return JsonResponse
     */
    public function updateRecipientTabs(
        Request $request,
        string $accountId,
        string $templateId,
        string $recipientId
    ): JsonResponse {
        $validator = Validator::make($request->all(), [
            'tabs' => 'required|array|min:1',
            'tabs.*.tab_id' => 'required|string',
            'tabs.*.label' => 'nullable|string|max:255',
            'tabs.*.page_number' => 'nullable|integer|min:1',
            'tabs.*.x_position' => 'nullable|numeric',
            'tabs.*.y_position' => 'nullable|numeric',
            'tabs.*.width' => 'nullable|numeric',
            'tabs.*.height' => 'nullable|numeric',
            'tabs.*.required' => 'nullable|boolean',
            'tabs.*.value' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $account = Account::where('account_id', $accountId)->firstOrFail();

        $template = Template::where('account_id', $account->id)
            ->where('template_id', $templateId)
            ->firstOrFail();

        $recipient = EnvelopeRecipient::where('template_id', $template->id)
            ->where('recipient_id', $recipientId)
            ->firstOrFail();

        $updatedTabs = [];
        foreach ($request->input('tabs') as $tabData) {
            $tab = EnvelopeTab::where('template_id', $template->id)
                ->where('recipient_id', $recipient->id)
                ->where('tab_id', $tabData['tab_id'])
                ->firstOrFail();

            // Update tab properties
            if (isset($tabData['label'])) $tab->label = $tabData['label'];
            if (isset($tabData['page_number'])) $tab->page_number = $tabData['page_number'];
            if (isset($tabData['x_position'])) $tab->x_position = $tabData['x_position'];
            if (isset($tabData['y_position'])) $tab->y_position = $tabData['y_position'];
            if (isset($tabData['width'])) $tab->width = $tabData['width'];
            if (isset($tabData['height'])) $tab->height = $tabData['height'];
            if (isset($tabData['required'])) $tab->required = $tabData['required'];
            if (isset($tabData['value'])) $tab->value = $tabData['value'];

            $tab->save();

            $updatedTabs[] = $this->tabService->getMetadata($tab);
        }

        return $this->success([
            'template_id' => $template->template_id,
            'recipient_id' => $recipient->recipient_id,
            'tabs_updated' => count($updatedTabs),
            'tabs' => $updatedTabs,
        ], 'Recipient tabs updated successfully');
    }

    /**
     * Delete tabs from a template recipient
     *
     * DELETE /v2.1/accounts/{accountId}/templates/{templateId}/recipients/{recipientId}/tabs
     *
     * @param Request $request
     * @param string $accountId
     * @param string $templateId
     * @param string $recipientId
     * @return JsonResponse
     */
    public function deleteRecipientTabs(
        Request $request,
        string $accountId,
        string $templateId,
        string $recipientId
    ): JsonResponse {
        $validator = Validator::make($request->all(), [
            'tab_ids' => 'required|array|min:1',
            'tab_ids.*' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $account = Account::where('account_id', $accountId)->firstOrFail();

        $template = Template::where('account_id', $account->id)
            ->where('template_id', $templateId)
            ->firstOrFail();

        $recipient = EnvelopeRecipient::where('template_id', $template->id)
            ->where('recipient_id', $recipientId)
            ->firstOrFail();

        $deletedCount = EnvelopeTab::where('template_id', $template->id)
            ->where('recipient_id', $recipient->id)
            ->whereIn('tab_id', $request->input('tab_ids'))
            ->delete();

        return $this->success([
            'template_id' => $template->template_id,
            'recipient_id' => $recipient->recipient_id,
            'tabs_deleted' => $deletedCount,
        ], 'Recipient tabs deleted successfully');
    }
}
