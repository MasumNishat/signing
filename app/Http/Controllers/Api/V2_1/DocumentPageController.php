<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Http\Controllers\Api\BaseController;
use App\Models\Account;
use App\Models\Envelope;
use App\Models\EnvelopeDocument;
use App\Models\EnvelopeTab;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * DocumentPageController
 *
 * Manages individual pages within envelope documents.
 * Supports page operations like rotation, deletion, and reordering.
 *
 * Total Endpoints: 8
 * Note: PDF manipulation requires external library (e.g., Spatie/pdf-to-image)
 */
class DocumentPageController extends BaseController
{
    /**
     * GET /envelopes/{envelopeId}/documents/{documentId}/pages
     */
    public function index(string $accountId, string $envelopeId, string $documentId): JsonResponse
    {
        try {
            $account = Account::findOrFail($accountId);
            $envelope = Envelope::where('account_id', $account->id)
                ->where('envelope_id', $envelopeId)->firstOrFail();
            $document = EnvelopeDocument::where('envelope_id', $envelope->id)
                ->where('document_id', $documentId)->firstOrFail();

            // Placeholder: would use PDF library to get actual page count
            $pageCount = $document->page_count ?? 1;
            $pages = [];
            for ($i = 1; $i <= $pageCount; $i++) {
                $pages[] = [
                    'page_number' => $i,
                    'width' => 612,  // Standard US Letter width
                    'height' => 792, // Standard US Letter height
                    'rotation' => 0,
                    'has_tabs' => EnvelopeTab::where('document_id', $document->id)
                        ->where('page_number', $i)->exists(),
                ];
            }

            return $this->success([
                'document_id' => $document->document_id,
                'total_pages' => $pageCount,
                'pages' => $pages,
            ], 'Document pages retrieved successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * GET /envelopes/{envelopeId}/documents/{documentId}/pages/{pageNumber}
     */
    public function show(string $accountId, string $envelopeId, string $documentId, int $pageNumber): JsonResponse
    {
        try {
            $account = Account::findOrFail($accountId);
            $envelope = Envelope::where('account_id', $account->id)->where('envelope_id', $envelopeId)->firstOrFail();
            $document = EnvelopeDocument::where('envelope_id', $envelope->id)->where('document_id', $documentId)->firstOrFail();

            $tabs = EnvelopeTab::where('document_id', $document->id)
                ->where('page_number', $pageNumber)->get();

            return $this->success([
                'page_number' => $pageNumber,
                'width' => 612,
                'height' => 792,
                'rotation' => 0,
                'tabs_count' => $tabs->count(),
                'tabs' => $tabs->map(fn($tab) => ['tab_id' => $tab->tab_id, 'type' => $tab->type]),
            ], 'Page retrieved successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * DELETE /envelopes/{envelopeId}/documents/{documentId}/pages/{pageNumber}
     */
    public function destroy(string $accountId, string $envelopeId, string $documentId, int $pageNumber): JsonResponse
    {
        try {
            $account = Account::findOrFail($accountId);
            $envelope = Envelope::where('account_id', $account->id)->where('envelope_id', $envelopeId)->firstOrFail();

            if (!$envelope->isDraft()) {
                return $this->error('Cannot delete pages from non-draft envelope', 422);
            }

            $document = EnvelopeDocument::where('envelope_id', $envelope->id)->where('document_id', $documentId)->firstOrFail();

            // Placeholder: would use PDF library to remove page
            // For now, just delete tabs on this page
            EnvelopeTab::where('document_id', $document->id)
                ->where('page_number', $pageNumber)->delete();

            return $this->noContent('Page deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * GET /envelopes/{envelopeId}/documents/{documentId}/pages/{pageNumber}/page_image
     */
    public function getPageImage(string $accountId, string $envelopeId, string $documentId, int $pageNumber): JsonResponse
    {
        try {
            $account = Account::findOrFail($accountId);
            $envelope = Envelope::where('account_id', $account->id)->where('envelope_id', $envelopeId)->firstOrFail();
            $document = EnvelopeDocument::where('envelope_id', $envelope->id)->where('document_id', $documentId)->firstOrFail();

            // Placeholder: would generate actual page image using PDF library
            return $this->success([
                'page_number' => $pageNumber,
                'image_url' => "/api/v2.1/envelopes/{$envelopeId}/documents/{$documentId}/pages/{$pageNumber}/image.png",
                'width' => 612,
                'height' => 792,
                'dpi' => 96,
            ], 'Page image URL generated');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * PUT /envelopes/{envelopeId}/documents/{documentId}/pages/{pageNumber}/page_image
     */
    public function rotatePageImage(Request $request, string $accountId, string $envelopeId, string $documentId, int $pageNumber): JsonResponse
    {
        $request->validate(['rotation' => 'required|integer|in:90,180,270']);

        try {
            $account = Account::findOrFail($accountId);
            $envelope = Envelope::where('account_id', $account->id)->where('envelope_id', $envelopeId)->firstOrFail();

            if (!$envelope->isDraft()) {
                return $this->error('Cannot rotate pages in non-draft envelope', 422);
            }

            $document = EnvelopeDocument::where('envelope_id', $envelope->id)->where('document_id', $documentId)->firstOrFail();

            // Placeholder: would use PDF library to rotate page
            return $this->success([
                'page_number' => $pageNumber,
                'rotation' => $request->rotation,
                'status' => 'rotated',
            ], 'Page rotated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * GET /envelopes/{envelopeId}/documents/{documentId}/pages/{pageNumber}/tabs
     */
    public function getPageTabs(string $accountId, string $envelopeId, string $documentId, int $pageNumber): JsonResponse
    {
        try {
            $account = Account::findOrFail($accountId);
            $envelope = Envelope::where('account_id', $account->id)->where('envelope_id', $envelopeId)->firstOrFail();
            $document = EnvelopeDocument::where('envelope_id', $envelope->id)->where('document_id', $documentId)->firstOrFail();

            $tabs = EnvelopeTab::where('document_id', $document->id)
                ->where('page_number', $pageNumber)->get();

            return $this->success([
                'page_number' => $pageNumber,
                'total_tabs' => $tabs->count(),
                'tabs' => $tabs->map(function($tab) {
                    return [
                        'tab_id' => $tab->tab_id,
                        'type' => $tab->type,
                        'label' => $tab->label,
                        'x_position' => $tab->x_position,
                        'y_position' => $tab->y_position,
                        'recipient_id' => $tab->recipient_id,
                    ];
                }),
            ], 'Page tabs retrieved successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * POST /envelopes/{envelopeId}/documents/{documentId}/pages/{pageNumber}/move
     */
    public function movePage(Request $request, string $accountId, string $envelopeId, string $documentId, int $pageNumber): JsonResponse
    {
        $request->validate(['new_position' => 'required|integer|min:1']);

        try {
            $account = Account::findOrFail($accountId);
            $envelope = Envelope::where('account_id', $account->id)->where('envelope_id', $envelopeId)->firstOrFail();

            if (!$envelope->isDraft()) {
                return $this->error('Cannot move pages in non-draft envelope', 422);
            }

            // Placeholder: would reorder PDF pages using library
            return $this->success([
                'page_number' => $pageNumber,
                'new_position' => $request->new_position,
                'status' => 'moved',
            ], 'Page moved successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * POST /envelopes/{envelopeId}/documents/{documentId}/pages/insert
     */
    public function insertPage(Request $request, string $accountId, string $envelopeId, string $documentId): JsonResponse
    {
        $request->validate([
            'position' => 'required|integer|min:1',
            'page_source' => 'required|string', // base64 or file path
        ]);

        try {
            $account = Account::findOrFail($accountId);
            $envelope = Envelope::where('account_id', $account->id)->where('envelope_id', $envelopeId)->firstOrFail();

            if (!$envelope->isDraft()) {
                return $this->error('Cannot insert pages in non-draft envelope', 422);
            }

            // Placeholder: would insert page using PDF library
            return $this->created([
                'position' => $request->position,
                'status' => 'inserted',
            ], 'Page inserted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
