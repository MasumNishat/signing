<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * TemplateNotificationController
 *
 * Handles template notification settings.
 * Notification settings are stored in the templates table.
 */
class TemplateNotificationController extends BaseController
{
    /**
     * GET /accounts/{accountId}/templates/{templateId}/notification
     *
     * Get template notification settings
     */
    public function show(string $accountId, string $templateId): JsonResponse
    {
        try {
            $template = Template::where('account_id', $accountId)
                ->where('template_id', $templateId)
                ->firstOrFail();

            return $this->successResponse([
                'email_subject' => $template->email_subject,
                'email_blurb' => $template->email_blurb,
                'reminder_enabled' => $template->reminder_enabled ?? false,
                'reminder_delay' => $template->reminder_delay,
                'reminder_frequency' => $template->reminder_frequency,
                'expiration_enabled' => $template->expiration_enabled ?? false,
                'expiration_after' => $template->expiration_after,
                'expiration_warn' => $template->expiration_warn,
            ], 'Template notification settings retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * PUT /accounts/{accountId}/templates/{templateId}/notification
     *
     * Update template notification settings
     */
    public function update(Request $request, string $accountId, string $templateId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email_subject' => 'sometimes|string|max:255',
                'email_blurb' => 'sometimes|string|max:1000',
                'reminder_enabled' => 'sometimes|boolean',
                'reminder_delay' => 'sometimes|integer|min:1|max:999',
                'reminder_frequency' => 'sometimes|integer|min:1|max:999',
                'expiration_enabled' => 'sometimes|boolean',
                'expiration_after' => 'sometimes|integer|min:1|max:999',
                'expiration_warn' => 'sometimes|integer|min:1|max:999',
            ]);

            $template = Template::where('account_id', $accountId)
                ->where('template_id', $templateId)
                ->firstOrFail();

            // Validation: if reminder/expiration is enabled, required fields must be set
            if (isset($validated['reminder_enabled']) && $validated['reminder_enabled']) {
                if (!isset($validated['reminder_delay']) && !$template->reminder_delay) {
                    return $this->validationErrorResponse([
                        'reminder_delay' => ['Reminder delay is required when reminders are enabled']
                    ]);
                }
                if (!isset($validated['reminder_frequency']) && !$template->reminder_frequency) {
                    return $this->validationErrorResponse([
                        'reminder_frequency' => ['Reminder frequency is required when reminders are enabled']
                    ]);
                }
            }

            if (isset($validated['expiration_enabled']) && $validated['expiration_enabled']) {
                if (!isset($validated['expiration_after']) && !$template->expiration_after) {
                    return $this->validationErrorResponse([
                        'expiration_after' => ['Expiration after is required when expiration is enabled']
                    ]);
                }
            }

            $template->update($validated);

            return $this->successResponse([
                'email_subject' => $template->email_subject,
                'email_blurb' => $template->email_blurb,
                'reminder_enabled' => $template->reminder_enabled ?? false,
                'reminder_delay' => $template->reminder_delay,
                'reminder_frequency' => $template->reminder_frequency,
                'expiration_enabled' => $template->expiration_enabled ?? false,
                'expiration_after' => $template->expiration_after,
                'expiration_warn' => $template->expiration_warn,
            ], 'Template notification settings updated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
