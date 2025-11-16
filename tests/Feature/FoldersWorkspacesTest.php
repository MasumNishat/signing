<?php

namespace Tests\Feature;

use App\Models\Folder;
use App\Models\Workspace;
use App\Models\WorkspaceFolder;
use App\Models\WorkspaceFile;
use App\Models\Envelope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FoldersWorkspacesTest extends ApiTestCase
{
    use RefreshDatabase;

    // ========== Folders Tests ==========

    /** @test */
    public function test_can_list_folders()
    {
        $this->createAndAuthenticateUser();

        Folder::create([
            'account_id' => $this->account->id,
            'owner_user_id' => $this->user->id,
            'folder_name' => 'My Folder',
            'folder_type' => 'custom',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/folders");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_create_folder()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/folders", [
            'folder_name' => 'Projects',
            'folder_type' => 'custom',
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('folders', [
            'account_id' => $this->account->id,
            'folder_name' => 'Projects',
        ]);
    }

    /** @test */
    public function test_can_get_specific_folder()
    {
        $this->createAndAuthenticateUser();

        $folder = Folder::create([
            'account_id' => $this->account->id,
            'owner_user_id' => $this->user->id,
            'folder_name' => 'Test Folder',
            'folder_type' => 'custom',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/folders/{$folder->folder_id}");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonPath('data.folder_name', 'Test Folder');
    }

    /** @test */
    public function test_can_update_folder()
    {
        $this->createAndAuthenticateUser();

        $folder = Folder::create([
            'account_id' => $this->account->id,
            'owner_user_id' => $this->user->id,
            'folder_name' => 'Original',
            'folder_type' => 'custom',
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/folders/{$folder->folder_id}", [
            'folder_name' => 'Updated Folder',
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('folders', [
            'id' => $folder->id,
            'folder_name' => 'Updated Folder',
        ]);
    }

    /** @test */
    public function test_supports_hierarchical_folders()
    {
        $this->createAndAuthenticateUser();

        $parentFolder = Folder::create([
            'account_id' => $this->account->id,
            'owner_user_id' => $this->user->id,
            'folder_name' => 'Parent',
            'folder_type' => 'custom',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/folders", [
            'folder_name' => 'Child Folder',
            'parent_folder_id' => $parentFolder->folder_id,
            'folder_type' => 'custom',
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('folders', [
            'folder_name' => 'Child Folder',
            'parent_folder_id' => $parentFolder->id,
        ]);
    }

    /** @test */
    public function test_can_move_envelope_to_folder()
    {
        $this->createAndAuthenticateUser();

        $folder = Folder::create([
            'account_id' => $this->account->id,
            'owner_user_id' => $this->user->id,
            'folder_name' => 'Projects',
            'folder_type' => 'custom',
        ]);

        $envelope = Envelope::create([
            'account_id' => $this->account->id,
            'sender_user_id' => $this->user->id,
            'subject' => 'Test Envelope',
            'status' => 'draft',
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/folders/{$folder->folder_id}/envelopes", [
            'envelope_ids' => [$envelope->envelope_id],
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('envelope_folders', [
            'envelope_id' => $envelope->id,
            'folder_id' => $folder->id,
        ]);
    }

    /** @test */
    public function test_supports_system_folders()
    {
        $this->createAndAuthenticateUser();

        $systemFolders = ['inbox', 'sent', 'draft', 'trash', 'recyclebin'];

        foreach ($systemFolders as $type) {
            Folder::create([
                'account_id' => $this->account->id,
                'owner_user_id' => $this->user->id,
                'folder_name' => ucfirst($type),
                'folder_type' => $type,
            ]);
        }

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/folders");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_prevents_deletion_of_system_folders()
    {
        $this->createAndAuthenticateUser();

        $systemFolder = Folder::create([
            'account_id' => $this->account->id,
            'owner_user_id' => $this->user->id,
            'folder_name' => 'Inbox',
            'folder_type' => 'inbox',
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/folders/{$systemFolder->folder_id}");

        $response->assertStatus(400);
        $this->assertErrorResponse();
    }

    // ========== Workspaces Tests ==========

    /** @test */
    public function test_can_list_workspaces()
    {
        $this->createAndAuthenticateUser();

        Workspace::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'workspace_name' => 'My Workspace',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/workspaces");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
    }

    /** @test */
    public function test_can_create_workspace()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/workspaces", [
            'workspace_name' => 'Project Alpha',
            'description' => 'Workspace for Project Alpha',
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('workspaces', [
            'account_id' => $this->account->id,
            'workspace_name' => 'Project Alpha',
        ]);
    }

    /** @test */
    public function test_can_get_specific_workspace()
    {
        $this->createAndAuthenticateUser();

        $workspace = Workspace::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'workspace_name' => 'Test Workspace',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/workspaces/{$workspace->workspace_id}");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonPath('data.workspace_name', 'Test Workspace');
    }

    /** @test */
    public function test_can_update_workspace()
    {
        $this->createAndAuthenticateUser();

        $workspace = Workspace::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'workspace_name' => 'Original',
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/workspaces/{$workspace->workspace_id}", [
            'workspace_name' => 'Updated Workspace',
            'description' => 'Updated description',
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('workspaces', [
            'id' => $workspace->id,
            'workspace_name' => 'Updated Workspace',
        ]);
    }

    /** @test */
    public function test_can_delete_workspace()
    {
        $this->createAndAuthenticateUser();

        $workspace = Workspace::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'workspace_name' => 'To Delete',
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/workspaces/{$workspace->workspace_id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('workspaces', [
            'id' => $workspace->id,
        ]);
    }

    /** @test */
    public function test_can_create_workspace_folder()
    {
        $this->createAndAuthenticateUser();

        $workspace = Workspace::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'workspace_name' => 'Test Workspace',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/workspaces/{$workspace->workspace_id}/folders", [
            'folder_name' => 'Documents',
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('workspace_folders', [
            'workspace_id' => $workspace->id,
            'folder_name' => 'Documents',
        ]);
    }

    /** @test */
    public function test_can_list_workspace_folders()
    {
        $this->createAndAuthenticateUser();

        $workspace = Workspace::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'workspace_name' => 'Test Workspace',
        ]);

        WorkspaceFolder::create([
            'workspace_id' => $workspace->id,
            'folder_name' => 'Folder 1',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/workspaces/{$workspace->workspace_id}/folders");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_upload_file_to_workspace()
    {
        Storage::fake('workspaces');
        $this->createAndAuthenticateUser();

        $workspace = Workspace::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'workspace_name' => 'Test Workspace',
        ]);

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/workspaces/{$workspace->workspace_id}/files", [
            'file' => $file,
            'file_name' => 'Test Document',
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('workspace_files', [
            'workspace_id' => $workspace->id,
            'file_name' => 'Test Document',
        ]);
    }

    /** @test */
    public function test_can_list_workspace_files()
    {
        $this->createAndAuthenticateUser();

        $workspace = Workspace::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'workspace_name' => 'Test Workspace',
        ]);

        WorkspaceFile::create([
            'workspace_id' => $workspace->id,
            'file_name' => 'document.pdf',
            'file_path' => 'path/to/document.pdf',
            'file_type' => 'pdf',
            'file_size' => 1024,
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/workspaces/{$workspace->workspace_id}/files");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
    }

    /** @test */
    public function test_can_delete_workspace_file()
    {
        $this->createAndAuthenticateUser();

        $workspace = Workspace::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'workspace_name' => 'Test Workspace',
        ]);

        $file = WorkspaceFile::create([
            'workspace_id' => $workspace->id,
            'file_name' => 'to_delete.pdf',
            'file_path' => 'path/to/file.pdf',
            'file_type' => 'pdf',
            'file_size' => 1024,
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/workspaces/{$workspace->workspace_id}/files/{$file->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('workspace_files', [
            'id' => $file->id,
        ]);
    }

    /** @test */
    public function test_validates_workspace_name_on_create()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/workspaces", [
            'description' => 'Test workspace',
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['workspace_name']);
    }

    /** @test */
    public function test_validates_folder_name_on_create()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/folders", [
            'folder_type' => 'custom',
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['folder_name']);
    }

    /** @test */
    public function test_validates_file_upload_size()
    {
        Storage::fake('workspaces');
        $this->createAndAuthenticateUser();

        $workspace = Workspace::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'workspace_name' => 'Test Workspace',
        ]);

        $file = UploadedFile::fake()->create('huge_file.pdf', 51 * 1024); // 51MB

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/workspaces/{$workspace->workspace_id}/files", [
            'file' => $file,
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['file']);
    }

    /** @test */
    public function test_workspace_folders_support_hierarchy()
    {
        $this->createAndAuthenticateUser();

        $workspace = Workspace::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'workspace_name' => 'Test Workspace',
        ]);

        $parentFolder = WorkspaceFolder::create([
            'workspace_id' => $workspace->id,
            'folder_name' => 'Parent',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/workspaces/{$workspace->workspace_id}/folders", [
            'folder_name' => 'Child',
            'parent_folder_id' => $parentFolder->id,
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('workspace_folders', [
            'folder_name' => 'Child',
            'parent_folder_id' => $parentFolder->id,
        ]);
    }

    /** @test */
    public function test_can_move_files_between_folders()
    {
        $this->createAndAuthenticateUser();

        $workspace = Workspace::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'workspace_name' => 'Test Workspace',
        ]);

        $folder1 = WorkspaceFolder::create(['workspace_id' => $workspace->id, 'folder_name' => 'Folder 1']);
        $folder2 = WorkspaceFolder::create(['workspace_id' => $workspace->id, 'folder_name' => 'Folder 2']);

        $file = WorkspaceFile::create([
            'workspace_id' => $workspace->id,
            'folder_id' => $folder1->id,
            'file_name' => 'document.pdf',
            'file_path' => 'path/to/file.pdf',
            'file_type' => 'pdf',
            'file_size' => 1024,
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/workspaces/{$workspace->workspace_id}/files/{$file->id}/move", [
            'folder_id' => $folder2->id,
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('workspace_files', [
            'id' => $file->id,
            'folder_id' => $folder2->id,
        ]);
    }

    /** @test */
    public function test_supports_different_file_types()
    {
        $this->createAndAuthenticateUser();

        $workspace = Workspace::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'workspace_name' => 'Test Workspace',
        ]);

        $fileTypes = ['pdf', 'docx', 'xlsx', 'jpg', 'png'];

        foreach ($fileTypes as $type) {
            WorkspaceFile::create([
                'workspace_id' => $workspace->id,
                'file_name' => "document.{$type}",
                'file_path' => "path/to/file.{$type}",
                'file_type' => $type,
                'file_size' => 1024,
            ]);
        }

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/workspaces/{$workspace->workspace_id}/files");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
        $response->assertJsonPath('pagination.total', 5);
    }
}
