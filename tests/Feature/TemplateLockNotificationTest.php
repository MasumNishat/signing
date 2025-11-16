<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Template;
use App\Models\EnvelopeLock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Template Lock and Notification Integration Tests
 *
 * Tests template lock (4 endpoints) and notification (2 endpoints):
 * Lock:
 * - GET /templates/{id}/lock
 * - POST /templates/{id}/lock
 * - PUT /templates/{id}/lock
 * - DELETE /templates/{id}/lock
 *
 * Notification:
 * - GET /templates/{id}/notification
 * - PUT /templates/{id}/notification
 */
class TemplateLockNotificationTest extends TestCase
{
    use RefreshDatabase;

    private Account $account;
    private User $user;
    private Template $template;

    protected function setUp(): void
    {
        parent::setUp();

        $this->account = Account::factory()->create();
        $this->user = User::factory()->create([
            'account_id' => $this->account->id,
        ]);

        $this->template = Template::create([
            'account_id' => $this->account->id,
            'template_id' => 'tpl-' . uniqid(),
            'name' => 'Test Template',
            'created_by_user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user);
    }

    // =========================================================================
    // TEMPLATE LOCK TESTS
    // =========================================================================

    
    public function test_can_check_unlocked_template_status()
    {
        $response = $this->getJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/lock"
        );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_locked' => false,
                    'message' => 'Template is not locked',
                ],
            ]);
    }

    
    public function test_can_create_template_lock()
    {
        $lockData = [
            'lock_duration_seconds' => 600,
            'locked_by_user_name' => 'Test User',
        ];

        $response = $this->postJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/lock",
            $lockData
        );

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'lock_token',
                    'locked_until',
                    'lock_duration_seconds',
                ],
            ])
            ->assertJson([
                'data' => [
                    'lock_duration_seconds' => 600,
                ],
            ]);

        $this->assertDatabaseHas('envelope_locks', [
            'template_id' => $this->template->id,
            'locked_by_user_id' => $this->user->id,
            'lock_duration_seconds' => 600,
        ]);
    }

    
    public function test_lock_uses_default_duration_if_not_specified()
    {
        $response = $this->postJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/lock"
        );

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'lock_duration_seconds' => 300, // Default 5 minutes
                ],
            ]);
    }

    
    public function test_can_check_locked_template_status()
    {
        // Create a lock
        $lock = EnvelopeLock::create([
            'template_id' => $this->template->id,
            'locked_by_user_id' => $this->user->id,
            'locked_by_user_name' => 'Test User',
            'lock_token' => 'test-lock-token',
            'locked_until' => now()->addMinutes(5),
            'lock_duration_seconds' => 300,
        ]);

        $response = $this->getJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/lock"
        );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_locked' => true,
                    'locked_by_user_id' => $this->user->id,
                    'lock_token' => 'test-lock-token',
                    'lock_duration_seconds' => 300,
                ],
            ]);
    }

    
    public function test_cannot_create_lock_when_already_locked()
    {
        // Create existing lock
        EnvelopeLock::create([
            'template_id' => $this->template->id,
            'locked_by_user_id' => 999, // Different user
            'locked_by_user_name' => 'Another User',
            'lock_token' => 'existing-lock',
            'locked_until' => now()->addMinutes(5),
            'lock_duration_seconds' => 300,
        ]);

        $response = $this->postJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/lock"
        );

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Template is already locked by another user',
            ]);
    }

    
    public function test_can_extend_template_lock()
    {
        // Create lock
        $lock = EnvelopeLock::create([
            'template_id' => $this->template->id,
            'locked_by_user_id' => $this->user->id,
            'locked_by_user_name' => 'Test User',
            'lock_token' => 'my-lock-token',
            'locked_until' => now()->addMinutes(5),
            'lock_duration_seconds' => 300,
        ]);

        $extendData = [
            'lock_token' => 'my-lock-token',
            'lock_duration_seconds' => 900,
        ];

        $response = $this->putJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/lock",
            $extendData
        );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'lock_token' => 'my-lock-token',
                    'lock_duration_seconds' => 900,
                ],
            ]);

        $this->assertDatabaseHas('envelope_locks', [
            'lock_token' => 'my-lock-token',
            'lock_duration_seconds' => 900,
        ]);
    }

    
    public function test_can_release_template_lock()
    {
        // Create lock
        $lock = EnvelopeLock::create([
            'template_id' => $this->template->id,
            'locked_by_user_id' => $this->user->id,
            'locked_by_user_name' => 'Test User',
            'lock_token' => 'release-me',
            'locked_until' => now()->addMinutes(5),
            'lock_duration_seconds' => 300,
        ]);

        $response = $this->deleteJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/lock",
            ['lock_token' => 'release-me']
        );

        $response->assertStatus(204);

        $this->assertDatabaseMissing('envelope_locks', [
            'lock_token' => 'release-me',
        ]);
    }

    
    public function test_validates_lock_duration_range()
    {
        // Too short
        $response = $this->postJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/lock",
            ['lock_duration_seconds' => 30] // Below minimum of 60
        );
        $response->assertStatus(422);

        // Too long
        $response = $this->postJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/lock",
            ['lock_duration_seconds' => 5000] // Above maximum of 3600
        );
        $response->assertStatus(422);
    }

    // =========================================================================
    // TEMPLATE NOTIFICATION TESTS
    // =========================================================================

    
    public function test_can_get_template_notification_settings()
    {
        $this->template->update([
            'email_subject' => 'Please sign: {{EnvelopeName}}',
            'email_blurb' => 'Your signature is required',
            'reminder_enabled' => true,
            'reminder_delay' => 3,
            'reminder_frequency' => 2,
            'expiration_enabled' => true,
            'expiration_after' => 30,
            'expiration_warn' => 7,
        ]);

        $response = $this->getJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/notification"
        );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'email_subject' => 'Please sign: {{EnvelopeName}}',
                    'email_blurb' => 'Your signature is required',
                    'reminder_enabled' => true,
                    'reminder_delay' => 3,
                    'reminder_frequency' => 2,
                    'expiration_enabled' => true,
                    'expiration_after' => 30,
                    'expiration_warn' => 7,
                ],
            ]);
    }

    
    public function test_can_update_template_notification_settings()
    {
        $notificationData = [
            'email_subject' => 'Action Required: Sign Document',
            'email_blurb' => 'Please review and sign this important document.',
            'reminder_enabled' => true,
            'reminder_delay' => 5,
            'reminder_frequency' => 3,
        ];

        $response = $this->putJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/notification",
            $notificationData
        );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => $notificationData,
            ]);

        $this->assertDatabaseHas('templates', [
            'id' => $this->template->id,
            'email_subject' => 'Action Required: Sign Document',
            'reminder_enabled' => true,
            'reminder_delay' => 5,
        ]);
    }

    
    public function test_can_enable_expiration_settings()
    {
        $expirationData = [
            'expiration_enabled' => true,
            'expiration_after' => 14,
            'expiration_warn' => 3,
        ];

        $response = $this->putJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/notification",
            $expirationData
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('templates', [
            'id' => $this->template->id,
            'expiration_enabled' => true,
            'expiration_after' => 14,
            'expiration_warn' => 3,
        ]);
    }

    
    public function test_validates_reminder_delay_when_enabled()
    {
        $this->template->update(['reminder_delay' => null]);

        $invalidData = [
            'reminder_enabled' => true,
            // Missing reminder_delay
        ];

        $response = $this->putJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/notification",
            $invalidData
        );

        $response->assertStatus(422);
    }

    
    public function test_validates_expiration_after_when_enabled()
    {
        $this->template->update(['expiration_after' => null]);

        $invalidData = [
            'expiration_enabled' => true,
            // Missing expiration_after
        ];

        $response = $this->putJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/notification",
            $invalidData
        );

        $response->assertStatus(422);
    }

    
    public function test_validates_numeric_ranges()
    {
        $invalidData = [
            'reminder_delay' => 1000, // Above max of 999
        ];

        $response = $this->putJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/notification",
            $invalidData
        );

        $response->assertStatus(422);
    }

    
    public function test_can_disable_reminders_and_expiration()
    {
        // First enable them
        $this->template->update([
            'reminder_enabled' => true,
            'reminder_delay' => 3,
            'expiration_enabled' => true,
            'expiration_after' => 30,
        ]);

        // Now disable
        $disableData = [
            'reminder_enabled' => false,
            'expiration_enabled' => false,
        ];

        $response = $this->putJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/notification",
            $disableData
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('templates', [
            'id' => $this->template->id,
            'reminder_enabled' => false,
            'expiration_enabled' => false,
        ]);
    }
}
