<?php

namespace Tests\Feature;

use App\Models\SigningGroup;
use App\Models\UserGroup;
use App\Models\User;
use App\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GroupManagementTest extends ApiTestCase
{
    use RefreshDatabase;

    // ========== Signing Groups Tests ==========

    /** @test */
    public function test_can_list_signing_groups()
    {
        $this->createAndAuthenticateUser();

        SigningGroup::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'group_name' => 'Test Group',
            'group_type' => 'private',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/signing_groups");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
    }

    /** @test */
    public function test_can_create_signing_group()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/signing_groups", [
            'group_name' => 'Legal Team',
            'group_type' => 'private',
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('signing_groups', [
            'account_id' => $this->account->id,
            'group_name' => 'Legal Team',
        ]);
    }

    /** @test */
    public function test_can_get_specific_signing_group()
    {
        $this->createAndAuthenticateUser();

        $group = SigningGroup::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'group_name' => 'Test Group',
            'group_type' => 'private',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/signing_groups/{$group->group_id}");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonPath('data.group_name', 'Test Group');
    }

    /** @test */
    public function test_can_update_signing_group()
    {
        $this->createAndAuthenticateUser();

        $group = SigningGroup::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'group_name' => 'Original',
            'group_type' => 'private',
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/signing_groups/{$group->group_id}", [
            'group_name' => 'Updated Group',
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('signing_groups', [
            'id' => $group->id,
            'group_name' => 'Updated Group',
        ]);
    }

    /** @test */
    public function test_can_delete_signing_group()
    {
        $this->createAndAuthenticateUser();

        $group = SigningGroup::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'group_name' => 'To Delete',
            'group_type' => 'private',
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/signing_groups/{$group->group_id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('signing_groups', [
            'id' => $group->id,
        ]);
    }

    /** @test */
    public function test_can_add_users_to_signing_group()
    {
        $this->createAndAuthenticateUser();

        $group = SigningGroup::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'group_name' => 'Test Group',
            'group_type' => 'private',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/signing_groups/{$group->group_id}/users", [
            'users' => [
                [
                    'user_id' => $this->user->id,
                    'email' => $this->user->email,
                    'user_name' => $this->user->user_name,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('signing_group_users', [
            'signing_group_id' => $group->id,
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function test_can_get_signing_group_users()
    {
        $this->createAndAuthenticateUser();

        $group = SigningGroup::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'group_name' => 'Test Group',
            'group_type' => 'private',
        ]);

        $group->users()->attach($this->user->id, [
            'email' => $this->user->email,
            'user_name' => $this->user->user_name,
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/signing_groups/{$group->group_id}/users");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_remove_user_from_signing_group()
    {
        $this->createAndAuthenticateUser();

        $group = SigningGroup::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'group_name' => 'Test Group',
            'group_type' => 'private',
        ]);

        $group->users()->attach($this->user->id, [
            'email' => $this->user->email,
            'user_name' => $this->user->user_name,
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/signing_groups/{$group->group_id}/users/{$this->user->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('signing_group_users', [
            'signing_group_id' => $group->id,
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function test_supports_different_group_types()
    {
        $this->createAndAuthenticateUser();

        $types = ['public', 'private', 'shared'];

        foreach ($types as $type) {
            SigningGroup::create([
                'account_id' => $this->account->id,
                'created_by_user_id' => $this->user->id,
                'group_name' => ucfirst($type) . ' Group',
                'group_type' => $type,
            ]);
        }

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/signing_groups");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
        $response->assertJsonPath('pagination.total', 3);
    }

    /** @test */
    public function test_can_bulk_create_signing_groups()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/signing_groups", [
            'groups' => [
                [
                    'group_name' => 'Group 1',
                    'group_type' => 'private',
                ],
                [
                    'group_name' => 'Group 2',
                    'group_type' => 'public',
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('signing_groups', ['group_name' => 'Group 1']);
        $this->assertDatabaseHas('signing_groups', ['group_name' => 'Group 2']);
    }

    // ========== User Groups Tests ==========

    /** @test */
    public function test_can_list_user_groups()
    {
        $this->createAndAuthenticateUser();

        UserGroup::create([
            'account_id' => $this->account->id,
            'group_name' => 'Administrators',
            'group_type' => 'admin_group',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/groups");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
    }

    /** @test */
    public function test_can_create_user_group()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/groups", [
            'group_name' => 'Managers',
            'group_type' => 'custom_group',
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('user_groups', [
            'account_id' => $this->account->id,
            'group_name' => 'Managers',
        ]);
    }

    /** @test */
    public function test_can_get_specific_user_group()
    {
        $this->createAndAuthenticateUser();

        $group = UserGroup::create([
            'account_id' => $this->account->id,
            'group_name' => 'Test Group',
            'group_type' => 'custom_group',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/groups/{$group->group_id}");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonPath('data.group_name', 'Test Group');
    }

    /** @test */
    public function test_can_update_user_group()
    {
        $this->createAndAuthenticateUser();

        $group = UserGroup::create([
            'account_id' => $this->account->id,
            'group_name' => 'Original',
            'group_type' => 'custom_group',
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/groups/{$group->group_id}", [
            'group_name' => 'Updated Group',
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('user_groups', [
            'id' => $group->id,
            'group_name' => 'Updated Group',
        ]);
    }

    /** @test */
    public function test_can_delete_user_group()
    {
        $this->createAndAuthenticateUser();

        $group = UserGroup::create([
            'account_id' => $this->account->id,
            'group_name' => 'To Delete',
            'group_type' => 'custom_group',
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/groups/{$group->group_id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('user_groups', [
            'id' => $group->id,
        ]);
    }

    /** @test */
    public function test_can_add_users_to_user_group()
    {
        $this->createAndAuthenticateUser();

        $group = UserGroup::create([
            'account_id' => $this->account->id,
            'group_name' => 'Test Group',
            'group_type' => 'custom_group',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/groups/{$group->group_id}/users", [
            'users' => [
                [
                    'user_id' => $this->user->id,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('user_group_users', [
            'user_group_id' => $group->id,
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function test_can_get_user_group_users()
    {
        $this->createAndAuthenticateUser();

        $group = UserGroup::create([
            'account_id' => $this->account->id,
            'group_name' => 'Test Group',
            'group_type' => 'custom_group',
        ]);

        $group->users()->attach($this->user->id);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/groups/{$group->group_id}/users");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_remove_user_from_user_group()
    {
        $this->createAndAuthenticateUser();

        $group = UserGroup::create([
            'account_id' => $this->account->id,
            'group_name' => 'Test Group',
            'group_type' => 'custom_group',
        ]);

        $group->users()->attach($this->user->id);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/groups/{$group->group_id}/users/{$this->user->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('user_group_users', [
            'user_group_id' => $group->id,
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function test_can_add_brands_to_user_group()
    {
        $this->createAndAuthenticateUser();

        $group = UserGroup::create([
            'account_id' => $this->account->id,
            'group_name' => 'Test Group',
            'group_type' => 'custom_group',
        ]);

        $brand = Brand::create([
            'account_id' => $this->account->id,
            'brand_name' => 'Test Brand',
            'is_default' => false,
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/groups/{$group->group_id}/brands", [
            'brands' => [
                [
                    'brand_id' => $brand->brand_id,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('user_group_brands', [
            'user_group_id' => $group->id,
            'brand_id' => $brand->id,
        ]);
    }

    /** @test */
    public function test_can_get_user_group_brands()
    {
        $this->createAndAuthenticateUser();

        $group = UserGroup::create([
            'account_id' => $this->account->id,
            'group_name' => 'Test Group',
            'group_type' => 'custom_group',
        ]);

        $brand = Brand::create([
            'account_id' => $this->account->id,
            'brand_name' => 'Test Brand',
            'is_default' => false,
        ]);

        $group->brands()->attach($brand->id);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/groups/{$group->group_id}/brands");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_remove_brand_from_user_group()
    {
        $this->createAndAuthenticateUser();

        $group = UserGroup::create([
            'account_id' => $this->account->id,
            'group_name' => 'Test Group',
            'group_type' => 'custom_group',
        ]);

        $brand = Brand::create([
            'account_id' => $this->account->id,
            'brand_name' => 'Test Brand',
            'is_default' => false,
        ]);

        $group->brands()->attach($brand->id);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/groups/{$group->group_id}/brands/{$brand->brand_id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('user_group_brands', [
            'user_group_id' => $group->id,
            'brand_id' => $brand->id,
        ]);
    }

    /** @test */
    public function test_validates_signing_group_name_on_create()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/signing_groups", [
            'group_type' => 'private',
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['group_name']);
    }

    /** @test */
    public function test_validates_user_group_name_on_create()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/groups", [
            'group_type' => 'custom_group',
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['group_name']);
    }
}
