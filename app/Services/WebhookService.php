<?php

namespace App\Services;

use App\Models\Account;
use App\Models\ConnectConfiguration;
use App\Models\ConnectFailure;
use App\Models\ConnectLog;
use App\Models\Envelope;
use App\Models\EnvelopeRecipient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WebhookService
 *
 * Handles publishing events to configured webhooks (Connect configurations).
 * Manages event delivery, logging, failure tracking, and retries.
 */
class WebhookService
{
    /**
     * Publish an envelope event to all configured webhooks
     *
     * @param Envelope $envelope
     * @param string $event
     * @return int Number of webhooks published to
     */
    public function publishEnvelopeEvent(Envelope $envelope, string $event): int
    {
        $configurations = ConnectConfiguration::where('account_id', $envelope->account_id)
            ->enabled()
            ->get();

        $publishedCount = 0;

        foreach ($configurations as $config) {
            if ($config->shouldPublishEnvelopeEvent($event)) {
                $this->publishToWebhook($config, $envelope, $event, 'envelope');
                $publishedCount++;
            }
        }

        return $publishedCount;
    }

    /**
     * Publish a recipient event to all configured webhooks
     *
     * @param EnvelopeRecipient $recipient
     * @param string $event
     * @return int Number of webhooks published to
     */
    public function publishRecipientEvent(EnvelopeRecipient $recipient, string $event): int
    {
        $envelope = $recipient->envelope;

        $configurations = ConnectConfiguration::where('account_id', $envelope->account_id)
            ->enabled()
            ->get();

        $publishedCount = 0;

        foreach ($configurations as $config) {
            if ($config->shouldPublishRecipientEvent($event)) {
                $this->publishToWebhook($config, $envelope, $event, 'recipient', $recipient);
                $publishedCount++;
            }
        }

        return $publishedCount;
    }

    /**
     * Retry failed webhook deliveries
     *
     * @param Account $account
     * @param string|null $envelopeId - Optional: retry specific envelope only
     * @return array ['success' => int, 'failed' => int]
     */
    public function retryFailedDeliveries(Account $account, ?string $envelopeId = null): array
    {
        $query = ConnectFailure::where('account_id', $account->id)->retryable();

        if ($envelopeId) {
            $query->where('envelope_id', $envelopeId);
        }

        $failures = $query->get();

        $results = ['success' => 0, 'failed' => 0];

        foreach ($failures as $failure) {
            $envelope = Envelope::where('envelope_id', $failure->envelope_id)->first();

            if (!$envelope) {
                Log::warning('Envelope not found for retry', ['envelope_id' => $failure->envelope_id]);
                continue;
            }

            // Get enabled configurations for this account
            $configurations = ConnectConfiguration::where('account_id', $account->id)
                ->enabled()
                ->get();

            foreach ($configurations as $config) {
                // Try to republish the event
                $success = $this->publishToWebhook(
                    $config,
                    $envelope,
                    'envelope-retry', // Special event type for retries
                    'envelope'
                );

                if ($success) {
                    $results['success']++;
                    // Remove from failures if successful
                    $failure->delete();
                } else {
                    $results['failed']++;
                    // Increment retry count
                    $failure->incrementRetryCount();
                }
            }
        }

        return $results;
    }

