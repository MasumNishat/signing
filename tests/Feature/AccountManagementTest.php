<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Plan;
use App\Models\AccountCustomField;
use App\Models\ConsumerDisclosure;
use App\Models\WatermarkConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AccountManagementTest extends ApiTestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_can_create_account()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts", [
            'account_name' => 'New Test Account',
            'plan_id' => $this->account->plan_id,
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('accounts', [
            'account_name' => 'New Test Account',
        ]);
    }

    /** @test */
    public function test_can_get_account()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $response->assertJsonPath('data.account_name', $this->account->account_name);
    }

    /** @test */
    public function test_can_delete_account()
    {
        $this->createAndAuthenticateUser();

        $newAccount = Account::create([
            'plan_id' => $this->account->plan_id,
            'account_id' => 'acc_delete_test',
            'account_name' => 'To Delete',
            'billing_period_envelopes_sent' => 0,
            'billing_period_envelopes_allowed' => 100,
            'created_date' => now(),
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$newAccount->account_id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('accounts', [
            'id' => $newAccount->id,
        ]);
    }

    /** @test */
    public function test_can_get_account_provisioning()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiGet("/api/v2.1/accounts/provisioning");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_create_account_custom_field()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/custom_fields", [
            'custom_fields' => [
                [
                    'field_id' => 'field1',
                    'name' => 'Department',
                    'show' => true,
                    'required' => false,
                    'field_type' => 'text',
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('account_custom_fields', [
            'account_id' => $this->account->id,
            'name' => 'Department',
        ]);
    }

    /** @test */
    public function test_can_list_account_custom_fields()
    {
        $this->createAndAuthenticateUser();

        AccountCustomField::create([
            'account_id' => $this->account->id,
            'name' => 'Department',
            'field_type' => 'text',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/custom_fields");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
        $this->assertNotEmpty($response->json('data'));
    }

    /** @test */
    public function test_can_update_account_custom_field()
    {
        $this->createAndAuthenticateUser();

        $field = AccountCustomField::create([
            'account_id' => $this->account->id,
            'name' => 'Original Name',
            'field_type' => 'text',
        ]);

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/custom_fields", [
            'custom_fields' => [
                [
                    'field_id' => $field->field_id,
                    'name' => 'Updated Name',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('account_custom_fields', [
            'id' => $field->id,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function test_can_delete_account_custom_field()
    {
        $this->createAndAuthenticateUser();

        AccountCustomField::create([
            'account_id' => $this->account->id,
            'name' => 'To Delete',
            'field_type' => 'text',
        ]);

        $response = $this->apiDelete("/api/v2.1/accounts/{$this->account->account_id}/custom_fields");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('account_custom_fields', [
            'account_id' => $this->account->id,
        ]);
    }

    /** @test */
    public function test_can_get_consumer_disclosure()
    {
        $this->createAndAuthenticateUser();

        ConsumerDisclosure::create([
            'account_id' => $this->account->id,
            'language_code' => 'en',
            'esign_text' => 'By clicking, you agree to sign electronically.',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/consumer_disclosure");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_update_consumer_disclosure()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/consumer_disclosure", [
            'language_code' => 'en',
            'esign_text' => 'Updated disclosure text.',
            'esign_accept' => true,
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('consumer_disclosures', [
            'account_id' => $this->account->id,
            'esign_text' => 'Updated disclosure text.',
        ]);
    }

    /** @test */
    public function test_can_get_watermark_configuration()
    {
        $this->createAndAuthenticateUser();

        WatermarkConfiguration::create([
            'account_id' => $this->account->id,
            'watermark_text' => 'CONFIDENTIAL',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/watermark");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_can_update_watermark_configuration()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/watermark", [
            'watermark_text' => 'DRAFT',
            'watermark_enabled' => true,
            'transparency' => 50,
        ]);

        $response->assertStatus(200);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('watermark_configurations', [
            'account_id' => $this->account->id,
            'watermark_text' => 'DRAFT',
        ]);
    }

    /** @test */
    public function test_can_get_watermark_preview()
    {
        $this->createAndAuthenticateUser();

        WatermarkConfiguration::create([
            'account_id' => $this->account->id,
            'watermark_text' => 'SAMPLE',
        ]);

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/watermark/preview");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }

    /** @test */
    public function test_supports_list_custom_fields()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPost("/api/v2.1/accounts/{$this->account->account_id}/custom_fields", [
            'custom_fields' => [
                [
                    'field_id' => 'list1',
                    'name' => 'Priority',
                    'field_type' => 'list',
                    'list_items' => ['Low', 'Medium', 'High'],
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertSuccessResponse();

        $this->assertDatabaseHas('account_custom_fields', [
            'account_id' => $this->account->id,
            'name' => 'Priority',
            'field_type' => 'list',
        ]);
    }

    /** @test */
    public function test_validates_watermark_transparency_range()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiPut("/api/v2.1/accounts/{$this->account->account_id}/watermark", [
            'watermark_text' => 'TEST',
            'transparency' => 150, // Invalid
        ]);

        $response->assertStatus(422);
        $this->assertValidationErrors(['transparency']);
    }

    /** @test */
    public function test_can_lookup_recipient_names_by_email()
    {
        $this->createAndAuthenticateUser();

        $response = $this->apiGet("/api/v2.1/accounts/{$this->account->account_id}/recipient_names?email={$this->user->email}");

        $response->assertStatus(200);
        $this->assertSuccessResponse();
    }
}
