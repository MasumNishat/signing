<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\Feature\ApiTestCase;

class AuthenticationTest extends ApiTestCase
{
    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v2.1/auth/register', [
            'account_name' => 'Test Company',
            'user_name' => 'testuser',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();
        $response->assertJsonStructure([
            'data' => [
                'user',
                'account',
                'access_token',
                'token_type',
            ],
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
        ]);
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = $this->createAndAuthenticateUser([
            'email' => 'login@example.com',
            'password' => \Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v2.1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonStructure([
            'data' => [
                'user',
                'access_token',
                'token_type',
                'expires_in',
            ],
        ]);
    }

    public function test_user_cannot_login_with_incorrect_credentials(): void
    {
        $user = $this->createAndAuthenticateUser([
            'email' => 'user@example.com',
        ]);

        $response = $this->postJson('/api/v2.1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
        $this->assertErrorResponse();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $response = $this->apiPost('/api/v2.1/auth/logout');

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        $response = $this->apiGet('/api/v2.1/auth/user');

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'user_name',
                'email',
                'account_id',
            ],
        ]);
    }

    public function test_unauthenticated_user_cannot_access_protected_route(): void
    {
        $response = $this->getJson('/api/v2.1/auth/user');

        $response->assertStatus(401);
        $this->assertErrorResponse('UNAUTHENTICATED');
    }
}
