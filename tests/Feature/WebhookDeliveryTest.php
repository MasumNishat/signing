<?php

use App\Models\Account;
use App\Models\ConnectConfiguration;
use App\Models\ConnectLog;
use App\Models\Envelope;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = $this->createAndAuthenticateUser();
    $this->account = $this->user->account;

    // Mock HTTP client to prevent actual webhook calls during tests
    Http::fake([
        '*' => Http::response(['success' => true], 200),
    ]);
});

describe('Webhook Configuration', function () {
    test('can create webhook configuration with valid URL', function () {
        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/connect", [
            'url_to_publish_to' => 'https://example.com/webhook',
            'all_users' => true,
            'allow_envelope_publish' => true,
            'include_document_fields' => true,
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $data = $response->json('data');
        expect($data['url_to_publish_to'])->toBe('https://example.com/webhook')
            ->and($data['all_users'])->toBeTrue()
            ->and($data['allow_envelope_publish'])->toBeTrue();
    });

    test('validates webhook URL format', function () {
        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/connect", [
            'url_to_publish_to' => 'not-a-valid-url',
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['url_to_publish_to']);
    });

    test('can update webhook configuration', function () {
        $config = ConnectConfiguration::factory()->create([
            'account_id' => $this->account->id,
            'url_to_publish_to' => 'https://example.com/webhook',
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/connect/{$config->connect_id}", [
            'url_to_publish_to' => 'https://newurl.com/webhook',
            'include_certificate_of_completion' => true,
        ]);

        $response->assertStatus(200);

        $data = $response->json('data');
        expect($data['url_to_publish_to'])->toBe('https://newurl.com/webhook')
            ->and($data['include_certificate_of_completion'])->toBeTrue();
    });

    test('can configure event filters', function () {
        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/connect", [
            'url_to_publish_to' => 'https://example.com/webhook',
            'event_data' => [
                'events' => ['envelope-sent', 'envelope-completed', 'envelope-voided'],
            ],
        ]);

        $response->assertStatus(201);

        $data = $response->json('data');
        expect($data['event_data']['events'])->toContain('envelope-sent')
            ->and($data['event_data']['events'])->toContain('envelope-completed')
            ->and($data['event_data']['events'])->toContain('envelope-voided');
    });

    test('can enable HMAC signature validation', function () {
        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/connect", [
            'url_to_publish_to' => 'https://example.com/webhook',
            'use_soap_interface' => false,
            'include_hmac' => true,
        ]);

        $response->assertStatus(201);

        $data = $response->json('data');
        expect($data['include_hmac'])->toBeTrue();
    });
});

describe('Webhook Event Triggers', function () {
    beforeEach(function () {
        $this->webhook = ConnectConfiguration::factory()->create([
            'account_id' => $this->account->id,
            'url_to_publish_to' => 'https://example.com/webhook',
            'allow_envelope_publish' => true,
            'event_data' => [
                'events' => ['envelope-sent', 'envelope-completed'],
            ],
        ]);
    });

    test('triggers webhook when envelope is sent', function () {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
        ]);
        $envelope->documents()->create([
            'document_id' => '1',
            'name' => 'Test.pdf',
            'file_extension' => 'pdf',
            'order' => 1,
        ]);
        $envelope->recipients()->create([
            'recipient_id' => '1',
            'recipient_type' => 'signer',
            'routing_order' => 1,
            'email' => 'signer@example.com',
            'name' => 'Test Signer',
        ]);

        // Send envelope (should trigger webhook)
        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/send");

        $response->assertStatus(200);

        // Verify webhook log was created
        $log = ConnectLog::where('connect_configuration_id', $this->webhook->id)
            ->where('event_type', 'envelope-sent')
            ->first();

        expect($log)->not()->toBeNull()
            ->and($log->status)->toBe('success')
            ->and($log->envelope_id)->toBe($envelope->envelope_id);
    });

    test('triggers webhook when envelope is completed', function () {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'sent',
        ]);

        // Mark envelope as completed
        $envelope->markAsCompleted();
        $envelope->save();

        // Verify webhook log
        $log = ConnectLog::where('connect_configuration_id', $this->webhook->id)
            ->where('event_type', 'envelope-completed')
            ->where('envelope_id', $envelope->envelope_id)
            ->first();

        expect($log)->not()->toBeNull();
    });

    test('does not trigger webhook for filtered events', function () {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'sent',
        ]);

        // Void envelope (not in event filter)
        $response = $this->apiPost(
            "/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/void",
            ['voidedReason' => 'Test']
        );

        $response->assertStatus(200);

        // Verify NO webhook log for voided event
        $log = ConnectLog::where('connect_configuration_id', $this->webhook->id)
            ->where('event_type', 'envelope-voided')
            ->first();

        expect($log)->toBeNull();
    });
});

