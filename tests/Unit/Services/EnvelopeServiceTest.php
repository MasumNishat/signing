<?php

use App\Models\Account;
use App\Models\Envelope;
use App\Models\EnvelopeDocument;
use App\Models\EnvelopeRecipient;
use App\Models\User;
use App\Services\EnvelopeService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->account = Account::factory()->create();
    $this->user = User::factory()->create(['account_id' => $this->account->id]);
    $this->service = new EnvelopeService();
});

describe('Envelope Creation', function () {
    test('creates envelope with default status draft', function () {
        $envelope = $this->service->createEnvelope($this->account->id, [
            'subject' => 'Test Envelope',
            'email_subject' => 'Please sign',
            'email_blurb' => 'Test message',
        ], $this->user->id);

        expect($envelope->status)->toBe('draft')
            ->and($envelope->subject)->toBe('Test Envelope')
            ->and($envelope->created_by_user_id)->toBe($this->user->id);
    });

    test('creates envelope with custom status', function () {
        $envelope = $this->service->createEnvelope($this->account->id, [
            'subject' => 'Test Envelope',
            'status' => 'created',
        ], $this->user->id);

        expect($envelope->status)->toBe('created');
    });

    test('generates unique envelope_id', function () {
        $envelope1 = $this->service->createEnvelope($this->account->id, [
            'subject' => 'Envelope 1',
        ], $this->user->id);

        $envelope2 = $this->service->createEnvelope($this->account->id, [
            'subject' => 'Envelope 2',
        ], $this->user->id);

        expect($envelope1->envelope_id)->not()->toBe($envelope2->envelope_id);
    });

    test('creates envelope with documents', function () {
        $envelope = $this->service->createEnvelope($this->account->id, [
            'subject' => 'Test Envelope',
            'documents' => [
                ['document_id' => '1', 'name' => 'Doc 1', 'file_extension' => 'pdf', 'order' => 1],
                ['document_id' => '2', 'name' => 'Doc 2', 'file_extension' => 'pdf', 'order' => 2],
            ],
        ], $this->user->id);

        expect($envelope->documents)->toHaveCount(2);
    });

    test('creates envelope with recipients', function () {
        $envelope = $this->service->createEnvelope($this->account->id, [
            'subject' => 'Test Envelope',
            'recipients' => [
                [
                    'recipient_type' => 'signer',
                    'routing_order' => 1,
                    'email' => 'signer@example.com',
                    'name' => 'Test Signer',
                ],
            ],
        ], $this->user->id);

        expect($envelope->recipients)->toHaveCount(1);
    });

    test('creates envelope with custom fields', function () {
        $envelope = $this->service->createEnvelope($this->account->id, [
            'subject' => 'Test Envelope',
            'custom_fields' => [
                ['field_id' => '1', 'name' => 'ProjectID', 'show' => true, 'required' => false, 'value' => 'PROJ-123'],
            ],
        ], $this->user->id);

        expect($envelope->customFields)->toHaveCount(1);
    });
});

