<?php

use App\Models\Account;
use App\Models\Envelope;
use App\Models\EnvelopeDocument;
use App\Models\EnvelopeRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->account = Account::factory()->create();
    $this->user = User::factory()->create(['account_id' => $this->account->id]);
});

describe('Envelope Status Helpers', function () {
    test('isDraft returns true for draft envelopes', function () {
        $envelope = Envelope::factory()->create(['status' => 'draft']);

        expect($envelope->isDraft())->toBeTrue();
    });

    test('isDraft returns false for non-draft envelopes', function () {
        $envelope = Envelope::factory()->create(['status' => 'sent']);

        expect($envelope->isDraft())->toBeFalse();
    });

    test('isSent returns true for sent envelopes', function () {
        $envelope = Envelope::factory()->create(['status' => 'sent']);

        expect($envelope->isSent())->toBeTrue();
    });

    test('isCompleted returns true for completed envelopes', function () {
        $envelope = Envelope::factory()->create(['status' => 'completed']);

        expect($envelope->isCompleted())->toBeTrue();
    });

    test('isVoided returns true for voided envelopes', function () {
        $envelope = Envelope::factory()->create(['status' => 'voided']);

        expect($envelope->isVoided())->toBeTrue();
    });

    test('canBeModified returns true for draft envelopes', function () {
        $envelope = Envelope::factory()->create(['status' => 'draft']);

        expect($envelope->canBeModified())->toBeTrue();
    });

    test('canBeModified returns false for sent envelopes', function () {
        $envelope = Envelope::factory()->create(['status' => 'sent']);

        expect($envelope->canBeModified())->toBeFalse();
    });

    test('canBeVoided returns true for sent envelopes', function () {
        $envelope = Envelope::factory()->create(['status' => 'sent']);

        expect($envelope->canBeVoided())->toBeTrue();
    });

    test('canBeVoided returns false for completed envelopes', function () {
        $envelope = Envelope::factory()->create(['status' => 'completed']);

        expect($envelope->canBeVoided())->toBeFalse();
    });

    test('hasExpired returns true when expiration date passed', function () {
        $envelope = Envelope::factory()->create([
            'expire_enabled' => true,
            'expire_date_time' => now()->subDay(),
        ]);

        expect($envelope->hasExpired())->toBeTrue();
    });

    test('hasExpired returns false when expiration not enabled', function () {
        $envelope = Envelope::factory()->create([
            'expire_enabled' => false,
            'expire_date_time' => now()->subDay(),
        ]);

        expect($envelope->hasExpired())->toBeFalse();
    });

    test('hasExpired returns false when expiration date not reached', function () {
        $envelope = Envelope::factory()->create([
            'expire_enabled' => true,
            'expire_date_time' => now()->addDay(),
        ]);

        expect($envelope->hasExpired())->toBeFalse();
    });
});

describe('Envelope State Transitions', function () {
    test('markAsSent updates status and sets sent timestamp', function () {
        $envelope = Envelope::factory()->create(['status' => 'draft']);

        $envelope->markAsSent();

        expect($envelope->status)->toBe('sent')
            ->and($envelope->sent_date_time)->not()->toBeNull();
    });

    test('markAsVoided updates status and sets void timestamp', function () {
        $envelope = Envelope::factory()->create(['status' => 'sent']);

        $envelope->markAsVoided('Test reason');

        expect($envelope->status)->toBe('voided')
            ->and($envelope->voided_date_time)->not()->toBeNull()
            ->and($envelope->voided_reason)->toBe('Test reason');
    });

    test('markAsCompleted updates status and sets completed timestamp', function () {
        $envelope = Envelope::factory()->create(['status' => 'sent']);

        $envelope->markAsCompleted();

        expect($envelope->status)->toBe('completed')
            ->and($envelope->completed_date_time)->not()->toBeNull();
    });
});

describe('Envelope Query Scopes', function () {
    test('withStatus scope filters by status', function () {
        Envelope::factory()->count(5)->create(['status' => 'draft']);
        Envelope::factory()->count(3)->create(['status' => 'sent']);

        $drafts = Envelope::withStatus('draft')->get();

        expect($drafts)->toHaveCount(5);
    });

    test('sent scope returns only sent envelopes', function () {
        Envelope::factory()->count(5)->create(['status' => 'sent']);
        Envelope::factory()->count(3)->create(['status' => 'draft']);

        $sent = Envelope::sent()->get();

        expect($sent)->toHaveCount(5);
    });

    test('completed scope returns only completed envelopes', function () {
        Envelope::factory()->count(2)->create(['status' => 'completed']);
        Envelope::factory()->count(3)->create(['status' => 'sent']);

        $completed = Envelope::completed()->get();

        expect($completed)->toHaveCount(2);
    });

    test('voided scope returns only voided envelopes', function () {
        Envelope::factory()->count(4)->create(['status' => 'voided']);
        Envelope::factory()->count(3)->create(['status' => 'sent']);

        $voided = Envelope::voided()->get();

        expect($voided)->toHaveCount(4);
    });

    test('forAccount scope filters by account', function () {
        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();

        Envelope::factory()->count(5)->create(['account_id' => $account1->id]);
        Envelope::factory()->count(3)->create(['account_id' => $account2->id]);

        $envelopes = Envelope::forAccount($account1->id)->get();

        expect($envelopes)->toHaveCount(5);
    });

    test('sentBy scope filters by sender', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Envelope::factory()->count(5)->create(['sender_user_id' => $user1->id]);
        Envelope::factory()->count(3)->create(['sender_user_id' => $user2->id]);

        $envelopes = Envelope::sentBy($user1->id)->get();

        expect($envelopes)->toHaveCount(5);
    });

    test('createdBetween scope filters by date range', function () {
        Envelope::factory()->create(['created_at' => now()->subDays(10)]);
        Envelope::factory()->create(['created_at' => now()->subDays(5)]);
        Envelope::factory()->create(['created_at' => now()->subDays(1)]);

        $envelopes = Envelope::createdBetween(
            now()->subDays(6),
            now()
        )->get();

        expect($envelopes)->toHaveCount(2);
    });
});

describe('Envelope Relationships', function () {
    test('has documents relationship', function () {
        $envelope = Envelope::factory()->create();
        EnvelopeDocument::factory()->count(3)->create(['envelope_id' => $envelope->id]);

        expect($envelope->documents)->toHaveCount(3);
    });

    test('has recipients relationship', function () {
        $envelope = Envelope::factory()->create();
        EnvelopeRecipient::factory()->count(5)->create(['envelope_id' => $envelope->id]);

        expect($envelope->recipients)->toHaveCount(5);
    });

    test('belongs to account', function () {
        $account = Account::factory()->create();
        $envelope = Envelope::factory()->create(['account_id' => $account->id]);

        expect($envelope->account->id)->toBe($account->id);
    });

    test('belongs to sender user', function () {
        $user = User::factory()->create();
        $envelope = Envelope::factory()->create(['sender_user_id' => $user->id]);

        expect($envelope->sender->id)->toBe($user->id);
    });
});