    /**
     * Publish event to a specific webhook configuration
     *
     * @param ConnectConfiguration $config
     * @param Envelope $envelope
     * @param string $event
     * @param string $eventType - 'envelope' or 'recipient'
     * @param EnvelopeRecipient|null $recipient
     * @return bool Success status
     */
    protected function publishToWebhook(
        ConnectConfiguration $config,
        Envelope $envelope,
        string $event,
        string $eventType,
        ?EnvelopeRecipient $recipient = null
    ): bool {
        DB::beginTransaction();

        try {
            // Build payload
            $payload = $this->buildPayload($config, $envelope, $event, $eventType, $recipient);

            // Add HMAC signature if enabled
            $headers = ['Content-Type' => 'application/json'];
            if ($config->include_hmac) {
                $headers['X-DocuSign-Signature-1'] = $this->generateHmacSignature($payload, $envelope->account->account_id);
            }

            // Send HTTP request
            $response = Http::timeout(30)
                ->withHeaders($headers)
                ->post($config->url_to_publish_to, $payload);

            // Log the delivery attempt
            $log = ConnectLog::create([
                'account_id' => $config->account_id,
                'connect_id' => $config->id,
                'envelope_id' => $envelope->envelope_id,
                'status' => $response->successful() ? ConnectLog::STATUS_SUCCESS : ConnectLog::STATUS_FAILED,
                'request_url' => $config->url_to_publish_to,
                'request_body' => json_encode($payload),
                'response_body' => $response->body(),
                'error' => $response->failed() ? $response->body() : null,
            ]);

            // If failed, create failure record
            if ($response->failed()) {
                ConnectFailure::create([
                    'account_id' => $config->account_id,
                    'envelope_id' => $envelope->envelope_id,
                    'error' => $response->body(),
                    'retry_count' => 0,
                ]);

                DB::commit();
                return false;
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();

            // Log exception
            Log::error('Webhook delivery failed', [
                'config_id' => $config->connect_id,
                'envelope_id' => $envelope->envelope_id,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);

            // Create log entry for exception
            try {
                ConnectLog::create([
                    'account_id' => $config->account_id,
                    'connect_id' => $config->id,
                    'envelope_id' => $envelope->envelope_id,
                    'status' => ConnectLog::STATUS_FAILED,
                    'request_url' => $config->url_to_publish_to,
                    'error' => $e->getMessage(),
                ]);

                // Create failure record
                ConnectFailure::create([
                    'account_id' => $config->account_id,
                    'envelope_id' => $envelope->envelope_id,
                    'error' => $e->getMessage(),
                    'retry_count' => 0,
                ]);
            } catch (\Exception $logException) {
                Log::error('Failed to create log/failure record', [
                    'error' => $logException->getMessage(),
                ]);
            }

            return false;
        }
    }

    /**
     * Build webhook payload
     *
     * @param ConnectConfiguration $config
     * @param Envelope $envelope
     * @param string $event
     * @param string $eventType
     * @param EnvelopeRecipient|null $recipient
     * @return array
     */
    protected function buildPayload(
        ConnectConfiguration $config,
        Envelope $envelope,
        string $event,
        string $eventType,
        ?EnvelopeRecipient $recipient = null
    ): array {
        $payload = [
            'event' => $event,
            'event_type' => $eventType,
            'generated_date_time' => now()->toIso8601String(),
            'envelope' => [
                'envelope_id' => $envelope->envelope_id,
                'status' => $envelope->status,
                'email_subject' => $envelope->email_subject,
                'sender_user_id' => $envelope->sender_user_id,
                'created_date_time' => $envelope->created_at->toIso8601String(),
                'sent_date_time' => $envelope->sent_date_time?->toIso8601String(),
                'completed_date_time' => $envelope->completed_date_time?->toIso8601String(),
                'voided_date_time' => $envelope->voided_date_time?->toIso8601String(),
            ],
        ];

        // Include void reason if enabled and envelope is voided
        if ($config->include_envelope_void_reason && $envelope->voided_reason) {
            $payload['envelope']['voided_reason'] = $envelope->voided_reason;
        }

        // Include time zone information if enabled
        if ($config->include_time_zone_information) {
            $payload['time_zone'] = config('app.timezone');
        }

        // Include sender account as custom field if enabled
        if ($config->include_sender_account_as_custom_field) {
            $payload['sender_account_id'] = $envelope->account_id;
        }

        // Include recipient information if this is a recipient event
        if ($recipient) {
            $payload['recipient'] = [
                'recipient_id' => $recipient->recipient_id,
                'recipient_type' => $recipient->recipient_type,
                'name' => $recipient->name,
                'email' => $recipient->email,
                'status' => $recipient->status,
                'routing_order' => $recipient->routing_order,
                'signed_date_time' => $recipient->signed_date_time?->toIso8601String(),
                'delivered_date_time' => $recipient->delivered_date_time?->toIso8601String(),
            ];
        }

        // Include documents if enabled (placeholder - would retrieve actual documents)
        if ($config->include_documents) {
            $payload['envelope']['documents'] = $envelope->documents->map(function ($doc) {
                return [
                    'document_id' => $doc->document_id,
                    'name' => $doc->name,
                    'order' => $doc->order_number,
                ];
            })->toArray();
        }

        // Include certificate of completion if enabled (placeholder)
        if ($config->include_certificate_of_completion && $envelope->isCompleted()) {
            $payload['certificate_of_completion'] = [
                'url' => sprintf('%s/api/v2.1/envelopes/%s/certificate', config('app.url'), $envelope->envelope_id),
                'generated_at' => now()->toIso8601String(),
            ];
        }

        return $payload;
    }

    /**
     * Generate HMAC signature for payload
     *
     * @param array $payload
     * @param int $accountId
     * @return string
     */
    protected function generateHmacSignature(array $payload, int $accountId): string
    {
        // Use account_id and app key as the secret
        $secret = config('app.key') . ':' . $accountId;

        // Generate HMAC-SHA256 signature
        return hash_hmac('sha256', json_encode($payload), $secret);
    }

    /**
     * Republish historical envelope events for auditing/reprocessing
     *
     * @param Account $account
     * @param array $options Filtering options (from_date, to_date, envelope_ids, etc.)
     * @return array ['envelopes_processed' => int, 'events_published' => int, 'failures' => int]
     */
    public function republishHistoricalEvents(Account $account, array $options = []): array
    {
        // Build envelope query based on filters
        $query = Envelope::where('account_id', $account->id);

        // Filter by date range
        if (isset($options['from_date'])) {
            $query->where('created_at', '>=', $options['from_date']);
        }

        if (isset($options['to_date'])) {
            $query->where('created_at', '<=', $options['to_date']);
        }

        // Filter by specific envelope IDs
        if (isset($options['envelope_ids']) && is_array($options['envelope_ids'])) {
            $query->whereIn('envelope_id', $options['envelope_ids']);
        }

        // Filter by envelope status
        if (isset($options['status'])) {
            $query->where('status', $options['status']);
        }

        // Get enabled webhook configurations
        $configurations = ConnectConfiguration::where('account_id', $account->id)
            ->enabled()
            ->get();

        if ($configurations->isEmpty()) {
            return [
                'envelopes_processed' => 0,
                'events_published' => 0,
                'failures' => 0,
                'message' => 'No enabled Connect configurations found',
            ];
        }

        $envelopes = $query->get();

        $results = [
            'envelopes_processed' => 0,
            'events_published' => 0,
            'failures' => 0,
        ];

        foreach ($envelopes as $envelope) {
            $results['envelopes_processed']++;

            foreach ($configurations as $config) {
                // Republish with special 'historical-republish' event type
                $success = $this->publishToWebhook(
                    $config,
                    $envelope,
                    'historical-republish',
                    'envelope'
                );

                if ($success) {
                    $results['events_published']++;
                } else {
                    $results['failures']++;
                }
            }
        }

        Log::info('Historical events republished', [
            'account_id' => $account->account_id,
            'results' => $results,
            'filters' => $options,
        ]);

        return $results;
    }
}