describe('Envelope Retrieval', function () {
    test('gets envelope by id', function () {
        $created = Envelope::factory()->create(['account_id' => $this->account->id]);

        $envelope = $this->service->getEnvelope($created->envelope_id);

        expect($envelope->id)->toBe($created->id);
    });

    test('returns null for non-existent envelope', function () {
        $envelope = $this->service->getEnvelope('non-existent-id');

        expect($envelope)->toBeNull();
    });

    test('lists envelopes with pagination', function () {
        Envelope::factory()->count(15)->create(['account_id' => $this->account->id]);

        $result = $this->service->listEnvelopes($this->account->id, ['per_page' => 10]);

        expect($result)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class)
            ->and($result->count())->toBe(10)
            ->and($result->total())->toBe(15);
    });

    test('filters envelopes by status', function () {
        Envelope::factory()->count(5)->create(['account_id' => $this->account->id, 'status' => 'draft']);
        Envelope::factory()->count(3)->create(['account_id' => $this->account->id, 'status' => 'sent']);

        $result = $this->service->listEnvelopes($this->account->id, ['status' => 'draft']);

        expect($result->total())->toBe(5);
    });

    test('filters envelopes by date range', function () {
        Envelope::factory()->create([
            'account_id' => $this->account->id,
            'created_at' => now()->subDays(10),
        ]);
        Envelope::factory()->create([
            'account_id' => $this->account->id,
            'created_at' => now()->subDays(5),
        ]);
        Envelope::factory()->create([
            'account_id' => $this->account->id,
            'created_at' => now()->subDays(1),
        ]);

        $result = $this->service->listEnvelopes($this->account->id, [
            'from_date' => now()->subDays(6)->toDateString(),
            'to_date' => now()->toDateString(),
        ]);

        expect($result->total())->toBe(2);
    });

    test('searches envelopes by subject', function () {
        Envelope::factory()->create(['account_id' => $this->account->id, 'subject' => 'Important Contract']);
        Envelope::factory()->create(['account_id' => $this->account->id, 'subject' => 'Regular Document']);

        $result = $this->service->listEnvelopes($this->account->id, ['search_text' => 'Contract']);

        expect($result->total())->toBe(1);
    });

    test('gets envelope statistics', function () {
        Envelope::factory()->count(5)->create(['account_id' => $this->account->id, 'status' => 'draft']);
        Envelope::factory()->count(3)->create(['account_id' => $this->account->id, 'status' => 'sent']);
        Envelope::factory()->count(2)->create(['account_id' => $this->account->id, 'status' => 'completed']);

        $stats = $this->service->getEnvelopeStatistics($this->account->id);

        expect($stats)->toHaveKey('draft', 5)
            ->and($stats)->toHaveKey('sent', 3)
            ->and($stats)->toHaveKey('completed', 2);
    });
});

describe('Envelope Modification', function () {
    test('updates envelope when in draft status', function () {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
            'subject' => 'Original Subject',
        ]);

        $updated = $this->service->updateEnvelope($envelope->envelope_id, [
            'subject' => 'Updated Subject',
        ]);

        expect($updated->subject)->toBe('Updated Subject');
    });

    test('cannot update envelope when sent', function () {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'sent',
        ]);

        expect(fn() => $this->service->updateEnvelope($envelope->envelope_id, ['subject' => 'New']))
            ->toThrow(\App\Exceptions\Custom\BusinessLogicException::class);
    });

    test('sends envelope with validation', function () {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
        ]);
        EnvelopeDocument::factory()->create(['envelope_id' => $envelope->id]);
        EnvelopeRecipient::factory()->create(['envelope_id' => $envelope->id]);

        $sent = $this->service->sendEnvelope($envelope->envelope_id);

        expect($sent->status)->toBe('sent')
            ->and($sent->sent_date_time)->not()->toBeNull();
    });

    test('cannot send envelope without documents', function () {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
        ]);
        EnvelopeRecipient::factory()->create(['envelope_id' => $envelope->id]);

        expect(fn() => $this->service->sendEnvelope($envelope->envelope_id))
            ->toThrow(\App\Exceptions\Custom\ValidationException::class);
    });

    test('cannot send envelope without recipients', function () {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
        ]);
        EnvelopeDocument::factory()->create(['envelope_id' => $envelope->id]);

        expect(fn() => $this->service->sendEnvelope($envelope->envelope_id))
            ->toThrow(\App\Exceptions\Custom\ValidationException::class);
    });

    test('voids envelope with reason', function () {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'sent',
        ]);

        $voided = $this->service->voidEnvelope($envelope->envelope_id, 'Mistake in document');

        expect($voided->status)->toBe('voided')
            ->and($voided->voided_date_time)->not()->toBeNull()
            ->and($voided->voided_reason)->toBe('Mistake in document');
    });

    test('cannot void draft envelope', function () {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
        ]);

        expect(fn() => $this->service->voidEnvelope($envelope->envelope_id, 'Test reason'))
            ->toThrow(\App\Exceptions\Custom\BusinessLogicException::class);
    });

    test('deletes draft envelope', function () {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
        ]);

        $result = $this->service->deleteEnvelope($envelope->envelope_id);

        expect($result)->toBeTrue();
        $this->assertSoftDeleted('envelopes', ['id' => $envelope->id]);
    });

    test('cannot delete sent envelope', function () {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'sent',
        ]);

        expect(fn() => $this->service->deleteEnvelope($envelope->envelope_id))
            ->toThrow(\App\Exceptions\Custom\BusinessLogicException::class);
    });
});
