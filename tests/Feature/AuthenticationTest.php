<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ApiKey;
use App\Models\UserAuthorization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AuthenticationTest extends ApiTestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_user_can_register()
    {
        $this->seed(\Database\Seeders\PlanSeeder::class);

        $response = $this->json('POST', '/api/v2.1/auth/register', [
            'name' => 'Test User',
            'email' => 'register@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => 'register@example.com',
        ]);
    }

    /** @test */
    public function test_user_can_login_with_correct_credentials()
    {
        $this->createAndAuthenticateUser();

        $response = $this->json('POST', '/api/v2.1/auth/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'access_token',
                'token_type',
                'expires_in',
            ],
        ]);
    }

    /** @test */
    public function test_user_cannot_login_with_incorrect_password()
    {
        $this->createAndAuthenticateUser();

        $response = $this->json('POST', '/api/v2.1/auth/login', [
            'email' => $this->user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function test_user_cannot_login_with_nonexistent_email()
    {
        $response = $this->json('POST', '/api/v2.1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function test_authenticated_user_can_logout()
    {
        $this->createAndAuthenticateUser();

        $response = $this->actingAsUser()->json('POST', '/api/v2.1/auth/logout');

        $response->assertStatus(200);
    }

    /** @test */
    public function test_authenticated_user_can_get_profile()
    {
        $this->createAndAuthenticateUser();

        $response = $this->actingAsUser()->json('GET', '/api/v2.1/auth/me');

        $response->assertStatus(200);
        $response->assertJsonPath('data.email', $this->user->email);
    }

    /** @test */
    public function test_unauthenticated_user_cannot_access_protected_route()
    {
        $response = $this->json('GET', '/api/v2.1/auth/me');

        $response->assertStatus(401);
    }

    /** @test */
    public function test_can_refresh_access_token()
    {
        $this->createAndAuthenticateUser();

        $loginResponse = $this->json('POST', '/api/v2.1/auth/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        $refreshToken = $loginResponse->json('data.refresh_token');

        $response = $this->json('POST', '/api/v2.1/auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'access_token',
            ],
        ]);
    }

    /** @test */
    public function test_can_create_api_key()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/api_keys", [
            'key_name' => 'Test API Key',
            'scopes' => ['signature_read', 'signature_write'],
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('api_keys', [
            'account_id' => $this->account->id,
            'key_name' => 'Test API Key',
        ]);
    }

    /** @test */
    public function test_can_list_api_keys()
    {
        $this->createAndAuthenticateUser();

        ApiKey::create([
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'key_name' => 'Key 1',
            'api_key' => 'key_' . \Str::random(32),
            'scopes' => ['signature_read'],
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/api_keys");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_get_specific_api_key()
    {
        $this->createAndAuthenticateUser();

        $apiKey = ApiKey::create([
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'key_name' => 'Test Key',
            'api_key' => 'key_' . \Str::random(32),
            'scopes' => ['signature_read'],
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/api_keys/{$apiKey->id}");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_update_api_key()
    {
        $this->createAndAuthenticateUser();

        $apiKey = ApiKey::create([
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'key_name' => 'Original',
            'api_key' => 'key_' . \Str::random(32),
            'scopes' => ['signature_read'],
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/api_keys/{$apiKey->id}", [
            'key_name' => 'Updated Key',
            'scopes' => ['signature_read', 'signature_write'],
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('api_keys', [
            'id' => $apiKey->id,
            'key_name' => 'Updated Key',
        ]);
    }

    /** @test */
    public function test_can_revoke_api_key()
    {
        $this->createAndAuthenticateUser();

        $apiKey = ApiKey::create([
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'key_name' => 'To Revoke',
            'api_key' => 'key_' . \Str::random(32),
            'scopes' => ['signature_read'],
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/api_keys/{$apiKey->id}");

        $response->assertStatus(204);

        $apiKey->refresh();
        $this->assertTrue($apiKey->is_revoked);
    }

    /** @test */
    public function test_can_rotate_api_key()
    {
        $this->createAndAuthenticateUser();

        $apiKey = ApiKey::create([
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'key_name' => 'To Rotate',
            'api_key' => 'key_old',
            'scopes' => ['signature_read'],
        ]);

        $oldKey = $apiKey->api_key;

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/api_keys/{$apiKey->id}/rotate");

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $apiKey->refresh();
        $this->assertNotEquals($oldKey, $apiKey->api_key);
    }

    /** @test */
    public function test_validates_api_key_scopes()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/api_keys", [
            'key_name' => 'Test Key',
            'scopes' => ['invalid_scope'],
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['scopes']);
    }

    /** @test */
    public function test_can_create_user_authorization()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/users/{$this->user->id}/authorization", [
            'agent_user_id' => $this->user->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('user_authorizations', [
            'principal_user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function test_can_list_user_authorizations()
    {
        $this->createAndAuthenticateUser();

        UserAuthorization::create([
            'principal_user_id' => $this->user->id,
            'agent_user_id' => $this->user->id,
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/users/{$this->user->id}/authorization");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_delete_user_authorization()
    {
        $this->createAndAuthenticateUser();

        $auth = UserAuthorization::create([
            'principal_user_id' => $this->user->id,
            'agent_user_id' => $this->user->id,
            'start_date' => now(),
            'end_date' => now()->addDays(30),
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/users/{$this->user->id}/authorization/{$auth->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('user_authorizations', [
            'id' => $auth->id,
        ]);
    }

    /** @test */
    public function test_validates_password_strength_on_register()
    {
        $this->seed(\Database\Seeders\PlanSeeder::class);

        $response = $this->json('POST', '/api/v2.1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['password']);
    }

    /** @test */
    public function test_requires_password_confirmation_on_register()
    {
        $this->seed(\Database\Seeders\PlanSeeder::class);

        $response = $this->json('POST', '/api/v2.1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'SecurePass123!',
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['password']);
    }

    /** @test */
    public function test_prevents_duplicate_email_registration()
    {
        $this->createAndAuthenticateUser();

        $response = $this->json('POST', '/api/v2.1/auth/register', [
            'name' => 'Duplicate User',
            'email' => $this->user->email,
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['email']);
    }

    /** @test */
    public function test_api_key_can_authenticate_requests()
    {
        $this->createAndAuthenticateUser();

        $apiKey = ApiKey::create([
            'account_id' => $this->account->id,
            'user_id' => $this->user->id,
            'key_name' => 'Test Key',
            'api_key' => 'key_test_123',
            'scopes' => ['signature_read'],
        ]);

        $response = $this->json('GET', "/api/v2.1/accounts/{$this->account->account_id}/signatures", [], [
            'Authorization' => 'Bearer key_test_123',
        ]);

        $response->assertStatus(200);
    }
}
