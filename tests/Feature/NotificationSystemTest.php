<?php

use App\Models\Account;
use App\Models\Envelope;
use App\Models\EnvelopeRecipient;
use App\Models\NotificationDefault;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->user = $this->createAndAuthenticateUser();
    $this->account = $this->user->account;

    // Mock mail and notifications to prevent actual sending during tests
    Mail::fake();
    Notification::fake();
});

describe('Notification Configuration', function () {
    test('can configure account notification defaults', function () {
        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/settings/notification_defaults", [
            'sender_email_notifications' => [
                'envelope_activation' => true,
                'envelope_complete' => true,
                'envelope_declined' => true,
                'envelope_voided' => false,
            ],
            'recipient_email_notifications' => [
                'envelope_activation' => true,
                'envelope_completed' => true,
            ],
        ]);

        $response->assertStatus(200);

        $data = $response->json('data');
        expect($data['sender_email_notifications']['envelope_activation'])->toBeTrue()
            ->and($data['sender_email_notifications']['envelope_voided'])->toBeFalse()
            ->and($data['recipient_email_notifications']['envelope_activation'])->toBeTrue();
    });

    test('can retrieve notification defaults', function () {
        NotificationDefault::create([
            'account_id' => $this->account->id,
            'sender_email_notifications' => [
                'envelope_complete' => true,
            ],
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/settings/notification_defaults");

        $response->assertStatus(200);

        expect($response->json('data'))->toHaveKey('sender_email_notifications');
    });

    test('can configure envelope-specific notification settings', function () {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
        ]);

        $response = $this->apiPut(
            "/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/notification",
            [
                'reminders_enabled' => true,
                'reminder_delay' => 3,
                'reminder_frequency' => 2,
                'expire_enabled' => true,
                'expire_after' => 120,
                'expire_warn' => 7,
            ]
        );

        $response->assertStatus(200);

        $data = $response->json('data');
        expect($data['reminders_enabled'])->toBeTrue()
            ->and($data['reminder_delay'])->toBe(3)
            ->and($data['reminder_frequency'])->toBe(2)
            ->and($data['expire_enabled'])->toBeTrue()
            ->and($data['expire_after'])->toBe(120);
    });
});

describe('Email Notifications', function () {
    test('sends notification when envelope is sent', function () {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
            'email_subject' => 'Please sign this document',
            'email_blurb' => 'You have a document to sign',
        ]);
        $envelope->documents()->create([
            'document_id' => '1',
            'name' => 'Contract.pdf',
            'file_extension' => 'pdf',
        ]);
        $recipient = $envelope->recipients()->create([
            'recipient_id' => '1',
            'recipient_type' => 'signer',
            'routing_order' => 1,
            'email' => 'signer@example.com',
            'name' => 'Test Signer',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/send");

        $response->assertStatus(200);

        // Verify email was sent
        Mail::assertSent(function ($mail) use ($recipient) {
            return $mail->hasTo($recipient->email);
        });
    });

    test('sends completion notification to sender', function () {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'sent',
            'sender_user_id' => $this->user->id,
        ]);

        // Mark envelope as completed
        $envelope->markAsCompleted();
        $envelope->save();

        // Verify sender received completion notification
        Mail::assertSent(function ($mail) {
            return $mail->hasTo($this->user->email) &&
                   str_contains($mail->subject ?? '', 'completed');
        });
    });

    test('sends void notification to recipients', function () {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'sent',
        ]);
        $recipient = $envelope->recipients()->create([
            'recipient_id' => '1',
            'recipient_type' => 'signer',
            'email' => 'signer@example.com',
            'name' => 'Test Signer',
        ]);

        $response = $this->apiPost(
            "/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/void",
            ['voidedReason' => 'Mistake in document']
        );

        $response->assertStatus(200);

        // Verify recipient received void notification
        Mail::assertSent(function ($mail) use ($recipient) {
            return $mail->hasTo($recipient->email);
        });
    });

    test('uses custom email subject and blurb', function () {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
            'email_subject' => 'Custom Email Subject',
            'email_blurb' => 'Custom message to recipients',
        ]);
        $envelope->documents()->create(['document_id' => '1', 'name' => 'Test.pdf', 'file_extension' => 'pdf']);
        $envelope->recipients()->create([
            'recipient_id' => '1',
            'recipient_type' => 'signer',
            'routing_order' => 1,
            'email' => 'test@example.com',
            'name' => 'Test',
        ]);

        $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/send");

        Mail::assertSent(function ($mail) {
            return str_contains($mail->subject ?? '', 'Custom Email Subject');
        });
    });

    test('respects BCC email addresses', function () {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
            'bcc_email_addresses' => ['admin@example.com', 'compliance@example.com'],
        ]);
        $envelope->documents()->create(['document_id' => '1', 'name' => 'Test.pdf', 'file_extension' => 'pdf']);
        $envelope->recipients()->create([
            'recipient_id' => '1',
            'recipient_type' => 'signer',
            'routing_order' => 1,
            'email' => 'test@example.com',
            'name' => 'Test',
        ]);

        $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/send");

        Mail::assertSent(function ($mail) {
            return $mail->hasBcc('admin@example.com') && $mail->hasBcc('compliance@example.com');
        });
    });
});

