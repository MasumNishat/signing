<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Models\Account;
use App\Models\CaptiveRecipient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CaptiveRecipientController
 *
 * Manages captive recipients (embedded signers) for an account.
 * Captive recipients are pre-configured contacts used for embedded signing workflows.
 *
 * Total Endpoints: 5
 */
class CaptiveRecipientController extends BaseController
{
    /**
     * GET /accounts/{accountId}/captive_recipients
     *
     * List all captive recipients for an account
     */
    public function index(Request $request, string $accountId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'count' => 'sometimes|integer|min:1|max:100',
                'start_position' => 'sometimes|integer|min:0',
                'email' => 'sometimes|string|max:255',
            ]);

            $account = Account::where('account_id', $accountId)->firstOrFail();

            $query = CaptiveRecipient::where('account_id', $account->id);

            // Filter by email if provided
            if (isset($validated['email'])) {
                $query->byEmail($validated['email']);
            }

            $count = $validated['count'] ?? 20;
            $startPosition = $validated['start_position'] ?? 0;

            $totalRecords = $query->count();
            $captiveRecipients = $query
                ->orderBy('email')
                ->skip($startPosition)
                ->take($count)
                ->get();

            return $this->successResponse([
                'captive_recipients' => $captiveRecipients->map(function ($recipient) {
                    return [
                        'captive_recipient_id' => $recipient->id,
                        'recipient_part' => $recipient->recipient_part,
                        'email' => $recipient->email,
                        'user_name' => $recipient->user_name,
                        'created_at' => $recipient->created_at->toIso8601String(),
                    ];
                }),
                'result_set_size' => $captiveRecipients->count(),
                'start_position' => $startPosition,
                'total_set_size' => $totalRecords,
                'end_position' => min($startPosition + $captiveRecipients->count(), $totalRecords),
            ], 'Captive recipients retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /accounts/{accountId}/captive_recipients
     *
     * Add new captive recipients
     */
    public function store(Request $request, string $accountId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'captive_recipients' => 'required|array|min:1',
                'captive_recipients.*.recipient_part' => 'required|string|max:255',
                'captive_recipients.*.email' => 'required|email|max:255',
                'captive_recipients.*.user_name' => 'sometimes|string|max:255',
            ]);

            $account = Account::where('account_id', $accountId)->firstOrFail();

            DB::beginTransaction();

            try {
                $createdRecipients = [];

                foreach ($validated['captive_recipients'] as $recipientData) {
                    // Check if recipient already exists
                    $existing = CaptiveRecipient::where('account_id', $account->id)
                        ->where('email', $recipientData['email'])
                        ->where('recipient_part', $recipientData['recipient_part'])
                        ->first();

                    if ($existing) {
                        // Update existing recipient
                        $existing->update([
                            'user_name' => $recipientData['user_name'] ?? $existing->user_name,
                        ]);
                        $createdRecipients[] = $existing;
                    } else {
                        // Create new recipient
                        $recipient = CaptiveRecipient::create([
                            'account_id' => $account->id,
                            'recipient_part' => $recipientData['recipient_part'],
                            'email' => $recipientData['email'],
                            'user_name' => $recipientData['user_name'] ?? null,
                        ]);
                        $createdRecipients[] = $recipient;
                    }
                }

                DB::commit();

                return $this->createdResponse([
                    'captive_recipients' => collect($createdRecipients)->map(function ($recipient) {
                        return [
                            'captive_recipient_id' => $recipient->id,
                            'recipient_part' => $recipient->recipient_part,
                            'email' => $recipient->email,
                            'user_name' => $recipient->user_name,
                            'created_at' => $recipient->created_at->toIso8601String(),
                        ];
                    }),
                    'total_created' => count($createdRecipients),
                ], 'Captive recipients created successfully');
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /accounts/{accountId}/captive_recipients/{recipientId}
     *
     * Get a specific captive recipient
     */
    public function show(string $accountId, string $recipientId): JsonResponse
    {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();

            $captiveRecipient = CaptiveRecipient::where('account_id', $account->id)
                ->where('id', $recipientId)
                ->firstOrFail();

            return $this->successResponse([
                'captive_recipient_id' => $captiveRecipient->id,
                'recipient_part' => $captiveRecipient->recipient_part,
                'email' => $captiveRecipient->email,
                'user_name' => $captiveRecipient->user_name,
                'created_at' => $captiveRecipient->created_at->toIso8601String(),
                'updated_at' => $captiveRecipient->updated_at->toIso8601String(),
            ], 'Captive recipient retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * PUT /accounts/{accountId}/captive_recipients/{recipientId}
     *
     * Update a captive recipient
     */
    public function update(Request $request, string $accountId, string $recipientId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'recipient_part' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|max:255',
                'user_name' => 'sometimes|string|max:255',
            ]);

            $account = Account::where('account_id', $accountId)->firstOrFail();

            $captiveRecipient = CaptiveRecipient::where('account_id', $account->id)
                ->where('id', $recipientId)
                ->firstOrFail();

            DB::beginTransaction();

            try {
                $captiveRecipient->update($validated);
                DB::commit();

                return $this->successResponse([
                    'captive_recipient_id' => $captiveRecipient->id,
                    'recipient_part' => $captiveRecipient->recipient_part,
                    'email' => $captiveRecipient->email,
                    'user_name' => $captiveRecipient->user_name,
                    'updated_at' => $captiveRecipient->updated_at->toIso8601String(),
                ], 'Captive recipient updated successfully');
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * DELETE /accounts/{accountId}/captive_recipients/{recipientPart}
     *
     * Deletes the signature for one or more captive recipient records
     * (removes captive recipients by recipient_part identifier)
     */
    public function destroy(string $accountId, string $recipientPart): JsonResponse
    {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();

            // Delete all captive recipients with this recipient_part
            $deleted = CaptiveRecipient::where('account_id', $account->id)
                ->where('recipient_part', $recipientPart)
                ->delete();

            if ($deleted === 0) {
                return $this->notFoundResponse('No captive recipients found with the specified recipient part');
            }

            return $this->successResponse([
                'recipient_part' => $recipientPart,
                'deleted_count' => $deleted,
                'deleted_at' => now()->toIso8601String(),
            ], 'Captive recipient(s) deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
