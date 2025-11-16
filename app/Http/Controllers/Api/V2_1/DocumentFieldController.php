<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Http\Controllers\Api\BaseController;
use App\Models\Account;
use App\Models\Envelope;
use App\Models\EnvelopeDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * DocumentFieldController
 *
 * Manages custom fields at the document level for envelopes.
 * Document fields are metadata fields that can be filled during signing.
 *
 * Total Endpoints: 4
 */
class DocumentFieldController extends BaseController
{
    /**
     * GET /envelopes/{envelopeId}/documents/{documentId}/fields
     *
     * Get all fields for a document
     */
    public function index(
        string $accountId,
        string $envelopeId,
        string $documentId
    ): JsonResponse {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();
            $envelope = Envelope::where('account_id', $account->id)
                ->where('envelope_id', $envelopeId)
                ->firstOrFail();

            $document = EnvelopeDocument::where('envelope_id', $envelope->id)
                ->where('document_id', $documentId)
                ->firstOrFail();

            $fields = $document->custom_fields ?? [];

            return $this->success([
                'document_id' => $document->document_id,
                'document_name' => $document->name,
                'fields' => $fields,
                'total_fields' => count($fields),
            ], 'Document fields retrieved successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to retrieve document fields: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /envelopes/{envelopeId}/documents/{documentId}/fields
     *
     * Create new fields for a document
     */
    public function store(
        Request $request,
        string $accountId,
        string $envelopeId,
        string $documentId
    ): JsonResponse {
        $validator = Validator::make($request->all(), [
            'fields' => 'required|array|min:1',
            'fields.*.name' => 'required|string|max:255',
            'fields.*.value' => 'nullable|string',
            'fields.*.type' => 'required|string|in:text,date,number,list,checkbox',
            'fields.*.required' => 'nullable|boolean',
            'fields.*.list_items' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();
            $envelope = Envelope::where('account_id', $account->id)
                ->where('envelope_id', $envelopeId)
                ->firstOrFail();

            if (!$envelope->isDraft()) {
                return $this->error('Cannot add fields to non-draft envelope', 422);
            }

            $document = EnvelopeDocument::where('envelope_id', $envelope->id)
                ->where('document_id', $documentId)
                ->firstOrFail();

            $existingFields = $document->custom_fields ?? [];
            $newFields = $request->fields;

            // Merge new fields with existing
            $allFields = array_merge($existingFields, $newFields);
            $document->custom_fields = $allFields;
            $document->save();

            return $this->created([
                'document_id' => $document->document_id,
                'fields' => $allFields,
                'total_fields' => count($allFields),
            ], 'Document fields created successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to create document fields: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /envelopes/{envelopeId}/documents/{documentId}/fields
     *
     * Update fields for a document
     */
    public function update(
        Request $request,
        string $accountId,
        string $envelopeId,
        string $documentId
    ): JsonResponse {
        $validator = Validator::make($request->all(), [
            'fields' => 'required|array',
            'fields.*.name' => 'required|string|max:255',
            'fields.*.value' => 'nullable|string',
            'fields.*.type' => 'required|string|in:text,date,number,list,checkbox',
            'fields.*.required' => 'nullable|boolean',
            'fields.*.list_items' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();
            $envelope = Envelope::where('account_id', $account->id)
                ->where('envelope_id', $envelopeId)
                ->firstOrFail();

            $document = EnvelopeDocument::where('envelope_id', $envelope->id)
                ->where('document_id', $documentId)
                ->firstOrFail();

            $document->custom_fields = $request->fields;
            $document->save();

            return $this->success([
                'document_id' => $document->document_id,
                'fields' => $document->custom_fields,
                'total_fields' => count($document->custom_fields),
            ], 'Document fields updated successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to update document fields: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /envelopes/{envelopeId}/documents/{documentId}/fields
     *
     * Delete all fields from a document
     */
    public function destroy(
        string $accountId,
        string $envelopeId,
        string $documentId
    ): JsonResponse {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();
            $envelope = Envelope::where('account_id', $account->id)
                ->where('envelope_id', $envelopeId)
                ->firstOrFail();

            if (!$envelope->isDraft()) {
                return $this->error('Cannot delete fields from non-draft envelope', 422);
            }

            $document = EnvelopeDocument::where('envelope_id', $envelope->id)
                ->where('document_id', $documentId)
                ->firstOrFail();

            $document->custom_fields = null;
            $document->save();

            return $this->noContent('Document fields deleted successfully');

        } catch (\Exception $e) {
            return $this->error('Failed to delete document fields: ' . $e->getMessage(), 500);
        }
    }
}