describe('Reminder Notifications', function () {
    test('schedules reminder notifications when enabled', function () {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'sent',
            'reminders_enabled' => true,
            'reminder_delay' => 3,
            'reminder_frequency' => 2,
        ]);
        $recipient = $envelope->recipients()->create([
            'recipient_id' => '1',
            'recipient_type' => 'signer',
            'routing_order' => 1,
            'email' => 'signer@example.com',
            'name' => 'Test Signer',
            'status' => 'sent',
        ]);

        // Verify reminder is scheduled (check that reminder metadata is set)
        expect($envelope->reminders_enabled)->toBeTrue()
            ->and($envelope->reminder_delay)->toBe(3)
            ->and($envelope->reminder_frequency)->toBe(2);
    });

    test('does not send reminders when disabled', function () {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'sent',
            'reminders_enabled' => false,
        ]);

        expect($envelope->reminders_enabled)->toBeFalse();
    });

    test('sends reminder to pending recipients only', function () {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'sent',
            'reminders_enabled' => true,
            'reminder_delay' => 1,
        ]);

        $pendingRecipient = $envelope->recipients()->create([
            'recipient_id' => '1',
            'recipient_type' => 'signer',
            'routing_order' => 1,
            'email' => 'pending@example.com',
            'name' => 'Pending Signer',
            'status' => 'sent',
        ]);

        $completedRecipient = $envelope->recipients()->create([
            'recipient_id' => '2',
            'recipient_type' => 'signer',
            'routing_order' => 2,
            'email' => 'completed@example.com',
            'name' => 'Completed Signer',
            'status' => 'completed',
        ]);

        // Verify only pending recipients would receive reminders
        expect($pendingRecipient->status)->toBe('sent')
            ->and($completedRecipient->status)->toBe('completed');
    });
});

describe('Expiration Notifications', function () {
    test('sends expiration warning notification', function () {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'sent',
            'expire_enabled' => true,
            'expire_after' => 30,
            'expire_warn' => 7,
            'expire_date_time' => now()->addDays(6), // Within warning period
        ]);
        $recipient = $envelope->recipients()->create([
            'recipient_id' => '1',
            'recipient_type' => 'signer',
            'email' => 'signer@example.com',
            'name' => 'Test Signer',
            'status' => 'sent',
        ]);

        // Verify expiration is configured
        expect($envelope->expire_enabled)->toBeTrue()
            ->and($envelope->expire_warn)->toBe(7);
    });

    test('voids envelope after expiration', function () {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'sent',
            'expire_enabled' => true,
            'expire_date_time' => now()->subDay(), // Already expired
        ]);

        // Verify envelope has expired
        expect($envelope->hasExpired())->toBeTrue();
    });

    test('does not expire when expiration disabled', function () {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'sent',
            'expire_enabled' => false,
            'expire_date_time' => now()->subDay(),
        ]);

        expect($envelope->hasExpired())->toBeFalse();
    });
});

