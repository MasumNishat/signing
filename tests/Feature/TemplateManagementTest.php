<?php

namespace Tests\Feature;

use App\Models\Template;
use App\Models\EnvelopeDocument;
use App\Models\EnvelopeRecipient;
use App\Models\FavoriteTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TemplateManagementTest extends ApiTestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_can_list_templates()
    {
        $this->createAndAuthenticateUser();

        Template::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'template_name' => 'Test Template',
            'status' => 'active',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/templates");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
    }

    /** @test */
    public function test_can_create_template()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/templates", [
            'template_name' => 'New Template',
            'description' => 'A template for contracts',
            'status' => 'active',
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('templates', [
            'account_id' => $this->account->id,
            'template_name' => 'New Template',
        ]);
    }

    /** @test */
    public function test_can_get_specific_template()
    {
        $this->createAndAuthenticateUser();

        $template = Template::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'template_name' => 'Contract Template',
            'status' => 'active',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/templates/{$template->template_id}");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonPath('data.template_name', 'Contract Template');
    }

    /** @test */
    public function test_can_update_template()
    {
        $this->createAndAuthenticateUser();

        $template = Template::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'template_name' => 'Original',
            'status' => 'active',
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/templates/{$template->template_id}", [
            'template_name' => 'Updated Template',
            'description' => 'Updated description',
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('templates', [
            'id' => $template->id,
            'template_name' => 'Updated Template',
        ]);
    }

    /** @test */
    public function test_can_delete_template()
    {
        $this->createAndAuthenticateUser();

        $template = Template::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'template_name' => 'To Delete',
            'status' => 'active',
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/templates/{$template->template_id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('templates', [
            'id' => $template->id,
        ]);
    }

    /** @test */
    public function test_can_create_envelope_from_template()
    {
        $this->createAndAuthenticateUser();

        $template = Template::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'template_name' => 'Contract Template',
            'status' => 'active',
        ]);

        // Add template document
        EnvelopeDocument::create([
            'template_id' => $template->id,
            'name' => 'contract.pdf',
            'file_extension' => 'pdf',
            'order' => 1,
        ]);

        // Add template recipient
        EnvelopeRecipient::create([
            'template_id' => $template->id,
            'recipient_type' => 'signer',
            'routing_order' => 1,
            'role_name' => 'Client',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/templates/{$template->template_id}/create_envelope", [
            'status' => 'draft',
            'recipients' => [
                [
                    'role_name' => 'Client',
                    'email' => 'client@example.com',
                    'name' => 'John Client',
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('envelopes', [
            'account_id' => $this->account->id,
        ]);
    }

    /** @test */
    public function test_can_mark_template_as_favorite()
    {
        $this->createAndAuthenticateUser();

        $template = Template::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'template_name' => 'Favorite Template',
            'status' => 'active',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/templates/{$template->template_id}/favorite");

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('favorite_templates', [
            'user_id' => $this->user->id,
            'template_id' => $template->id,
        ]);
    }

    /** @test */
    public function test_can_unmark_template_as_favorite()
    {
        $this->createAndAuthenticateUser();

        $template = Template::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'template_name' => 'Favorite Template',
            'status' => 'active',
        ]);

        FavoriteTemplate::create([
            'user_id' => $this->user->id,
            'template_id' => $template->id,
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/templates/{$template->template_id}/favorite");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('favorite_templates', [
            'user_id' => $this->user->id,
            'template_id' => $template->id,
        ]);
    }

    /** @test */
    public function test_can_list_favorite_templates()
    {
        $this->createAndAuthenticateUser();

        $template = Template::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'template_name' => 'Favorite',
            'status' => 'active',
        ]);

        FavoriteTemplate::create([
            'user_id' => $this->user->id,
            'template_id' => $template->id,
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/templates?favorites_only=true");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
        $response->assertJsonPath('pagination.total', 1);
    }

    /** @test */
    public function test_can_share_template_with_users()
    {
        $this->createAndAuthenticateUser();

        $template = Template::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'template_name' => 'Shared Template',
            'status' => 'active',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/templates/{$template->template_id}/share", [
            'user_ids' => [$this->user->id],
            'permission_level' => 'view',
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('shared_access', [
            'item_type' => 'template',
            'item_id' => $template->id,
        ]);
    }

    /** @test */
    public function test_can_filter_templates_by_status()
    {
        $this->createAndAuthenticateUser();

        Template::create(['account_id' => $this->account->id, 'created_by_user_id' => $this->user->id, 'template_name' => 'Active', 'status' => 'active']);
        Template::create(['account_id' => $this->account->id, 'created_by_user_id' => $this->user->id, 'template_name' => 'Inactive', 'status' => 'inactive']);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/templates?status=active");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
        $response->assertJsonPath('pagination.total', 1);
    }

    /** @test */
    public function test_can_search_templates_by_name()
    {
        $this->createAndAuthenticateUser();

        Template::create(['account_id' => $this->account->id, 'created_by_user_id' => $this->user->id, 'template_name' => 'Contract Template', 'status' => 'active']);
        Template::create(['account_id' => $this->account->id, 'created_by_user_id' => $this->user->id, 'template_name' => 'NDA Template', 'status' => 'active']);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/templates?search=Contract");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
        $response->assertJsonPath('pagination.total', 1);
    }

    /** @test */
    public function test_validates_template_name_on_create()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/templates", [
            'status' => 'active',
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['template_name']);
    }

    /** @test */
    public function test_supports_template_versioning()
    {
        $this->createAndAuthenticateUser();

        $template = Template::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'template_name' => 'Versioned Template',
            'status' => 'active',
            'version' => 1,
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/templates/{$template->template_id}/version", [
            'version_notes' => 'Updated for new requirements',
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('templates', [
            'account_id' => $this->account->id,
            'template_name' => 'Versioned Template',
            'version' => 2,
        ]);
    }
}
