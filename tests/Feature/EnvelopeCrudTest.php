<?php

namespace Tests\Feature;

use App\Models\Envelope;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EnvelopeCrudTest extends ApiTestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_can_create_envelope()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes", [
            'subject' => 'Test Envelope',
            'email_subject' => 'Please sign this document',
            'email_blurb' => 'This is a test envelope',
            'status' => 'draft',
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();
        $response->assertJsonPath('data.subject', 'Test Envelope');
        $response->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('envelopes', [
            'account_id' => $this->account->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);
    }

    /** @test */
    public function test_can_get_envelope()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonPath('data.envelope_id', $envelope->envelope_id);
        $response->assertJsonPath('data.subject', 'Test Envelope');
    }

    /** @test */
    public function test_can_list_envelopes()
    {
        $this->createAndAuthenticateUser();

        // Create multiple envelopes
        for ($i = 1; $i <= 5; $i++) {
            Envelope::create([
                'account_id' => $this->account->id,
                'sender_user_id' => $this->user->id,
                'subject' => "Test Envelope {$i}",
                'status' => 'draft',
            ]);
        }

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
        $response->assertJsonPath('pagination.total', 5);
    }

    /** @test */
    public function test_can_update_draft_envelope()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Original Subject',
            'status' => 'draft',
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}", [
            'subject' => 'Updated Subject',
            'email_subject' => 'Updated Email Subject',
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonPath('data.subject', 'Updated Subject');

        $this->assertDatabaseHas('envelopes', [
            'id' => $envelope->id,
            'subject' => 'Updated Subject',
        ]);
    }

    /** @test */
    public function test_cannot_update_sent_envelope()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Sent Envelope',
            'status' => 'sent',
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}", [
            'subject' => 'Updated Subject',
        ]);

        $response->assertStatus(400);
        $this->assertErrorResponse();
    }

    /** @test */
    public function test_can_delete_draft_envelope()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'To Be Deleted',
            'status' => 'draft',
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('envelopes', [
            'id' => $envelope->id,
        ]);
    }

    /** @test */
    public function test_cannot_delete_sent_envelope()
    {
        $this->createAndAuthenticateUser();

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Sent Envelope',
            'status' => 'sent',
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}");

        $response->assertStatus(400);
        $this->assertErrorResponse();
    }

    /** @test */
    public function test_can_get_envelope_statistics()
    {
        $this->createAndAuthenticateUser();

        // Create envelopes with different statuses
        Envelope::create(['account_id' => $this->account->id, 'sender_user_id' => $this->user->id, 'subject' => 'Draft 1', 'status' => 'draft']);
        Envelope::create(['account_id' => $this->account->id, 'sender_user_id' => $this->user->id, 'subject' => 'Draft 2', 'status' => 'draft']);
        Envelope::create(['account_id' => $this->account->id, 'sender_user_id' => $this->user->id, 'subject' => 'Sent 1', 'status' => 'sent']);
        Envelope::create(['account_id' => $this->account->id, 'sender_user_id' => $this->user->id, 'subject' => 'Completed', 'status' => 'completed']);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/statistics");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonPath('data.draft', 2);
        $response->assertJsonPath('data.sent', 1);
        $response->assertJsonPath('data.completed', 1);
    }

    /** @test */
    public function test_validates_required_fields_on_create()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes", []);

        $response->assertStatus(422);
        $this->assertValidationErrors(['subject', 'status']);
    }

    /** @test */
    public function test_can_filter_envelopes_by_status()
    {
        $this->createAndAuthenticateUser();

        Envelope::create(['account_id' => $this->account->id, 'sender_user_id' => $this->user->id, 'subject' => 'Draft', 'status' => 'draft']);
        Envelope::create(['account_id' => $this->account->id, 'sender_user_id' => $this->user->id, 'subject' => 'Sent', 'status' => 'sent']);
        Envelope::create(['account_id' => $this->account->id, 'sender_user_id' => $this->user->id, 'subject' => 'Completed', 'status' => 'completed']);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes?status=draft");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
        $response->assertJsonPath('pagination.total', 1);
        $response->assertJsonPath('data.0.status', 'draft');
    }

    /** @test */
    public function test_can_search_envelopes_by_subject()
    {
        $this->createAndAuthenticateUser();

        Envelope::create(['account_id' => $this->account->id, 'sender_user_id' => $this->user->id, 'subject' => 'Contract Agreement', 'status' => 'draft']);
        Envelope::create(['account_id' => $this->account->id, 'sender_user_id' => $this->user->id, 'subject' => 'NDA Document', 'status' => 'draft']);
        Envelope::create(['account_id' => $this->account->id, 'sender_user_id' => $this->user->id, 'subject' => 'Other Document', 'status' => 'draft']);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes?search=Contract");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
        $response->assertJsonPath('pagination.total', 1);
        $response->assertJsonPath('data.0.subject', 'Contract Agreement');
    }

    /** @test */
    public function test_can_sort_envelopes()
    {
        $this->createAndAuthenticateUser();

        Envelope::create(['account_id' => $this->account->id, 'sender_user_id' => $this->user->id, 'subject' => 'Z Document', 'status' => 'draft', 'created_at' => now()->subDays(2)]);
        Envelope::create(['account_id' => $this->account->id, 'sender_user_id' => $this->user->id, 'subject' => 'A Document', 'status' => 'draft', 'created_at' => now()->subDays(1)]);
        Envelope::create(['account_id' => $this->account->id, 'sender_user_id' => $this->user->id, 'subject' => 'M Document', 'status' => 'draft', 'created_at' => now()]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes?sort=subject&order=asc");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
        $response->assertJsonPath('data.0.subject', 'A Document');
        $response->assertJsonPath('data.2.subject', 'Z Document');
    }

    /** @test */
    public function test_can_paginate_envelopes()
    {
        $this->createAndAuthenticateUser();

        // Create 25 envelopes
        for ($i = 1; $i <= 25; $i++) {
            Envelope::create([
                'account_id' => $this->account->id,
                'sender_user_id' => $this->user->id,
                'subject' => "Envelope {$i}",
                'status' => 'draft',
            ]);
        }

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes?per_page=10&page=2");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
        $response->assertJsonPath('pagination.total', 25);
        $response->assertJsonPath('pagination.current_page', 2);
        $response->assertJsonPath('pagination.per_page', 10);
    }
}