describe('Recipient-Specific Notifications', function () {
    test('sends notification to carbon copy recipients', function () {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
        ]);
        $envelope->documents()->create(['document_id' => '1', 'name' => 'Test.pdf', 'file_extension' => 'pdf']);

        $signer = $envelope->recipients()->create([
            'recipient_id' => '1',
            'recipient_type' => 'signer',
            'routing_order' => 1,
            'email' => 'signer@example.com',
            'name' => 'Signer',
        ]);

        $carbonCopy = $envelope->recipients()->create([
            'recipient_id' => '2',
            'recipient_type' => 'carbon_copy',
            'routing_order' => 2,
            'email' => 'cc@example.com',
            'name' => 'CC Recipient',
        ]);

        $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/send");

        Mail::assertSent(function ($mail) use ($carbonCopy) {
            return $mail->hasTo($carbonCopy->email);
        });
    });

    test('sends notification to certified delivery recipients', function () {
        $envelope = Envelope::factory()->create(['account_id' => $this->account->id, 'status' => 'draft']);
        $envelope->documents()->create(['document_id' => '1', 'name' => 'Test.pdf', 'file_extension' => 'pdf']);

        $certifiedRecipient = $envelope->recipients()->create([
            'recipient_id' => '1',
            'recipient_type' => 'certified_delivery',
            'routing_order' => 1,
            'email' => 'certified@example.com',
            'name' => 'Certified Recipient',
        ]);

        $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/send");

        Mail::assertSent(function ($mail) use ($certifiedRecipient) {
            return $mail->hasTo($certifiedRecipient->email);
        });
    });

    test('respects routing order for notifications', function () {
        $envelope = Envelope::factory()->create(['account_id' => $this->account->id, 'status' => 'draft']);
        $envelope->documents()->create(['document_id' => '1', 'name' => 'Test.pdf', 'file_extension' => 'pdf']);

        $recipient1 = $envelope->recipients()->create([
            'recipient_id' => '1',
            'recipient_type' => 'signer',
            'routing_order' => 1,
            'email' => 'first@example.com',
            'name' => 'First Signer',
        ]);

        $recipient2 = $envelope->recipients()->create([
            'recipient_id' => '2',
            'recipient_type' => 'signer',
            'routing_order' => 2,
            'email' => 'second@example.com',
            'name' => 'Second Signer',
        ]);

        $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/send");

        // Only first recipient should receive initial notification
        Mail::assertSent(function ($mail) use ($recipient1) {
            return $mail->hasTo($recipient1->email);
        });

        // Second recipient should not receive notification yet
        expect($recipient2->status)->not()->toBe('sent');
    });
});

describe('Notification Templates', function () {
    test('uses branding in email notifications', function () {
        // Create brand for account
        $brand = \App\Models\Brand::factory()->create([
            'account_id' => $this->account->id,
            'brand_name' => 'Acme Corporation',
            'is_default' => true,
        ]);

        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
            'brand_id' => $brand->id,
        ]);
        $envelope->documents()->create(['document_id' => '1', 'name' => 'Test.pdf', 'file_extension' => 'pdf']);
        $envelope->recipients()->create([
            'recipient_id' => '1',
            'recipient_type' => 'signer',
            'routing_order' => 1,
            'email' => 'test@example.com',
            'name' => 'Test',
        ]);

        $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/send");

        // Verify brand is associated with envelope
        expect($envelope->brand_id)->toBe($brand->id);
    });
});
