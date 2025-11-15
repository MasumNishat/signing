<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Account;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    /**
     * The authenticated user for the test.
     */
    protected ?User $user = null;

    /**
     * The account for the test.
     */
    protected ?Account $account = null;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations
        $this->artisan('migrate:fresh');

        // Seed essential data
        $this->seed(\Database\Seeders\FileTypeSeeder::class);
        $this->seed(\Database\Seeders\SupportedLanguageSeeder::class);
        $this->seed(\Database\Seeders\SignatureProviderSeeder::class);
        $this->seed(\Database\Seeders\PlanSeeder::class);
    }

    /**
     * Create and authenticate a user for API testing.
     *
     * @param array $attributes
     * @param array $accountAttributes
     * @return User
     */
    protected function createAndAuthenticateUser(array $attributes = [], array $accountAttributes = []): User
    {
        // Create account if not exists
        if (!$this->account) {
            $plan = Plan::where('is_free', true)->first();

            $this->account = Account::create(array_merge([
                'plan_id' => $plan->id,
                'account_id' => 'acc_test_' . \Str::random(12),
                'account_name' => 'Test Account',
                'billing_period_envelopes_sent' => 0,
                'billing_period_envelopes_allowed' => $plan->envelope_allowance,
                'created_date' => now(),
            ], $accountAttributes));
        }

        // Create user
        $this->user = User::create(array_merge([
            'account_id' => $this->account->id,
            'user_name' => 'testuser',
            'email' => 'test@example.com',
            'password' => \Hash::make('password'),
            'user_status' => 'active',
            'user_type' => 'admin',
            'is_admin' => true,
            'created_datetime' => now(),
        ], $attributes));

        return $this->user;
    }

    /**
     * Act as the authenticated user.
     *
     * @param User|null $user
     * @return $this
     */
    protected function actingAsUser(?User $user = null): self
    {
        $user = $user ?? $this->user;

        if (!$user) {
            $user = $this->createAndAuthenticateUser();
        }

        $this->actingAs($user, 'api');

        return $this;
    }

    /**
     * Make an authenticated API request.
     *
     * @param string $method
     * @param string $uri
     * @param array $data
     * @param array $headers
     * @return \Illuminate\Testing\TestResponse
     */
    protected function apiRequest(
        string $method,
        string $uri,
        array $data = [],
        array $headers = []
    ): \Illuminate\Testing\TestResponse {
        if (!$this->user) {
            $this->createAndAuthenticateUser();
        }

        $this->actingAs($this->user, 'api');

        $headers = array_merge([
            'Accept' => 'application/json',
            'X-Request-ID' => \Str::uuid()->toString(),
        ], $headers);

        return $this->json($method, $uri, $data, $headers);
    }

    /**
     * Make a GET API request.
     */
    protected function apiGet(string $uri, array $headers = []): \Illuminate\Testing\TestResponse
    {
        return $this->apiRequest('GET', $uri, [], $headers);
    }

    /**
     * Make a POST API request.
     */
    protected function apiPost(string $uri, array $data = [], array $headers = []): \Illuminate\Testing\TestResponse
    {
        return $this->apiRequest('POST', $uri, $data, $headers);
    }

    /**
     * Make a PUT API request.
     */
    protected function apiPut(string $uri, array $data = [], array $headers = []): \Illuminate\Testing\TestResponse
    {
        return $this->apiRequest('PUT', $uri, $data, $headers);
    }

    /**
     * Make a PATCH API request.
     */
    protected function apiPatch(string $uri, array $data = [], array $headers = []): \Illuminate\Testing\TestResponse
    {
        return $this->apiRequest('PATCH', $uri, $data, $headers);
    }

    /**
     * Make a DELETE API request.
     */
    protected function apiDelete(string $uri, array $data = [], array $headers = []): \Illuminate\Testing\TestResponse
    {
        return $this->apiRequest('DELETE', $uri, $data, $headers);
    }

    /**
     * Assert that the response has a successful structure.
     */
    protected function assertSuccessResponse(): void
    {
        $this->assertJsonStructure([
            'success',
            'data',
            'meta' => [
                'timestamp',
                'request_id',
                'version',
            ],
        ]);

        $this->assertJsonFragment(['success' => true]);
        $this->assertJsonFragment(['version' => 'v2.1']);
    }

    /**
     * Assert that the response has an error structure.
     */
    protected function assertErrorResponse(string $errorCode = null): void
    {
        $this->assertJsonStructure([
            'success',
            'error' => [
                'code',
                'message',
            ],
            'meta' => [
                'timestamp',
                'request_id',
                'version',
            ],
        ]);

        $this->assertJsonFragment(['success' => false]);
        $this->assertJsonFragment(['version' => 'v2.1']);

        if ($errorCode) {
            $this->assertJsonFragment(['code' => $errorCode]);
        }
    }

    /**
     * Assert that the response has a paginated structure.
     */
    protected function assertPaginatedResponse(): void
    {
        $this->assertJsonStructure([
            'success',
            'data',
            'pagination' => [
                'total',
                'per_page',
                'current_page',
                'last_page',
                'from',
                'to',
                'has_more_pages',
            ],
            'meta' => [
                'timestamp',
                'request_id',
                'version',
            ],
        ]);

        $this->assertJsonFragment(['success' => true]);
    }

    /**
     * Assert validation errors for specific fields.
     */
    protected function assertValidationErrors(array $fields): void
    {
        $this->assertErrorResponse('VALIDATION_ERROR');

        $response = $this->json();

        $this->assertArrayHasKey('details', $response['error']);

        foreach ($fields as $field) {
            $this->assertArrayHasKey($field, $response['error']['details']);
        }
    }
}
