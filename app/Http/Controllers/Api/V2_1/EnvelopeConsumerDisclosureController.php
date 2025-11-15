<?php

namespace App\Http\Controllers\Api\V2_1;

use App\Models\Account;
use App\Models\ConsumerDisclosure;
use App\Models\Envelope;
use App\Models\EnvelopeRecipient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * EnvelopeConsumerDisclosureController
 *
 * Manages consumer disclosure (eSign consent) for envelope recipients.
 * Tracks acceptance of electronic signature agreements per recipient.
 *
 * Total Endpoints: 3
 */
class EnvelopeConsumerDisclosureController extends BaseController
{
    /**
     * GET /accounts/{accountId}/envelopes/{envelopeId}/recipients/{recipientId}/consumer_disclosure
     *
     * Get consumer disclosure for a specific recipient
     */
    public function getRecipientDisclosure(
        string $accountId,
        string $envelopeId,
        string $recipientId
    ): JsonResponse {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();

            $envelope = Envelope::where('account_id', $account->id)
                ->where('envelope_id', $envelopeId)
                ->firstOrFail();

            $recipient = EnvelopeRecipient::where('envelope_id', $envelope->id)
                ->where('recipient_id', $recipientId)
                ->firstOrFail();

            // Get account's default consumer disclosure
            $disclosure = ConsumerDisclosure::where('account_id', $account->id)
                ->where('language_code', 'en')
                ->first();

            if (!$disclosure) {
                // Return default disclosure if none configured
                return $this->successResponse([
                    'account_esign_accepted' => false,
                    'allow_cd_withdraw' => true,
                    'allow_cd_withdraw_metadata' => null,
                    'change_email' => '',
                    'change_email_other' => '',
                    'company_name' => $account->company_name ?? '',
                    'company_phone' => $account->company_phone ?? '',
                    'copy_cost_per_page' => '',
                    'copy_fee_collection_method' => '',
                    'copy_request_email' => '',
                    'custom' => '',
                    'enable_esign' => true,
                    'esign_agreement' => 'default',
                    'esign_text' => null,
                    'language_code' => 'en',
                    'must_agree_to_esign' => true,
                    'pdf_id' => '',
                    'use_brand' => false,
                    'use_consumer_disclosure_within_account' => false,
                    'withdraw_address_line_1' => '',
                    'withdraw_address_line_2' => '',
                    'withdraw_by_email' => true,
                    'withdraw_by_mail' => true,
                    'withdraw_by_phone' => true,
                    'withdraw_city' => '',
                    'withdraw_consequences' => '',
                    'withdraw_email' => '',
                    'withdraw_other' => '',
                    'withdraw_phone' => '',
                    'withdraw_postal_code' => '',
                    'withdraw_state' => '',
                ], 'Consumer disclosure retrieved successfully');
            }

            return $this->successResponse([
                'account_esign_accepted' => $recipient->consumer_disclosure_accepted ?? false,
                'accepted_at' => $recipient->consumer_disclosure_accepted_at?->toIso8601String(),
                'ip_address' => $recipient->consumer_disclosure_ip_address,
                'allow_cd_withdraw' => $disclosure->allow_cd_withdraw,
                'allow_cd_withdraw_metadata' => $disclosure->allow_cd_withdraw_metadata,
                'change_email' => $disclosure->change_email,
                'change_email_other' => $disclosure->change_email_other,
                'company_name' => $disclosure->company_name,
                'company_phone' => $disclosure->company_phone,
                'copy_cost_per_page' => $disclosure->copy_cost_per_page,
                'copy_fee_collection_method' => $disclosure->copy_fee_collection_method,
                'copy_request_email' => $disclosure->copy_request_email,
                'custom' => $disclosure->custom,
                'enable_esign' => $disclosure->enable_esign,
                'esign_agreement' => $disclosure->esign_agreement,
                'esign_text' => $disclosure->esign_text,
                'language_code' => $disclosure->language_code,
                'must_agree_to_esign' => $disclosure->must_agree_to_esign,
                'pdf_id' => $disclosure->pdf_id,
                'use_brand' => $disclosure->use_brand,
                'use_consumer_disclosure_within_account' => $disclosure->use_consumer_disclosure_within_account,
                'withdraw_address_line_1' => $disclosure->withdraw_address_line_1,
                'withdraw_address_line_2' => $disclosure->withdraw_address_line_2,
                'withdraw_by_email' => $disclosure->withdraw_by_email,
                'withdraw_by_mail' => $disclosure->withdraw_by_mail,
                'withdraw_by_phone' => $disclosure->withdraw_by_phone,
                'withdraw_city' => $disclosure->withdraw_city,
                'withdraw_consequences' => $disclosure->withdraw_consequences,
                'withdraw_email' => $disclosure->withdraw_email,
                'withdraw_other' => $disclosure->withdraw_other,
                'withdraw_phone' => $disclosure->withdraw_phone,
                'withdraw_postal_code' => $disclosure->withdraw_postal_code,
                'withdraw_state' => $disclosure->withdraw_state,
            ], 'Consumer disclosure retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /accounts/{accountId}/envelopes/{envelopeId}/recipients/{recipientId}/consumer_disclosure
     *
     * Record recipient's acceptance of consumer disclosure
     */
    public function acceptDisclosure(
        Request $request,
        string $accountId,
        string $envelopeId,
        string $recipientId
    ): JsonResponse {
        try {
            $validated = $request->validate([
                'client_user_id' => 'sometimes|string|max:255',
                'custom_fields' => 'sometimes|array',
                'decline_reason' => 'sometimes|string|max:500',
                'embedded_recipient_start_url' => 'sometimes|url',
                'ip_address' => 'sometimes|ip',
                'user_agent' => 'sometimes|string|max:500',
            ]);

            $account = Account::where('account_id', $accountId)->firstOrFail();

            $envelope = Envelope::where('account_id', $account->id)
                ->where('envelope_id', $envelopeId)
                ->firstOrFail();

            $recipient = EnvelopeRecipient::where('envelope_id', $envelope->id)
                ->where('recipient_id', $recipientId)
                ->firstOrFail();

            DB::beginTransaction();

            try {
                // Record acceptance
                $recipient->update([
                    'consumer_disclosure_accepted' => true,
                    'consumer_disclosure_accepted_at' => now(),
                    'consumer_disclosure_ip_address' => $validated['ip_address'] ?? $request->ip(),
                    'consumer_disclosure_user_agent' => $validated['user_agent'] ?? $request->userAgent(),
                ]);

                DB::commit();

                return $this->successResponse([
                    'account_esign_accepted' => true,
                    'accepted_at' => $recipient->consumer_disclosure_accepted_at->toIso8601String(),
                    'ip_address' => $recipient->consumer_disclosure_ip_address,
                    'user_agent' => $recipient->consumer_disclosure_user_agent,
                    'recipient_id' => $recipient->recipient_id,
                    'recipient_name' => $recipient->name,
                    'recipient_email' => $recipient->email,
                ], 'Consumer disclosure accepted successfully');
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /accounts/{accountId}/envelopes/{envelopeId}/consumer_disclosure/{langCode}
     *
     * Get consumer disclosure for envelope in specific language
     */
    public function getEnvelopeDisclosure(
        string $accountId,
        string $envelopeId,
        string $langCode = 'en'
    ): JsonResponse {
        try {
            $account = Account::where('account_id', $accountId)->firstOrFail();

            $envelope = Envelope::where('account_id', $account->id)
                ->where('envelope_id', $envelopeId)
                ->firstOrFail();

            // Get consumer disclosure for specified language
            $disclosure = ConsumerDisclosure::where('account_id', $account->id)
                ->where('language_code', $langCode)
                ->first();

            // Fall back to English if language not found
            if (!$disclosure) {
                $disclosure = ConsumerDisclosure::where('account_id', $account->id)
                    ->where('language_code', 'en')
                    ->first();
            }

            if (!$disclosure) {
                return $this->errorResponse(
                    'No consumer disclosure configured for this account',
                    404
                );
            }

            return $this->successResponse([
                'account_id' => $account->account_id,
                'envelope_id' => $envelope->envelope_id,
                'language_code' => $disclosure->language_code,
                'enable_esign' => $disclosure->enable_esign,
                'must_agree_to_esign' => $disclosure->must_agree_to_esign,
                'esign_agreement' => $disclosure->esign_agreement,
                'esign_text' => $disclosure->esign_text,
                'company_name' => $disclosure->company_name,
                'company_phone' => $disclosure->company_phone,
                'allow_cd_withdraw' => $disclosure->allow_cd_withdraw,
                'withdraw_by_email' => $disclosure->withdraw_by_email,
                'withdraw_by_phone' => $disclosure->withdraw_by_phone,
                'withdraw_by_mail' => $disclosure->withdraw_by_mail,
                'withdraw_email' => $disclosure->withdraw_email,
                'withdraw_phone' => $disclosure->withdraw_phone,
                'withdraw_consequences' => $disclosure->withdraw_consequences,
                'pdf_id' => $disclosure->pdf_id,
            ], 'Consumer disclosure retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
