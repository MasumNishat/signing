<?php

namespace Tests\Feature;

use App\Models\Envelope;
use App\Models\EnvelopeCustomField;
use App\Models\EnvelopeLock;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EnvelopeSettingsTest extends ApiTestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_can_get_notification_settings()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
            'reminder_enabled' => true,
            'reminder_delay' => 2,
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/notification");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonPath('data.reminder_enabled', true);
        $response->assertJsonPath('data.reminder_delay', 2);
    }

    /** @test */
    public function test_can_update_notification_settings()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/notification", [
            'reminder_enabled' => true,
            'reminder_delay' => 3,
            'reminder_frequency' => 2,
            'expiration_enabled' => true,
            'expiration_after' => 120,
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('envelopes', [
            'id' => $envelope->id,
            'reminder_enabled' => true,
            'reminder_delay' => 3,
            'expiration_enabled' => true,
            'expiration_after' => 120,
        ]);
    }

    /** @test */
    public function test_can_get_email_settings()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
            'reply_email' => 'reply@example.com',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/email_settings");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonPath('data.reply_email', 'reply@example.com');
    }

    /** @test */
    public function test_can_update_email_settings()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/email_settings", [
            'reply_email' => 'custom@example.com',
            'reply_email_name' => 'Custom Reply',
            'bcc_email_addresses' => ['bcc1@example.com', 'bcc2@example.com'],
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('envelopes', [
            'id' => $envelope->id,
            'reply_email' => 'custom@example.com',
            'reply_email_name' => 'Custom Reply',
        ]);
    }

    /** @test */
    public function test_can_create_custom_fields()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/custom_fields", [
            'text_custom_fields' => [
                [
                    'field_id' => 'field1',
                    'name' => 'Department',
                    'value' => 'Sales',
                    'show' => true,
                    'required' => true,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('envelope_custom_fields', [
            'envelope_id' => $envelope->id,
            'name' => 'Department',
            'value' => 'Sales',
        ]);
    }

    /** @test */
    public function test_can_get_custom_fields()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        EnvelopeCustomField::create([
            'envelope_id' => $envelope->id,
            'field_type' => 'text',
            'name' => 'Project',
            'value' => 'Alpha',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/custom_fields");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonCount(1, 'data.text_custom_fields');
    }

    /** @test */
    public function test_can_update_custom_fields()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        $field = EnvelopeCustomField::create([
            'envelope_id' => $envelope->id,
            'field_type' => 'text',
            'name' => 'Project',
            'value' => 'Alpha',
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/custom_fields", [
            'text_custom_fields' => [
                [
                    'field_id' => $field->field_id,
                    'value' => 'Beta',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('envelope_custom_fields', [
            'id' => $field->id,
            'value' => 'Beta',
        ]);
    }

    /** @test */
    public function test_can_delete_custom_fields()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        EnvelopeCustomField::create([
            'envelope_id' => $envelope->id,
            'field_type' => 'text',
            'name' => 'Project',
            'value' => 'Alpha',
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/custom_fields");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('envelope_custom_fields', [
            'envelope_id' => $envelope->id,
        ]);
    }

    /** @test */
    public function test_can_create_envelope_lock()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/lock", [
            'lock_duration_in_seconds' => 600,
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();
        $response->assertJsonStructure(['data' => ['lock_token', 'locked_by_user', 'locked_until']]);

        $this->assertDatabaseHas('envelope_locks', [
            'envelope_id' => $envelope->id,
            'locked_by_user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function test_can_get_lock_status()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        EnvelopeLock::create([
            'envelope_id' => $envelope->id,
            'locked_by_user_id' => $this->user->id,
            'lock_duration_in_seconds' => 300,
            'locked_until' => now()->addSeconds(300),
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/lock");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonPath('data.is_locked', true);
    }

    /** @test */
    public function test_can_extend_lock()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        $lock = EnvelopeLock::create([
            'envelope_id' => $envelope->id,
            'locked_by_user_id' => $this->user->id,
            'lock_duration_in_seconds' => 300,
            'locked_until' => now()->addSeconds(300),
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/lock", [
            'lock_token' => $lock->lock_token,
            'lock_duration_in_seconds' => 600,
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('envelope_locks', [
            'id' => $lock->id,
            'lock_duration_in_seconds' => 600,
        ]);
    }

    /** @test */
    public function test_can_release_lock()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        $lock = EnvelopeLock::create([
            'envelope_id' => $envelope->id,
            'locked_by_user_id' => $this->user->id,
            'lock_duration_in_seconds' => 300,
            'locked_until' => now()->addSeconds(300),
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/lock", [
            'lock_token' => $lock->lock_token,
        ]);

        $response->assertStatus(204);

        $this->assertDatabaseMissing('envelope_locks', [
            'id' => $lock->id,
        ]);
    }

    /** @test */
    public function test_validates_lock_duration_range()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/lock", [
            'lock_duration_in_seconds' => 50, // Too short
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['lock_duration_in_seconds']);
    }

    /** @test */
    public function test_supports_list_custom_fields()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/custom_fields", [
            'list_custom_fields' => [
                [
                    'field_id' => 'list1',
                    'name' => 'Priority',
                    'value' => 'High',
                    'list_items' => ['Low', 'Medium', 'High'],
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('envelope_custom_fields', [
            'envelope_id' => $envelope->id,
            'name' => 'Priority',
            'field_type' => 'list',
        ]);
    }
}
