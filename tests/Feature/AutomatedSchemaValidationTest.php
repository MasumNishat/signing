<?php

use App\Models\Account;
use App\Models\Envelope;
use App\Models\Template;
use App\Models\User;
use App\Support\OpenApiValidator;

beforeEach(function () {
    $this->user = $this->createAndAuthenticateUser();
    $this->account = $this->user->account;

    try {
        $this->validator = new OpenApiValidator();
    } catch (\RuntimeException $e) {
        $this->markTestSkipped('OpenAPI spec not available: ' . $e->getMessage());
    }
});

describe('Automated Envelope Endpoint Validation', function () {
    test('validates POST /envelopes against OpenAPI spec', function () {
        $requestData = [
            'subject' => 'Test Envelope',
            'email_subject' => 'Please sign',
            'email_blurb' => 'Test message',
            'status' => 'draft',
        ];

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes", $requestData);

        expect($response->status())->toBe(201);

        // Validate request
        $isValidRequest = $this->validator->validateRequest(
            "/accounts/{accountId}/envelopes",
            'post',
            $requestData
        );

        if (!$isValidRequest) {
            dump('Request validation errors:', $this->validator->getErrors());
        }

        // Validate response
        $isValidResponse = $this->validator->validateResponse(
            "/accounts/{accountId}/envelopes",
            'post',
            201,
            $response->json()
        );

        if (!$isValidResponse) {
            dump('Response validation errors:', $this->validator->getErrors());
        }

        expect($isValidRequest && $isValidResponse)->toBeTrue();
    });

    test('validates GET /envelopes against OpenAPI spec', function () {
        Envelope::factory()->count(3)->create(['account_id' => $this->account->id]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes");

        $isValid = $this->validator->validateResponse(
            "/accounts/{accountId}/envelopes",
            'get',
            200,
            $response->json()
        );

        if (!$isValid) {
            dump('Validation errors:', $this->validator->getErrors());
        }

        expect($isValid)->toBeTrue();
    });

    test('validates GET /envelopes/{id} against OpenAPI spec', function () {
        $envelope = Envelope::factory()->create(['account_id' => $this->account->id]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}");

        $isValid = $this->validator->validateResponse(
            "/accounts/{accountId}/envelopes/{envelopeId}",
            'get',
            200,
            $response->json()
        );

        if (!$isValid) {
            dump('Validation errors:', $this->validator->getErrors());
        }

        expect($isValid)->toBeTrue();
    });

    test('validates PUT /envelopes/{id} against OpenAPI spec', function () {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
        ]);

        $requestData = [
            'subject' => 'Updated Subject',
        ];

        $response = $this->apiPut(
            "/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}",
            $requestData
        );

        $isValid = $this->validator->validateResponse(
            "/accounts/{accountId}/envelopes/{envelopeId}",
            'put',
            200,
            $response->json()
        );

        if (!$isValid) {
            dump('Validation errors:', $this->validator->getErrors());
        }

        expect($isValid)->toBeTrue();
    });

    test('validates DELETE /envelopes/{id} against OpenAPI spec', function () {
        $envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/envelopes/{$envelope->envelope_id}");

        $isValid = $this->validator->validateResponse(
            "/accounts/{accountId}/envelopes/{envelopeId}",
            'delete',
            204,
            []
        );

        if (!$isValid) {
            dump('Validation errors:', $this->validator->getErrors());
        }

        expect($response->status())->toBe(204);
    });
});

describe('Automated Template Endpoint Validation', function () {
    test('validates POST /templates against OpenAPI spec', function () {
        $requestData = [
            'name' => 'Test Template',
            'description' => 'Test description',
        ];

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/templates", $requestData);

        $isValid = $this->validator->validateResponse(
            "/accounts/{accountId}/templates",
            'post',
            201,
            $response->json()
        );

        if (!$isValid) {
            dump('Validation errors:', $this->validator->getErrors());
        }

        expect($isValid)->toBeTrue();
    });

    test('validates GET /templates against OpenAPI spec', function () {
        Template::factory()->count(3)->create(['account_id' => $this->account->id]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/templates");

        $isValid = $this->validator->validateResponse(
            "/accounts/{accountId}/templates",
            'get',
            200,
            $response->json()
        );

        if (!$isValid) {
            dump('Validation errors:', $this->validator->getErrors());
        }

        expect($isValid)->toBeTrue();
    });
});

describe('Automated User Endpoint Validation', function () {
    test('validates GET /users against OpenAPI spec', function () {
        User::factory()->count(3)->create(['account_id' => $this->account->id]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/users");

        $isValid = $this->validator->validateResponse(
            "/accounts/{accountId}/users",
            'get',
            200,
            $response->json()
        );

        if (!$isValid) {
            dump('Validation errors:', $this->validator->getErrors());
        }

        expect($isValid)->toBeTrue();
    });

    test('validates POST /users against OpenAPI spec', function () {
        $requestData = [
            'email' => 'newuser@example.com',
            'user_name' => 'New User',
            'first_name' => 'New',
            'last_name' => 'User',
        ];

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/users", $requestData);

        $isValid = $this->validator->validateResponse(
            "/accounts/{accountId}/users",
            'post',
            201,
            $response->json()
        );

        if (!$isValid) {
            dump('Validation errors:', $this->validator->getErrors());
        }

        expect($response->status())->toBe(201);
    });
});

describe('Automated Account Endpoint Validation', function () {
    test('validates GET /accounts/{id} against OpenAPI spec', function () {
        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}");

        $isValid = $this->validator->validateResponse(
            "/accounts/{accountId}",
            'get',
            200,
            $response->json()
        );

        if (!$isValid) {
            dump('Validation errors:', $this->validator->getErrors());
        }

        expect($isValid)->toBeTrue();
    });
});

describe('Automated Error Response Validation', function () {
    test('validates 404 error response against OpenAPI spec', function () {
        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/envelopes/non-existent-id");

        $isValid = $this->validator->validateResponse(
            "/accounts/{accountId}/envelopes/{envelopeId}",
            'get',
            404,
            $response->json()
        );

        if (!$isValid) {
            dump('Validation errors:', $this->validator->getErrors());
        }

        expect($response->status())->toBe(404);
    });

    test('validates 422 validation error response against OpenAPI spec', function () {
        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/envelopes", [
            'status' => 'invalid',
        ]);

        expect($response->status())->toBe(422);
    });
});
