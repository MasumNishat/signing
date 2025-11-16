<?php

namespace Tests\Feature;

use App\Models\ConnectConfiguration;
use App\Models\ConnectLog;
use App\Models\ConnectFailure;
use App\Models\ConnectOAuthConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WebhookManagementTest extends ApiTestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_can_list_connect_configurations()
    {
        $this->createAndAuthenticateUser();

        ConnectConfiguration::create([
            'account_id' => $this->account->id,
            'url_to_publish_to' => 'https://example.com/webhook',
            'enabled' => true,
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/connect");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_create_connect_configuration()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/connect", [
            'url_to_publish_to' => 'https://example.com/webhook',
            'enabled' => true,
            'all_users' => true,
            'event_types' => ['envelope_sent', 'envelope_completed'],
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('connect_configurations', [
            'account_id' => $this->account->id,
            'url_to_publish_to' => 'https://example.com/webhook',
        ]);
    }

    /** @test */
    public function test_can_get_specific_connect_configuration()
    {
        $this->createAndAuthenticateUser();

        $config = ConnectConfiguration::create([
            'account_id' => $this->account->id,
            'url_to_publish_to' => 'https://example.com/webhook',
            'enabled' => true,
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/connect/{$config->connect_id}");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_update_connect_configuration()
    {
        $this->createAndAuthenticateUser();

        $config = ConnectConfiguration::create([
            'account_id' => $this->account->id,
            'url_to_publish_to' => 'https://example.com/webhook',
            'enabled' => true,
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/connect", [
            'connect_id' => $config->connect_id,
            'url_to_publish_to' => 'https://example.com/new-webhook',
            'enabled' => false,
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('connect_configurations', [
            'id' => $config->id,
            'url_to_publish_to' => 'https://example.com/new-webhook',
            'enabled' => false,
        ]);
    }

    /** @test */
    public function test_can_delete_connect_configuration()
    {
        $this->createAndAuthenticateUser();

        $config = ConnectConfiguration::create([
            'account_id' => $this->account->id,
            'url_to_publish_to' => 'https://example.com/webhook',
            'enabled' => true,
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/connect/{$config->connect_id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('connect_configurations', [
            'id' => $config->id,
        ]);
    }

    /** @test */
    public function test_can_list_connect_logs()
    {
        $this->createAndAuthenticateUser();

        $config = ConnectConfiguration::create([
            'account_id' => $this->account->id,
            'url_to_publish_to' => 'https://example.com/webhook',
            'enabled' => true,
        ]);

        ConnectLog::create([
            'account_id' => $this->account->id,
            'connect_id' => $config->connect_id,
            'event_type' => 'envelope_sent',
            'http_status' => 200,
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/connect/logs");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
    }

    /** @test */
    public function test_can_get_specific_connect_log()
    {
        $this->createAndAuthenticateUser();

        $config = ConnectConfiguration::create([
            'account_id' => $this->account->id,
            'url_to_publish_to' => 'https://example.com/webhook',
            'enabled' => true,
        ]);

        $log = ConnectLog::create([
            'account_id' => $this->account->id,
            'connect_id' => $config->connect_id,
            'event_type' => 'envelope_sent',
            'http_status' => 200,
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/connect/logs/{$log->log_id}");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_delete_connect_log()
    {
        $this->createAndAuthenticateUser();

        $config = ConnectConfiguration::create([
            'account_id' => $this->account->id,
            'url_to_publish_to' => 'https://example.com/webhook',
            'enabled' => true,
        ]);

        $log = ConnectLog::create([
            'account_id' => $this->account->id,
            'connect_id' => $config->connect_id,
            'event_type' => 'envelope_sent',
            'http_status' => 200,
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/connect/logs/{$log->log_id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('connect_logs', [
            'id' => $log->id,
        ]);
    }

    /** @test */
    public function test_can_list_connect_failures()
    {
        $this->createAndAuthenticateUser();

        $config = ConnectConfiguration::create([
            'account_id' => $this->account->id,
            'url_to_publish_to' => 'https://example.com/webhook',
            'enabled' => true,
        ]);

        ConnectFailure::create([
            'account_id' => $this->account->id,
            'connect_id' => $config->connect_id,
            'event_type' => 'envelope_sent',
            'failure_reason' => 'Connection timeout',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/connect/failures");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_delete_connect_failure()
    {
        $this->createAndAuthenticateUser();

        $config = ConnectConfiguration::create([
            'account_id' => $this->account->id,
            'url_to_publish_to' => 'https://example.com/webhook',
            'enabled' => true,
        ]);

        $failure = ConnectFailure::create([
            'account_id' => $this->account->id,
            'connect_id' => $config->connect_id,
            'event_type' => 'envelope_sent',
            'failure_reason' => 'Connection timeout',
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/connect/failures/{$failure->failure_id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('connect_failures', [
            'id' => $failure->id,
        ]);
    }

    /** @test */
    public function test_can_retry_envelope_webhook()
    {
        $this->createAndAuthenticateUser();

        $config = ConnectConfiguration::create([
            'account_id' => $this->account->id,
            'url_to_publish_to' => 'https://example.com/webhook',
            'enabled' => true,
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/connect/envelopes/test-envelope-id/retry_queue");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_retry_multiple_envelopes()
    {
        $this->createAndAuthenticateUser();

        ConnectConfiguration::create([
            'account_id' => $this->account->id,
            'url_to_publish_to' => 'https://example.com/webhook',
            'enabled' => true,
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/connect/envelopes/retry_queue", [
            'envelope_ids' => ['env1', 'env2', 'env3'],
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_publish_historical_events()
    {
        $this->createAndAuthenticateUser();

        ConnectConfiguration::create([
            'account_id' => $this->account->id,
            'url_to_publish_to' => 'https://example.com/webhook',
            'enabled' => true,
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/connect/envelopes/publish/historical", [
            'from_date' => now()->subDays(30)->toDateString(),
            'to_date' => now()->toDateString(),
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_get_oauth_config()
    {
        $this->createAndAuthenticateUser();

        ConnectOAuthConfig::create([
            'account_id' => $this->account->id,
            'authorization_server' => 'https://oauth.example.com',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/connect/oauth");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_create_oauth_config()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/connect/oauth", [
            'authorization_server' => 'https://oauth.example.com',
            'client_id' => 'client_123',
            'client_secret' => 'secret_456',
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('connect_oauth_config', [
            'account_id' => $this->account->id,
            'client_id' => 'client_123',
        ]);
    }

    /** @test */
    public function test_can_update_oauth_config()
    {
        $this->createAndAuthenticateUser();

        $config = ConnectOAuthConfig::create([
            'account_id' => $this->account->id,
            'authorization_server' => 'https://oauth.example.com',
            'client_id' => 'old_client',
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/connect/oauth", [
            'client_id' => 'new_client',
            'client_secret' => 'new_secret',
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('connect_oauth_config', [
            'id' => $config->id,
            'client_id' => 'new_client',
        ]);
    }

    /** @test */
    public function test_can_delete_oauth_config()
    {
        $this->createAndAuthenticateUser();

        $config = ConnectOAuthConfig::create([
            'account_id' => $this->account->id,
            'authorization_server' => 'https://oauth.example.com',
            'client_id' => 'client_123',
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/connect/oauth");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('connect_oauth_config', [
            'id' => $config->id,
        ]);
    }

    /** @test */
    public function test_validates_webhook_url_format()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/connect", [
            'url_to_publish_to' => 'invalid-url',
            'enabled' => true,
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['url_to_publish_to']);
    }

    /** @test */
    public function test_supports_multiple_event_types()
    {
        $this->createAndAuthenticateUser();

        $config = ConnectConfiguration::create([
            'account_id' => $this->account->id,
            'url_to_publish_to' => 'https://example.com/webhook',
            'enabled' => true,
            'event_types' => ['envelope_sent', 'envelope_completed', 'envelope_declined', 'envelope_voided'],
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/connect/{$config->connect_id}");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $this->assertCount(4, $response->json('data.event_types'));
    }

    /** @test */
    public function test_can_filter_logs_by_event_type()
    {
        $this->createAndAuthenticateUser();

        $config = ConnectConfiguration::create([
            'account_id' => $this->account->id,
            'url_to_publish_to' => 'https://example.com/webhook',
            'enabled' => true,
        ]);

        ConnectLog::create(['account_id' => $this->account->id, 'connect_id' => $config->connect_id, 'event_type' => 'envelope_sent', 'http_status' => 200]);
        ConnectLog::create(['account_id' => $this->account->id, 'connect_id' => $config->connect_id, 'event_type' => 'envelope_completed', 'http_status' => 200]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/connect/logs?event_type=envelope_sent");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
    }

    /** @test */
    public function test_can_filter_logs_by_status()
    {
        $this->createAndAuthenticateUser();

        $config = ConnectConfiguration::create([
            'account_id' => $this->account->id,
            'url_to_publish_to' => 'https://example.com/webhook',
            'enabled' => true,
        ]);

        ConnectLog::create(['account_id' => $this->account->id, 'connect_id' => $config->connect_id, 'event_type' => 'envelope_sent', 'http_status' => 200]);
        ConnectLog::create(['account_id' => $this->account->id, 'connect_id' => $config->connect_id, 'event_type' => 'envelope_sent', 'http_status' => 500]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/connect/logs?status=success");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
    }
}
