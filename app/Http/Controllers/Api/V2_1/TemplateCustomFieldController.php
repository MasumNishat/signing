<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Models\Template;
use App\Models\EnvelopeCustomField;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * TemplateCustomFieldController
 *
 * Handles template custom field operations.
 * Templates reuse envelope_custom_fields table with template_id column.
 */
class TemplateCustomFieldController extends BaseController
{
    /**
     * GET /accounts/{accountId}/templates/{templateId}/custom_fields
     *
     * Get all custom fields for a template
     */
    public function index(string $accountId, string $templateId): JsonResponse
    {
        try {
            $template = Template::where('account_id', $accountId)
                ->where('template_id', $templateId)
                ->with('customFields')
                ->firstOrFail();

            return $this->successResponse([
                'text_custom_fields' => $template->customFields
                    ->where('field_type', 'text')
                    ->values()
                    ->map(fn($field) => $this->formatCustomField($field)),
                'list_custom_fields' => $template->customFields
                    ->where('field_type', 'list')
                    ->values()
                    ->map(fn($field) => $this->formatCustomField($field)),
            ], 'Template custom fields retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /accounts/{accountId}/templates/{templateId}/custom_fields
     *
     * Create custom fields for a template
     */
    public function store(Request $request, string $accountId, string $templateId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'text_custom_fields' => 'sometimes|array',
                'text_custom_fields.*.field_id' => 'sometimes|string|max:100',
                'text_custom_fields.*.name' => 'required|string|max:255',
                'text_custom_fields.*.value' => 'sometimes|string|max:1000',
                'text_custom_fields.*.show' => 'sometimes|boolean',
                'text_custom_fields.*.required' => 'sometimes|boolean',
                'list_custom_fields' => 'sometimes|array',
                'list_custom_fields.*.field_id' => 'sometimes|string|max:100',
                'list_custom_fields.*.name' => 'required|string|max:255',
                'list_custom_fields.*.value' => 'sometimes|string|max:1000',
                'list_custom_fields.*.show' => 'sometimes|boolean',
                'list_custom_fields.*.required' => 'sometimes|boolean',
                'list_custom_fields.*.list_items' => 'sometimes|array',
            ]);

            $template = Template::where('account_id', $accountId)
                ->where('template_id', $templateId)
                ->firstOrFail();

            DB::beginTransaction();
            try {
                $createdFields = [];

                // Create text custom fields
                if (!empty($validated['text_custom_fields'])) {
                    foreach ($validated['text_custom_fields'] as $fieldData) {
                        $field = EnvelopeCustomField::create([
                            'template_id' => $template->id,
                            'field_id' => $fieldData['field_id'] ?? null,
                            'name' => $fieldData['name'],
                            'value' => $fieldData['value'] ?? null,
                            'field_type' => 'text',
                            'show' => $fieldData['show'] ?? true,
                            'required' => $fieldData['required'] ?? false,
                        ]);
                        $createdFields[] = $field;
                    }
                }

                // Create list custom fields
                if (!empty($validated['list_custom_fields'])) {
                    foreach ($validated['list_custom_fields'] as $fieldData) {
                        $field = EnvelopeCustomField::create([
                            'template_id' => $template->id,
                            'field_id' => $fieldData['field_id'] ?? null,
                            'name' => $fieldData['name'],
                            'value' => $fieldData['value'] ?? null,
                            'field_type' => 'list',
                            'show' => $fieldData['show'] ?? true,
                            'required' => $fieldData['required'] ?? false,
                            'list_items' => $fieldData['list_items'] ?? null,
                        ]);
                        $createdFields[] = $field;
                    }
                }

                DB::commit();

                $createdFieldsCollection = collect($createdFields);
                return $this->createdResponse([
                    'text_custom_fields' => $createdFieldsCollection
                        ->where('field_type', 'text')
                        ->values()
                        ->map(fn($field) => $this->formatCustomField($field)),
                    'list_custom_fields' => $createdFieldsCollection
                        ->where('field_type', 'list')
                        ->values()
                        ->map(fn($field) => $this->formatCustomField($field)),
                ], 'Template custom fields created successfully');
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * PUT /accounts/{accountId}/templates/{templateId}/custom_fields
     *
     * Update custom fields for a template
     */
    public function update(Request $request, string $accountId, string $templateId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'text_custom_fields' => 'sometimes|array',
                'text_custom_fields.*.field_id' => 'required|string|max:100',
                'text_custom_fields.*.name' => 'sometimes|string|max:255',
                'text_custom_fields.*.value' => 'sometimes|string|max:1000',
                'text_custom_fields.*.show' => 'sometimes|boolean',
                'text_custom_fields.*.required' => 'sometimes|boolean',
                'list_custom_fields' => 'sometimes|array',
                'list_custom_fields.*.field_id' => 'required|string|max:100',
                'list_custom_fields.*.name' => 'sometimes|string|max:255',
                'list_custom_fields.*.value' => 'sometimes|string|max:1000',
                'list_custom_fields.*.show' => 'sometimes|boolean',
                'list_custom_fields.*.required' => 'sometimes|boolean',
                'list_custom_fields.*.list_items' => 'sometimes|array',
            ]);

            $template = Template::where('account_id', $accountId)
                ->where('template_id', $templateId)
                ->firstOrFail();

            DB::beginTransaction();
            try {
                $updatedFields = [];

                // Update text custom fields
                if (!empty($validated['text_custom_fields'])) {
                    foreach ($validated['text_custom_fields'] as $fieldData) {
                        $field = EnvelopeCustomField::where('template_id', $template->id)
                            ->where('field_id', $fieldData['field_id'])
                            ->where('field_type', 'text')
                            ->firstOrFail();

                        $field->update(array_filter([
                            'name' => $fieldData['name'] ?? null,
                            'value' => $fieldData['value'] ?? null,
                            'show' => $fieldData['show'] ?? null,
                            'required' => $fieldData['required'] ?? null,
                        ], fn($value) => $value !== null));

                        $updatedFields[] = $field->fresh();
                    }
                }

                // Update list custom fields
                if (!empty($validated['list_custom_fields'])) {
                    foreach ($validated['list_custom_fields'] as $fieldData) {
                        $field = EnvelopeCustomField::where('template_id', $template->id)
                            ->where('field_id', $fieldData['field_id'])
                            ->where('field_type', 'list')
                            ->firstOrFail();

                        $field->update(array_filter([
                            'name' => $fieldData['name'] ?? null,
                            'value' => $fieldData['value'] ?? null,
                            'show' => $fieldData['show'] ?? null,
                            'required' => $fieldData['required'] ?? null,
                            'list_items' => $fieldData['list_items'] ?? null,
                        ], fn($value) => $value !== null));

                        $updatedFields[] = $field->fresh();
                    }
                }

                DB::commit();

                $updatedFieldsCollection = collect($updatedFields);
                return $this->successResponse([
                    'text_custom_fields' => $updatedFieldsCollection
                        ->where('field_type', 'text')
                        ->values()
                        ->map(fn($field) => $this->formatCustomField($field)),
                    'list_custom_fields' => $updatedFieldsCollection
                        ->where('field_type', 'list')
                        ->values()
                        ->map(fn($field) => $this->formatCustomField($field)),
                ], 'Template custom fields updated successfully');
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * DELETE /accounts/{accountId}/templates/{templateId}/custom_fields
     *
     * Delete custom fields from a template
     */
    public function destroy(string $accountId, string $templateId): JsonResponse
    {
        try {
            $template = Template::where('account_id', $accountId)
                ->where('template_id', $templateId)
                ->firstOrFail();

            $count = EnvelopeCustomField::where('template_id', $template->id)->delete();

            return $this->successResponse([
                'deleted_count' => $count,
            ], 'Template custom fields deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Format custom field for response
     */
    protected function formatCustomField(EnvelopeCustomField $field): array
    {
        $formatted = [
            'field_id' => $field->field_id,
            'name' => $field->name,
            'value' => $field->value,
            'show' => $field->show,
            'required' => $field->required,
        ];

        if ($field->field_type === 'list' && $field->list_items) {
            $formatted['list_items'] = $field->list_items;
        }

        return $formatted;
    }
}
