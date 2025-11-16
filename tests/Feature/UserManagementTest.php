<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Contact;
use App\Models\UserProfile;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UserManagementTest extends ApiTestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_can_list_users()
    {
        $this->createAndAuthenticateUser();

        // Create additional users
        User::create([
            'account_id' => $this->account->id,
            'user_name' => 'user2',
            'email' => 'user2@example.com',
            'password' => \Hash::make('password'),
            'user_status' => 'active',
            'user_type' => 'member',
            'created_datetime' => now(),
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/users");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
        $response->assertJsonPath('pagination.total', 2); // Including the authenticated user
    }

    /** @test */
    public function test_can_create_user()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/users", [
            'user_name' => 'newuser',
            'email' => 'newuser@example.com',
            'first_name' => 'New',
            'last_name' => 'User',
            'user_status' => 'active',
            'user_type' => 'member',
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('users', [
            'account_id' => $this->account->id,
            'email' => 'newuser@example.com',
        ]);
    }

    /** @test */
    public function test_can_get_specific_user()
    {
        $this->createAndAuthenticateUser();

        $newUser = User::create([
            'account_id' => $this->account->id,
            'user_name' => 'testuser2',
            'email' => 'testuser2@example.com',
            'password' => \Hash::make('password'),
            'user_status' => 'active',
            'user_type' => 'member',
            'created_datetime' => now(),
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/users/{$newUser->id}");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonPath('data.email', 'testuser2@example.com');
    }

    /** @test */
    public function test_can_update_user()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/users/{$this->user->id}", [
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ]);
    }

    /** @test */
    public function test_can_delete_user()
    {
        $this->createAndAuthenticateUser();

        $userToDelete = User::create([
            'account_id' => $this->account->id,
            'user_name' => 'todelete',
            'email' => 'todelete@example.com',
            'password' => \Hash::make('password'),
            'user_status' => 'active',
            'user_type' => 'member',
            'created_datetime' => now(),
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/users/{$userToDelete->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('users', [
            'id' => $userToDelete->id,
        ]);
    }

    /** @test */
    public function test_can_bulk_update_users()
    {
        $this->createAndAuthenticateUser();

        $user1 = User::create(['account_id' => $this->account->id, 'user_name' => 'user1', 'email' => 'user1@example.com', 'password' => \Hash::make('pw'), 'user_status' => 'active', 'user_type' => 'member', 'created_datetime' => now()]);
        $user2 = User::create(['account_id' => $this->account->id, 'user_name' => 'user2', 'email' => 'user2@example.com', 'password' => \Hash::make('pw'), 'user_status' => 'active', 'user_type' => 'member', 'created_datetime' => now()]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/users", [
            'users' => [
                [
                    'user_id' => $user1->id,
                    'user_status' => 'closed',
                ],
                [
                    'user_id' => $user2->id,
                    'user_status' => 'closed',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('users', ['id' => $user1->id, 'user_status' => 'closed']);
        $this->assertDatabaseHas('users', ['id' => $user2->id, 'user_status' => 'closed']);
    }

    /** @test */
    public function test_can_list_contacts()
    {
        $this->createAndAuthenticateUser();

        Contact::create([
            'user_id' => $this->user->id,
            'contact_name' => 'John Contact',
            'email' => 'contact@example.com',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/users/{$this->user->id}/contacts");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
    }

    /** @test */
    public function test_can_import_contacts()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/users/{$this->user->id}/contacts", [
            'contacts' => [
                [
                    'contact_name' => 'Contact 1',
                    'email' => 'contact1@example.com',
                ],
                [
                    'contact_name' => 'Contact 2',
                    'email' => 'contact2@example.com',
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('contacts', ['user_id' => $this->user->id, 'email' => 'contact1@example.com']);
        $this->assertDatabaseHas('contacts', ['user_id' => $this->user->id, 'email' => 'contact2@example.com']);
    }

    /** @test */
    public function test_can_replace_all_contacts()
    {
        $this->createAndAuthenticateUser();

        // Create existing contacts
        Contact::create(['user_id' => $this->user->id, 'contact_name' => 'Old Contact', 'email' => 'old@example.com']);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/users/{$this->user->id}/contacts", [
            'contacts' => [
                [
                    'contact_name' => 'New Contact',
                    'email' => 'new@example.com',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseMissing('contacts', ['email' => 'old@example.com']);
        $this->assertDatabaseHas('contacts', ['email' => 'new@example.com']);
    }

    /** @test */
    public function test_can_delete_all_contacts()
    {
        $this->createAndAuthenticateUser();

        Contact::create(['user_id' => $this->user->id, 'contact_name' => 'Contact 1', 'email' => 'contact1@example.com']);
        Contact::create(['user_id' => $this->user->id, 'contact_name' => 'Contact 2', 'email' => 'contact2@example.com']);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/users/{$this->user->id}/contacts");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('contacts', ['user_id' => $this->user->id]);
    }

    /** @test */
    public function test_can_get_user_profile()
    {
        $this->createAndAuthenticateUser();

        UserProfile::create([
            'user_id' => $this->user->id,
            'company_name' => 'Test Company',
            'job_title' => 'Developer',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/users/{$this->user->id}/profile");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_update_user_profile()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/users/{$this->user->id}/profile", [
            'company_name' => 'Updated Company',
            'job_title' => 'Senior Developer',
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $this->user->id,
            'company_name' => 'Updated Company',
        ]);
    }

    /** @test */
    public function test_can_upload_profile_image()
    {
        Storage::fake('profile_images');
        $this->createAndAuthenticateUser();

        $file = UploadedFile::fake()->image('avatar.jpg', 300, 300);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/users/{$this->user->id}/profile/image", [
            'image' => $file,
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function test_can_get_user_settings()
    {
        $this->createAndAuthenticateUser();

        UserSetting::create([
            'user_id' => $this->user->id,
            'language_code' => 'en',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/users/{$this->user->id}/settings");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_update_user_settings()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/users/{$this->user->id}/settings", [
            'language_code' => 'es',
            'timezone' => 'America/New_York',
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('user_settings', [
            'user_id' => $this->user->id,
            'language_code' => 'es',
        ]);
    }

    /** @test */
    public function test_validates_email_format_on_user_create()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/users", [
            'user_name' => 'newuser',
            'email' => 'invalid-email',
            'user_status' => 'active',
            'user_type' => 'member',
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['email']);
    }

    /** @test */
    public function test_can_filter_users_by_status()
    {
        $this->createAndAuthenticateUser();

        User::create(['account_id' => $this->account->id, 'user_name' => 'active1', 'email' => 'active1@example.com', 'password' => \Hash::make('pw'), 'user_status' => 'active', 'user_type' => 'member', 'created_datetime' => now()]);
        User::create(['account_id' => $this->account->id, 'user_name' => 'closed1', 'email' => 'closed1@example.com', 'password' => \Hash::make('pw'), 'user_status' => 'closed', 'user_type' => 'member', 'created_datetime' => now()]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/users?status=active");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
        $this->assertGreaterThan(0, $response->json('pagination.total'));
    }
}
