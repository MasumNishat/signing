<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Models\Template;
use App\Models\EnvelopeLock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * TemplateLockController
 *
 * Handles template lock operations to prevent concurrent editing.
 * Templates reuse envelope_locks table with template_id column.
 */
class TemplateLockController extends BaseController
{
    /**
     * GET /accounts/{accountId}/templates/{templateId}/lock
     *
     * Get template lock status
     */
    public function show(string $accountId, string $templateId): JsonResponse
    {
        try {
            $template = Template::where('account_id', $accountId)
                ->where('template_id', $templateId)
                ->firstOrFail();

            $lock = EnvelopeLock::where('template_id', $template->id)
                ->where('locked_until', '>', now())
                ->first();

            if (!$lock) {
                return $this->successResponse([
                    'is_locked' => false,
                    'message' => 'Template is not locked',
                ], 'Template lock status retrieved successfully');
            }

            return $this->successResponse([
                'is_locked' => true,
                'locked_by_user_id' => $lock->locked_by_user_id,
                'locked_by_user_name' => $lock->locked_by_user_name,
                'lock_token' => $lock->lock_token,
                'locked_until' => $lock->locked_until->toIso8601String(),
                'lock_duration_seconds' => $lock->lock_duration_seconds,
                'created_at' => $lock->created_at?->toIso8601String(),
            ], 'Template lock status retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /accounts/{accountId}/templates/{templateId}/lock
     *
     * Create a lock on the template
     */
    public function store(Request $request, string $accountId, string $templateId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'lock_duration_seconds' => 'sometimes|integer|min:60|max:3600',
                'locked_by_user_name' => 'sometimes|string|max:255',
            ]);

            $template = Template::where('account_id', $accountId)
                ->where('template_id', $templateId)
                ->firstOrFail();

            // Check if template is already locked
            $existingLock = EnvelopeLock::where('template_id', $template->id)
                ->where('locked_until', '>', now())
                ->first();

            if ($existingLock) {
                return $this->errorResponse(
                    'Template is already locked by another user',
                    409,
                    [
                        'locked_by_user_id' => $existingLock->locked_by_user_id,
                        'locked_by_user_name' => $existingLock->locked_by_user_name,
                        'locked_until' => $existingLock->locked_until->toIso8601String(),
                    ]
                );
            }

            $lockDuration = $validated['lock_duration_seconds'] ?? 300; // Default 5 minutes
            $lockToken = (string) Str::uuid();

            $lock = EnvelopeLock::create([
                'template_id' => $template->id,
                'locked_by_user_id' => auth()->id(),
                'locked_by_user_name' => $validated['locked_by_user_name'] ?? auth()->user()?->name ?? 'Unknown User',
                'lock_token' => $lockToken,
                'locked_until' => now()->addSeconds($lockDuration),
                'lock_duration_seconds' => $lockDuration,
            ]);

            return $this->createdResponse([
                'lock_token' => $lock->lock_token,
                'locked_until' => $lock->locked_until->toIso8601String(),
                'lock_duration_seconds' => $lock->lock_duration_seconds,
            ], 'Template locked successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * PUT /accounts/{accountId}/templates/{templateId}/lock
     *
     * Update (extend) the template lock
     */
    public function update(Request $request, string $accountId, string $templateId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'lock_token' => 'required|string|uuid',
                'lock_duration_seconds' => 'sometimes|integer|min:60|max:3600',
            ]);

            $template = Template::where('account_id', $accountId)
                ->where('template_id', $templateId)
                ->firstOrFail();

            $lock = EnvelopeLock::where('template_id', $template->id)
                ->where('lock_token', $validated['lock_token'])
                ->firstOrFail();

            // Verify the lock belongs to the current user
            if ($lock->locked_by_user_id != auth()->id()) {
                return $this->forbiddenResponse('You do not own this lock');
            }

            $lockDuration = $validated['lock_duration_seconds'] ?? 300;
            $lock->update([
                'locked_until' => now()->addSeconds($lockDuration),
                'lock_duration_seconds' => $lockDuration,
            ]);

            return $this->successResponse([
                'lock_token' => $lock->lock_token,
                'locked_until' => $lock->locked_until->toIso8601String(),
                'lock_duration_seconds' => $lock->lock_duration_seconds,
            ], 'Template lock extended successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * DELETE /accounts/{accountId}/templates/{templateId}/lock
     *
     * Release the template lock
     */
    public function destroy(Request $request, string $accountId, string $templateId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'lock_token' => 'required|string|uuid',
            ]);

            $template = Template::where('account_id', $accountId)
                ->where('template_id', $templateId)
                ->firstOrFail();

            $lock = EnvelopeLock::where('template_id', $template->id)
                ->where('lock_token', $validated['lock_token'])
                ->firstOrFail();

            // Verify the lock belongs to the current user
            if ($lock->locked_by_user_id != auth()->id()) {
                return $this->forbiddenResponse('You do not own this lock');
            }

            $lock->delete();

            return $this->noContentResponse();
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