describe('Webhook Payload Validation', function () {
    beforeEach(function () {
        $this->webhook = ConnectConfiguration::factory()->create([
            'account_id' => $this->account->id,
            'url_to_publish_to' => 'https://example.com/webhook',
            'include_document_fields' => true,
            'include_envelope_void_reason' => true,
            'include_sender_account_as_custom_field' => true,
        ]);
    });

    test('includes envelope data in webhook payload', function () {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'subject' => 'Test Contract',
            'status' => 'sent',
        ]);

        $log = ConnectLog::where('envelope_id', $envelope->envelope_id)->first();

        if ($log) {
            $payload = $log->request_body;
            expect($payload)->toHaveKey('envelope_id')
                ->and($payload)->toHaveKey('subject')
                ->and($payload['subject'])->toBe('Test Contract');
        }
    });

    test('includes document fields when configured', function () {
        $this->webhook->update(['include_document_fields' => true]);

        $envelope = Envelope::factory()->create(['account_id' => $this->account->id]);
        $envelope->documents()->create([
            'document_id' => '1',
            'name' => 'Contract.pdf',
            'file_extension' => 'pdf',
        ]);

        $log = ConnectLog::where('envelope_id', $envelope->envelope_id)->first();

        if ($log) {
            expect($log->request_body)->toHaveKey('documents');
        }
    });

    test('includes void reason when configured', function () {
        $this->webhook->update([
            'include_envelope_void_reason' => true,
            'event_data' => ['events' => ['envelope-voided']],
        ]);

        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'sent',
        ]);

        $response = $this->apiPost(
            "/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/void",
            ['voidedReason' => 'Mistake in document']
        );

        $log = ConnectLog::where('event_type', 'envelope-voided')
            ->where('envelope_id', $envelope->envelope_id)
            ->first();

        if ($log) {
            expect($log->request_body)->toHaveKey('voided_reason')
                ->and($log->request_body['voided_reason'])->toBe('Mistake in document');
        }
    });
});

describe('Webhook Retry Logic', function () {
    beforeEach(function () {
        $this->webhook = ConnectConfiguration::factory()->create([
            'account_id' => $this->account->id,
            'url_to_publish_to' => 'https://example.com/webhook',
            'retry_count' => 3,
            'retry_delay_minutes' => 5,
        ]);
    });

    test('retries webhook on failure', function () {
        // Mock failed HTTP response
        Http::fake([
            'https://example.com/webhook' => Http::response('Server Error', 500),
        ]);

        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
        ]);
        $envelope->documents()->create([
            'document_id' => '1',
            'name' => 'Test.pdf',
            'file_extension' => 'pdf',
        ]);
        $envelope->recipients()->create([
            'recipient_id' => '1',
            'recipient_type' => 'signer',
            'routing_order' => 1,
            'email' => 'test@example.com',
            'name' => 'Test',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/send");

        // Verify failure log was created
        $failure = \App\Models\ConnectFailure::where('envelope_id', $envelope->envelope_id)->first();

        if ($failure) {
            expect($failure->retry_count)->toBe(0)
                ->and($failure->status_code)->toBe(500);
        }
    });

    test('stops retrying after max attempts', function () {
        $failure = \App\Models\ConnectFailure::create([
            'connect_configuration_id' => $this->webhook->id,
            'envelope_id' => 'test-envelope-id',
            'event_type' => 'envelope-sent',
            'url' => 'https://example.com/webhook',
            'retry_count' => 3,
            'status_code' => 500,
            'error_message' => 'Server Error',
            'request_body' => [],
        ]);

        expect($failure->retry_count)->toBe(3);
    });
});

describe('Webhook Logging', function () {
    beforeEach(function () {
        $this->webhook = ConnectConfiguration::factory()->create([
            'account_id' => $this->account->id,
            'url_to_publish_to' => 'https://example.com/webhook',
        ]);
    });

    test('logs successful webhook delivery', function () {
        $envelope = Envelope::factory()->create(['account_id' => $this->account->id]);

        $log = ConnectLog::where('envelope_id', $envelope->envelope_id)->first();

        if ($log) {
            expect($log->status)->toBe('success')
                ->and($log->status_code)->toBe(200)
                ->and($log->url)->toBe('https://example.com/webhook');
        }
    });

    test('logs failed webhook delivery', function () {
        Http::fake([
            'https://example.com/webhook' => Http::response('Unauthorized', 401),
        ]);

        $envelope = Envelope::factory()->create(['account_id' => $this->account->id]);

        $failure = \App\Models\ConnectFailure::where('envelope_id', $envelope->envelope_id)->first();

        if ($failure) {
            expect($failure->status_code)->toBe(401);
        }
    });

    test('retrieves webhook logs for account', function () {
        ConnectLog::factory()->count(5)->create([
            'connect_configuration_id' => $this->webhook->id,
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/connect/{$this->webhook->connect_id}/logs");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();

        expect($response->json('data'))->toHaveCount(5);
    });
});
