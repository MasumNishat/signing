<?php

use App\Models\Account;
use App\Models\Envelope;
use App\Models\Template;
use App\Models\User;

beforeEach(function () {
    $this->user = $this->createAndAuthenticateUser();
    $this->account = $this->user->account;

    // Load OpenAPI spec
    $specPath = base_path('docs/openapi.json');
    if (!file_exists($specPath)) {
        $this->markTestSkipped('OpenAPI specification not found at ' . $specPath);
    }

    $this->openApiSpec = json_decode(file_get_contents($specPath), true);
});

describe('Envelope Schema Validation', function () {
    test('POST /envelopes conforms to schema', function () {
        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes", [
            'subject' => 'Test Envelope',
            'email_subject' => 'Please sign this document',
            'email_blurb' => 'This is a test envelope',
            'status' => 'draft',
        ]);

        $response->assertStatus(201);

        // Validate response structure
        $data = $response->json('data');
        expect($data)->toHaveKeys(['envelope_id', 'subject', 'status', 'created_at']);
    });

    test('GET /envelopes response conforms to schema', function () {
        Envelope::factory()->count(5)->create(['account_id' => $this->account->id]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();

        $data = $response->json('data');
        expect($data)->toBeArray()
            ->and(count($data))->toBeGreaterThan(0);

        // Validate each envelope in response
        foreach ($data as $envelope) {
            expect($envelope)->toHaveKeys(['envelope_id', 'subject', 'status']);
        }
    });

    test('GET /envelopes/{id} response conforms to schema', function () {
        $envelope = Envelope::factory()->create(['account_id' => $this->account->id]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}");

        $response->assertStatus(200);

        $data = $response->json('data');
        expect($data)->toHaveKeys([
            'envelope_id', 'subject', 'status', 'email_subject',
            'email_blurb', 'created_at', 'updated_at'
        ]);
    });

    test('PUT /envelopes/{id} request/response conforms to schema', function () {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}", [
            'subject' => 'Updated Subject',
            'email_subject' => 'Updated Email Subject',
        ]);

        $response->assertStatus(200);

        $data = $response->json('data');
        expect($data['subject'])->toBe('Updated Subject');
    });

    test('POST /envelopes/{id}/send response conforms to schema', function () {
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

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/send");

        $response->assertStatus(200);

        $data = $response->json('data');
        expect($data['status'])->toBe('sent')
            ->and($data)->toHaveKey('sent_date_time');
    });
});

describe('Template Schema Validation', function () {
    test('POST /templates conforms to schema', function () {
        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/templates", [
            'name' => 'Test Template',
            'description' => 'Test description',
            'shared' => false,
        ]);

        $response->assertStatus(201);

        $data = $response->json('data');
        expect($data)->toHaveKeys(['template_id', 'name', 'description', 'shared']);
    });

    test('GET /templates response conforms to schema', function () {
        Template::factory()->count(3)->create(['account_id' => $this->account->id]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/templates");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();

        $data = $response->json('data');
        foreach ($data as $template) {
            expect($template)->toHaveKeys(['template_id', 'name']);
        }
    });
});

describe('Account Schema Validation', function () {
    test('GET /accounts/{id} response conforms to schema', function () {
        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}");

        $response->assertStatus(200);

        $data = $response->json('data');
        expect($data)->toHaveKeys(['account_id', 'account_name', 'plan_id']);
    });

    test('GET /accounts/{id}/users response conforms to schema', function () {
        User::factory()->count(3)->create(['account_id' => $this->account->id]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/users");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();

        $data = $response->json('data');
        foreach ($data as $user) {
            expect($user)->toHaveKeys(['user_id', 'email', 'user_name']);
        }
    });
});

describe('Billing Schema Validation', function () {
    test('GET /accounts/{id}/billing/plans response conforms to schema', function () {
        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/billing/plans");

        $response->assertStatus(200);

        $data = $response->json('data');
        expect($data)->toBeArray();
    });

    test('GET /accounts/{id}/billing/invoices response conforms to schema', function () {
        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/billing/invoices");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
    });
});

