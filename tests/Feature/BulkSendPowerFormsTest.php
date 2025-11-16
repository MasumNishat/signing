<?php

namespace Tests\Feature;

use App\Models\BulkSendBatch;
use App\Models\BulkSendList;
use App\Models\BulkSendRecipient;
use App\Models\PowerForm;
use App\Models\PowerFormSubmission;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BulkSendPowerFormsTest extends ApiTestCase
{
    use RefreshDatabase;

    // ========== Bulk Send Tests ==========

    /** @test */
    public function test_can_list_bulk_send_batches()
    {
        $this->createAndAuthenticateUser();

        BulkSendBatch::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'batch_name' => 'Test Batch',
            'batch_status' => 'queued',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/bulk_send_batch");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
    }

    /** @test */
    public function test_can_create_bulk_send_batch()
    {
        $this->createAndAuthenticateUser();

        $template = Template::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'template_name' => 'Test Template',
            'status' => 'active',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/bulk_send_batch", [
            'batch_name' => 'Q1 Contracts',
            'template_id' => $template->template_id,
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('bulk_send_batches', [
            'account_id' => $this->account->id,
            'batch_name' => 'Q1 Contracts',
        ]);
    }

    /** @test */
    public function test_can_get_specific_bulk_send_batch()
    {
        $this->createAndAuthenticateUser();

        $batch = BulkSendBatch::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'batch_name' => 'Test Batch',
            'batch_status' => 'queued',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/bulk_send_batch/{$batch->bulk_send_batch_id}");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonPath('data.batch_name', 'Test Batch');
    }

    /** @test */
    public function test_can_get_bulk_send_batch_status()
    {
        $this->createAndAuthenticateUser();

        $batch = BulkSendBatch::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'batch_name' => 'Test Batch',
            'batch_status' => 'processing',
            'total_recipients' => 100,
            'sent_count' => 50,
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/bulk_send_batch/{$batch->bulk_send_batch_id}/status");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonPath('data.batch_status', 'processing');
        $response->assertJsonPath('data.sent_count', 50);
    }

    /** @test */
    public function test_can_delete_bulk_send_batch()
    {
        $this->createAndAuthenticateUser();

        $batch = BulkSendBatch::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'batch_name' => 'To Delete',
            'batch_status' => 'queued',
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/bulk_send_batch/{$batch->bulk_send_batch_id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('bulk_send_batches', [
            'id' => $batch->id,
        ]);
    }

    /** @test */
    public function test_can_create_bulk_send_list()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/bulk_send_lists", [
            'list_name' => 'Customer List',
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('bulk_send_lists', [
            'account_id' => $this->account->id,
            'list_name' => 'Customer List',
        ]);
    }

    /** @test */
    public function test_can_list_bulk_send_lists()
    {
        $this->createAndAuthenticateUser();

        BulkSendList::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'list_name' => 'List 1',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/bulk_send_lists");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
    }

    /** @test */
    public function test_can_get_specific_bulk_send_list()
    {
        $this->createAndAuthenticateUser();

        $list = BulkSendList::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'list_name' => 'Test List',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/bulk_send_lists/{$list->bulk_send_list_id}");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonPath('data.list_name', 'Test List');
    }

    /** @test */
    public function test_can_update_bulk_send_list()
    {
        $this->createAndAuthenticateUser();

        $list = BulkSendList::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'list_name' => 'Original',
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/bulk_send_lists/{$list->bulk_send_list_id}", [
            'list_name' => 'Updated List',
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('bulk_send_lists', [
            'id' => $list->id,
            'list_name' => 'Updated List',
        ]);
    }

    /** @test */
    public function test_can_delete_bulk_send_list()
    {
        $this->createAndAuthenticateUser();

        $list = BulkSendList::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'list_name' => 'To Delete',
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/bulk_send_lists/{$list->bulk_send_list_id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('bulk_send_lists', [
            'id' => $list->id,
        ]);
    }

    /** @test */
    public function test_can_add_recipients_to_bulk_send_list()
    {
        $this->createAndAuthenticateUser();

        $list = BulkSendList::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'list_name' => 'Test List',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/bulk_send_lists/{$list->bulk_send_list_id}/recipients", [
            'recipients' => [
                [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ],
                [
                    'name' => 'Jane Smith',
                    'email' => 'jane@example.com',
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('bulk_send_recipients', ['list_id' => $list->id, 'email' => 'john@example.com']);
        $this->assertDatabaseHas('bulk_send_recipients', ['list_id' => $list->id, 'email' => 'jane@example.com']);
    }

    /** @test */
    public function test_can_get_bulk_send_list_recipients()
    {
        $this->createAndAuthenticateUser();

        $list = BulkSendList::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'list_name' => 'Test List',
        ]);

        BulkSendRecipient::create([
            'list_id' => $list->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/bulk_send_lists/{$list->bulk_send_list_id}/recipients");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
    }

    /** @test */
    public function test_can_update_bulk_send_batch_status()
    {
        $this->createAndAuthenticateUser();

        $batch = BulkSendBatch::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'batch_name' => 'Test Batch',
            'batch_status' => 'queued',
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/bulk_send_batch/{$batch->bulk_send_batch_id}", [
            'action' => 'pause',
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('bulk_send_batches', [
            'id' => $batch->id,
            'batch_status' => 'paused',
        ]);
    }

    /** @test */
    public function test_can_resume_paused_bulk_send_batch()
    {
        $this->createAndAuthenticateUser();

        $batch = BulkSendBatch::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'batch_name' => 'Test Batch',
            'batch_status' => 'paused',
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/bulk_send_batch/{$batch->bulk_send_batch_id}", [
            'action' => 'resume',
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('bulk_send_batches', [
            'id' => $batch->id,
            'batch_status' => 'processing',
        ]);
    }

    /** @test */
    public function test_validates_bulk_send_batch_name()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/bulk_send_batch", []);

        $response->assertStatus(422);
        $this->assertValidationErrors(['batch_name']);
    }

    // ========== PowerForms Tests ==========

    /** @test */
    public function test_can_list_powerforms()
    {
        $this->createAndAuthenticateUser();

        PowerForm::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'powerform_name' => 'Test PowerForm',
            'status' => 'active',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/powerforms");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
    }

    /** @test */
    public function test_can_create_powerform()
    {
        $this->createAndAuthenticateUser();

        $template = Template::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'template_name' => 'Test Template',
            'status' => 'active',
        ]);

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/powerforms", [
            'powerform_name' => 'Contact Form',
            'template_id' => $template->template_id,
            'status' => 'active',
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('powerforms', [
            'account_id' => $this->account->id,
            'powerform_name' => 'Contact Form',
        ]);
    }

    /** @test */
    public function test_can_get_specific_powerform()
    {
        $this->createAndAuthenticateUser();

        $powerform = PowerForm::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'powerform_name' => 'Test PowerForm',
            'status' => 'active',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/powerforms/{$powerform->powerform_id}");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonPath('data.powerform_name', 'Test PowerForm');
    }

    /** @test */
    public function test_can_update_powerform()
    {
        $this->createAndAuthenticateUser();

        $powerform = PowerForm::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'powerform_name' => 'Original',
            'status' => 'active',
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/powerforms/{$powerform->powerform_id}", [
            'powerform_name' => 'Updated PowerForm',
            'status' => 'inactive',
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('powerforms', [
            'id' => $powerform->id,
            'powerform_name' => 'Updated PowerForm',
            'status' => 'inactive',
        ]);
    }

    /** @test */
    public function test_can_delete_powerform()
    {
        $this->createAndAuthenticateUser();

        $powerform = PowerForm::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'powerform_name' => 'To Delete',
            'status' => 'active',
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/powerforms/{$powerform->powerform_id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('powerforms', [
            'id' => $powerform->id,
        ]);
    }

    /** @test */
    public function test_can_submit_to_powerform()
    {
        $this->createAndAuthenticateUser();

        $powerform = PowerForm::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'powerform_name' => 'Test PowerForm',
            'status' => 'active',
        ]);

        $response = $this->json('POST', "/api/v2.1/public/powerforms/{$powerform->powerform_id}/submit", [
            'recipient_name' => 'John Doe',
            'recipient_email' => 'john@example.com',
            'form_data' => [
                'field1' => 'value1',
                'field2' => 'value2',
            ],
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('powerform_submissions', [
            'powerform_id' => $powerform->id,
            'recipient_email' => 'john@example.com',
        ]);
    }

    /** @test */
    public function test_can_list_powerform_submissions()
    {
        $this->createAndAuthenticateUser();

        $powerform = PowerForm::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'powerform_name' => 'Test PowerForm',
            'status' => 'active',
        ]);

        PowerFormSubmission::create([
            'powerform_id' => $powerform->id,
            'recipient_email' => 'john@example.com',
            'recipient_name' => 'John Doe',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/powerforms/{$powerform->powerform_id}/submissions");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
    }

    /** @test */
    public function test_validates_powerform_name_on_create()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/powerforms", [
            'status' => 'active',
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['powerform_name']);
    }

    /** @test */
    public function test_validates_recipient_email_on_submission()
    {
        $this->createAndAuthenticateUser();

        $powerform = PowerForm::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'powerform_name' => 'Test PowerForm',
            'status' => 'active',
        ]);

        $response = $this->json('POST', "/api/v2.1/public/powerforms/{$powerform->powerform_id}/submit", [
            'recipient_name' => 'John Doe',
            'recipient_email' => 'invalid-email',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function test_cannot_submit_to_inactive_powerform()
    {
        $this->createAndAuthenticateUser();

        $powerform = PowerForm::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'powerform_name' => 'Inactive PowerForm',
            'status' => 'inactive',
        ]);

        $response = $this->json('POST', "/api/v2.1/public/powerforms/{$powerform->powerform_id}/submit", [
            'recipient_name' => 'John Doe',
            'recipient_email' => 'john@example.com',
        ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function test_supports_different_powerform_statuses()
    {
        $this->createAndAuthenticateUser();

        $statuses = ['active', 'inactive', 'archived'];

        foreach ($statuses as $status) {
            PowerForm::create([
                'account_id' => $this->account->id,
                'created_by_user_id' => $this->user->id,
                'powerform_name' => ucfirst($status) . ' Form',
                'status' => $status,
            ]);
        }

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/powerforms");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
        $response->assertJsonPath('pagination.total', 3);
    }

    /** @test */
    public function test_can_filter_powerforms_by_status()
    {
        $this->createAndAuthenticateUser();

        PowerForm::create(['account_id' => $this->account->id, 'created_by_user_id' => $this->user->id, 'powerform_name' => 'Active', 'status' => 'active']);
        PowerForm::create(['account_id' => $this->account->id, 'created_by_user_id' => $this->user->id, 'powerform_name' => 'Inactive', 'status' => 'inactive']);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/powerforms?status=active");

        $response->assertStatus(200);
        $this->assertPaginatedResponse();
        $response->assertJsonPath('pagination.total', 1);
    }

    /** @test */
    public function test_tracks_powerform_submission_count()
    {
        $this->createAndAuthenticateUser();

        $powerform = PowerForm::create([
            'account_id' => $this->account->id,
            'created_by_user_id' => $this->user->id,
            'powerform_name' => 'Test PowerForm',
            'status' => 'active',
            'submission_count' => 0,
        ]);

        // Create submissions
        PowerFormSubmission::create(['powerform_id' => $powerform->id, 'recipient_email' => 'user1@example.com', 'recipient_name' => 'User 1']);
        PowerFormSubmission::create(['powerform_id' => $powerform->id, 'recipient_email' => 'user2@example.com', 'recipient_name' => 'User 2']);

        $powerform->increment('submission_count', 2);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/powerforms/{$powerform->powerform_id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.submission_count', 2);
    }
}
