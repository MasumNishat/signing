<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Http\Controllers\Api\BaseController;
use App\Models\Account;
use App\Models\Envelope;
use App\Models\EnvelopeDocument;
use App\Models\EnvelopeTab;
use App\Services\TabService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * EnvelopeDocumentTabController
 *
 * Manages tabs (form fields) at the envelope document level.
 * Similar to TemplateTabController but for sent envelopes.
 *
 * Total Endpoints: 8 (4 document + 4 recipient)
 */
class EnvelopeDocumentTabController extends BaseController
{
    protected TabService $tabService;

    public function __construct(TabService $tabService)
    {
        $this->tabService = $tabService;
    }

    /**
     * GET /envelopes/{envelopeId}/documents/{documentId}/tabs
     */
    public function getDocumentTabs(string $accountId, string $envelopeId, string $documentId): JsonResponse
    {
        try {
            $account = Account::findOrFail($accountId);
            $envelope = Envelope::where('account_id', $account->id)->where('envelope_id', $envelopeId)->firstOrFail();
            $document = EnvelopeDocument::where('envelope_id', $envelope->id)->where('document_id', $documentId)->firstOrFail();

            $tabs = EnvelopeTab::where('envelope_id', $envelope->id)
                ->where('document_id', $document->id)->get();

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
                'envelope_id' => $envelope->envelope_id,
                'document_id' => $document->document_id,
                'total_tabs' => $tabs->count(),
                'tabs_by_recipient' => $groupedTabs,
            ], 'Document tabs retrieved successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * POST /envelopes/{envelopeId}/documents/{documentId}/tabs
     */
    public function addDocumentTabs(Request $request, string $accountId, string $envelopeId, string $documentId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tabs' => 'required|array|min:1',
            'tabs.*.type' => 'required|string',
            'tabs.*.recipient_id' => 'nullable|string',
            'tabs.*.page_number' => 'required|integer|min:1',
            'tabs.*.x_position' => 'required|numeric',
            'tabs.*.y_position' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $account = Account::findOrFail($accountId);
            $envelope = Envelope::where('account_id', $account->id)->where('envelope_id', $envelopeId)->firstOrFail();

            if (!$envelope->isDraft()) {
                return $this->error('Cannot add tabs to non-draft envelope', 422);
            }

            $document = EnvelopeDocument::where('envelope_id', $envelope->id)->where('document_id', $documentId)->firstOrFail();

            $createdTabs = $this->tabService->createTabs($envelope, $document, $request->tabs);

            return $this->created([
                'envelope_id' => $envelope->envelope_id,
                'document_id' => $document->document_id,
                'tabs_created' => count($createdTabs),
            ], 'Document tabs added successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * PUT /envelopes/{envelopeId}/documents/{documentId}/tabs
     */
    public function updateDocumentTabs(Request $request, string $accountId, string $envelopeId, string $documentId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tabs' => 'required|array|min:1',
            'tabs.*.tab_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $account = Account::findOrFail($accountId);
            $envelope = Envelope::where('account_id', $account->id)->where('envelope_id', $envelopeId)->firstOrFail();
            $document = EnvelopeDocument::where('envelope_id', $envelope->id)->where('document_id', $documentId)->firstOrFail();

            $updatedCount = $this->tabService->updateTabs($envelope, $request->tabs);

            return $this->success([
                'envelope_id' => $envelope->envelope_id,
                'document_id' => $document->document_id,
                'tabs_updated' => $updatedCount,
            ], 'Document tabs updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * DELETE /envelopes/{envelopeId}/documents/{documentId}/tabs
     */
    public function deleteDocumentTabs(Request $request, string $accountId, string $envelopeId, string $documentId): JsonResponse
    {
        try {
            $account = Account::findOrFail($accountId);
            $envelope = Envelope::where('account_id', $account->id)->where('envelope_id', $envelopeId)->firstOrFail();

            if (!$envelope->isDraft()) {
                return $this->error('Cannot delete tabs from non-draft envelope', 422);
            }

            $document = EnvelopeDocument::where('envelope_id', $envelope->id)->where('document_id', $documentId)->firstOrFail();

            $deleted = EnvelopeTab::where('envelope_id', $envelope->id)
                ->where('document_id', $document->id)->delete();

            return $this->success([
                'envelope_id' => $envelope->envelope_id,
                'document_id' => $document->document_id,
                'tabs_deleted' => $deleted,
            ], 'Document tabs deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * GET /envelopes/{envelopeId}/recipients/{recipientId}/tabs
     */
    public function getRecipientTabs(string $accountId, string $envelopeId, string $recipientId): JsonResponse
    {
        try {
            $account = Account::findOrFail($accountId);
            $envelope = Envelope::where('account_id', $account->id)->where('envelope_id', $envelopeId)->firstOrFail();

            $tabs = EnvelopeTab::where('envelope_id', $envelope->id)
                ->where('recipient_id', $recipientId)->get();

            $groupedByType = [];
            foreach ($tabs as $tab) {
                $type = $tab->type;
                if (!isset($groupedByType[$type])) {
                    $groupedByType[$type] = [];
                }
                $groupedByType[$type][] = $this->tabService->getMetadata($tab);
            }

            return $this->success([
                'envelope_id' => $envelope->envelope_id,
                'recipient_id' => $recipientId,
                'total_tabs' => $tabs->count(),
                'tabs_by_type' => $groupedByType,
            ], 'Recipient tabs retrieved successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * POST /envelopes/{envelopeId}/recipients/{recipientId}/tabs
     */
    public function addRecipientTabs(Request $request, string $accountId, string $envelopeId, string $recipientId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tabs' => 'required|array|min:1',
            'tabs.*.type' => 'required|string',
            'tabs.*.document_id' => 'required|string',
            'tabs.*.page_number' => 'required|integer|min:1',
            'tabs.*.x_position' => 'required|numeric',
            'tabs.*.y_position' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $account = Account::findOrFail($accountId);
            $envelope = Envelope::where('account_id', $account->id)->where('envelope_id', $envelopeId)->firstOrFail();

            if (!$envelope->isDraft()) {
                return $this->error('Cannot add tabs to non-draft envelope', 422);
            }

            $createdTabs = $this->tabService->createRecipientTabs($envelope, $recipientId, $request->tabs);

            return $this->created([
                'envelope_id' => $envelope->envelope_id,
                'recipient_id' => $recipientId,
                'tabs_created' => count($createdTabs),
            ], 'Recipient tabs added successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * PUT /envelopes/{envelopeId}/recipients/{recipientId}/tabs
     */
    public function updateRecipientTabs(Request $request, string $accountId, string $envelopeId, string $recipientId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tabs' => 'required|array|min:1',
            'tabs.*.tab_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $account = Account::findOrFail($accountId);
            $envelope = Envelope::where('account_id', $account->id)->where('envelope_id', $envelopeId)->firstOrFail();

            $updatedCount = $this->tabService->updateTabs($envelope, $request->tabs);

            return $this->success([
                'envelope_id' => $envelope->envelope_id,
                'recipient_id' => $recipientId,
                'tabs_updated' => $updatedCount,
            ], 'Recipient tabs updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * DELETE /envelopes/{envelopeId}/recipients/{recipientId}/tabs
     */
    public function deleteRecipientTabs(string $accountId, string $envelopeId, string $recipientId): JsonResponse
    {
        try {
            $account = Account::findOrFail($accountId);
            $envelope = Envelope::where('account_id', $account->id)->where('envelope_id', $envelopeId)->firstOrFail();

            if (!$envelope->isDraft()) {
                return $this->error('Cannot delete tabs from non-draft envelope', 422);
            }

            $deleted = EnvelopeTab::where('envelope_id', $envelope->id)
                ->where('recipient_id', $recipientId)->delete();

            return $this->success([
                'envelope_id' => $envelope->envelope_id,
                'recipient_id' => $recipientId,
                'tabs_deleted' => $deleted,
            ], 'Recipient tabs deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