describe('Error Response Schema Validation', function () {
    test('404 error response conforms to schema', function () {
        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/non-existent-id");

        $response->assertStatus(404);
        $this->assertErrorResponse('RESOURCE_NOT_FOUND');

        expect($response->json())->toHaveKeys(['success', 'error', 'meta']);
    });

    test('422 validation error response conforms to schema', function () {
        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes", [
            // Missing required fields
            'status' => 'invalid_status',
        ]);

        $response->assertStatus(422);

        $json = $response->json();
        expect($json)->toHaveKeys(['success', 'error', 'meta'])
            ->and($json['error'])->toHaveKey('validation_errors');
    });

    test('401 unauthorized response conforms to schema', function () {
        // Make request without authentication
        $response = $this->withHeaders([])->getJson("/api/v2.1/accounts/{$this->account->account_id}/envelopes");

        $response->assertStatus(401);

        expect($response->json())->toHaveKeys(['success', 'error', 'meta']);
    });

    test('403 forbidden response conforms to schema', function () {
        // Try to access another account
        $otherAccount = Account::factory()->create();

        $response = $this->apiGet("/api/v2.1/accounts/{$otherAccount->account_id}/envelopes");

        $response->assertStatus(403);

        expect($response->json())->toHaveKeys(['success', 'error', 'meta']);
    });
});

describe('Request Parameter Validation', function () {
    test('validates required fields in POST requests', function () {
        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes", []);

        $response->assertStatus(422);
        $this->assertValidationErrors(['subject']);
    });

    test('validates field types', function () {
        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes", [
            'subject' => 'Test',
            'email_subject' => 123, // Should be string
        ]);

        $response->assertStatus(422);
    });

    test('validates enum values', function () {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}", [
            'status' => 'invalid_status',
        ]);

        $response->assertStatus(422);
    });

    test('validates maximum length constraints', function () {
        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes", [
            'subject' => str_repeat('a', 10001), // Exceeds max length
        ]);

        $response->assertStatus(422);
    });

    test('validates email format', function () {
        $envelope = Envelope::factory()->create(['account_id' => $this->account->id]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/recipients", [
            'recipients' => [[
                'recipient_type' => 'signer',
                'routing_order' => 1,
                'email' => 'not-an-email',
                'name' => 'Test',
            ]],
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['recipients.0.email']);
    });
});

describe('Response Field Types Validation', function () {
    test('envelope_id is string UUID', function () {
        $envelope = Envelope::factory()->create(['account_id' => $this->account->id]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}");

        $data = $response->json('data');
        expect($data['envelope_id'])->toBeString()
            ->and(strlen($data['envelope_id']))->toBeGreaterThan(0);
    });

    test('timestamps are ISO8601 format', function () {
        $envelope = Envelope::factory()->create(['account_id' => $this->account->id]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}");

        $data = $response->json('data');
        expect($data['created_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d+Z$/');
    });

    test('boolean fields are actual booleans', function () {
        $template = Template::factory()->create(['account_id' => $this->account->id, 'shared' => true]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/templates/{$template->template_id}");

        $data = $response->json('data');
        expect($data['shared'])->toBeBool();
    });

    test('numeric fields are numbers', function () {
        $envelope = Envelope::factory()->create(['account_id' => $this->account->id]);
        $envelope->recipients()->create([
            'recipient_id' => '1',
            'recipient_type' => 'signer',
            'routing_order' => 1,
            'email' => 'test@example.com',
            'name' => 'Test',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}/recipients");

        $data = $response->json('data');
        expect($data[0]['routing_order'])->toBeInt();
    });
});

describe('Pagination Schema Validation', function () {
    test('pagination meta structure is correct', function () {
        Envelope::factory()->count(25)->create(['account_id' => $this->account->id]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes?per_page=10");

        $response->assertStatus(200);

        $meta = $response->json('meta.pagination');
        expect($meta)->toHaveKeys(['total', 'count', 'per_page', 'current_page', 'total_pages'])
            ->and($meta['total'])->toBe(25)
            ->and($meta['count'])->toBe(10)
            ->and($meta['per_page'])->toBe(10)
            ->and($meta['current_page'])->toBe(1)
            ->and($meta['total_pages'])->toBe(3);
    });

    test('pagination links structure is correct', function () {
        Envelope::factory()->count(25)->create(['account_id' => $this->account->id]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes?per_page=10");

        $links = $response->json('meta.pagination.links');
        expect($links)->toHaveKeys(['first', 'last', 'prev', 'next']);
    });
});
