<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Http\Controllers\Api\BaseController;
use App\Models\Account;
use App\Models\Envelope;
use App\Services\EnvelopeDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Envelope Download Controller
 *
 * Handles envelope document downloads, PDF generation, and certificates.
 *
 * Endpoints:
 * - GET  /envelopes/{id}/documents/combined          - Download combined PDF
 * - GET  /envelopes/{id}/documents/{docId}/download  - Download specific document
 * - GET  /envelopes/{id}/certificate                 - Get certificate of completion
 * - GET  /envelopes/{id}/form_data                   - Get extracted form data
 */
class EnvelopeDownloadController extends BaseController
{
    /**
     * Envelope document service
     */
    protected EnvelopeDocumentService $envelopeDocumentService;

    /**
     * Initialize controller
     */
    public function __construct(EnvelopeDocumentService $envelopeDocumentService)
    {
        $this->envelopeDocumentService = $envelopeDocumentService;
    }

    /**
     * Download envelope as combined PDF
     *
     * GET /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/combined
     *
     * @param Request $request
     * @param string $accountId
     * @param string $envelopeId
     * @return JsonResponse
     */
    public function downloadCombinedPdf(Request $request, string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'include_certificate' => 'nullable|boolean',
            'watermark' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $pdfData = $this->envelopeDocumentService->downloadEnvelopePdf($envelope, [
                'include_certificate' => $request->input('include_certificate', true),
                'watermark' => $request->input('watermark'),
            ]);

            return $this->success($pdfData, 'Envelope PDF download URL generated');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Download specific document
     *
     * GET /v2.1/accounts/{accountId}/envelopes/{envelopeId}/documents/{documentId}/download
     *
     * @param Request $request
     * @param string $accountId
     * @param string $envelopeId
     * @param string $documentId
     * @return JsonResponse
     */
    public function downloadDocument(Request $request, string $accountId, string $envelopeId, string $documentId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'include_certificate' => 'nullable|boolean',
            'show_changes' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $documentData = $this->envelopeDocumentService->downloadDocument($envelope, $documentId, [
                'include_certificate' => $request->input('include_certificate', false),
                'show_changes' => $request->input('show_changes', false),
            ]);

            return $this->success($documentData, 'Document download URL generated');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get certificate of completion
     *
     * GET /v2.1/accounts/{accountId}/envelopes/{envelopeId}/certificate
     *
     * @param string $accountId
     * @param string $envelopeId
     * @return JsonResponse
     */
    public function certificate(string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        try {
            $certificateData = $this->envelopeDocumentService->generateCertificate($envelope);

            return $this->success($certificateData, 'Certificate of completion generated');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get envelope form data
     *
     * GET /v2.1/accounts/{accountId}/envelopes/{envelopeId}/form_data
     *
     * @param string $accountId
     * @param string $envelopeId
     * @return JsonResponse
     */
    public function formData(string $accountId, string $envelopeId): JsonResponse
    {
        $account = Account::where('account_id', $accountId)->firstOrFail();

        $envelope = Envelope::where('account_id', $account->id)
            ->where('envelope_id', $envelopeId)
            ->firstOrFail();

        try {
            $formData = $this->envelopeDocumentService->getEnvelopeFormData($envelope);

            return $this->success($formData, 'Form data retrieved successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
