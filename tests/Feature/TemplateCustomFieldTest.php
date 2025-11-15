<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Template;
use App\Models\EnvelopeCustomField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Template Custom Fields Integration Tests
 *
 * Tests all 4 template custom field endpoints:
 * - GET /templates/{id}/custom_fields
 * - POST /templates/{id}/custom_fields
 * - PUT /templates/{id}/custom_fields
 * - DELETE /templates/{id}/custom_fields
 */
class TemplateCustomFieldTest extends TestCase
{
    use RefreshDatabase;

    private Account $account;
    private User $user;
    private Template $template;

    protected function setUp(): void
    {
        parent::setUp();

        $this->account = Account::factory()->create();
        $this->user = User::factory()->create([
            'account_id' => $this->account->id,
        ]);

        $this->template = Template::create([
            'account_id' => $this->account->id,
            'template_id' => 'tpl-' . uniqid(),
            'name' => 'Test Template',
            'created_by_user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user);
    }

    
    public function test_can_get_template_custom_fields()
    {
        // Create text field
        EnvelopeCustomField::create([
            'template_id' => $this->template->id,
            'field_id' => 'field-text-1',
            'name' => 'Department',
            'value' => 'Sales',
            'field_type' => 'text',
            'show' => true,
            'required' => false,
        ]);

        // Create list field
        EnvelopeCustomField::create([
            'template_id' => $this->template->id,
            'field_id' => 'field-list-1',
            'name' => 'Priority',
            'value' => 'High',
            'field_type' => 'list',
            'show' => true,
            'required' => true,
            'list_items' => ['Low', 'Medium', 'High', 'Critical'],
        ]);

        $response = $this->getJson("/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/custom_fields");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'text_custom_fields' => [
                        '*' => ['field_id', 'name', 'value', 'show', 'required'],
                    ],
                    'list_custom_fields' => [
                        '*' => ['field_id', 'name', 'value', 'show', 'required', 'list_items'],
                    ],
                ],
            ])
            ->assertJsonCount(1, 'data.text_custom_fields')
            ->assertJsonCount(1, 'data.list_custom_fields');
    }

    
    public function test_can_create_text_custom_fields()
    {
        $fieldsData = [
            'text_custom_fields' => [
                [
                    'name' => 'Project Code',
                    'value' => 'PROJ-2025',
                    'show' => true,
                    'required' => false,
                ],
                [
                    'name' => 'Region',
                    'value' => 'North America',
                    'show' => true,
                    'required' => true,
                ],
            ],
        ];

        $response = $this->postJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/custom_fields",
            $fieldsData
        );

        $response->assertStatus(201)
            ->assertJsonCount(2, 'data.text_custom_fields')
            ->assertJsonCount(0, 'data.list_custom_fields');

        $this->assertDatabaseHas('envelope_custom_fields', [
            'template_id' => $this->template->id,
            'name' => 'Project Code',
            'value' => 'PROJ-2025',
            'field_type' => 'text',
        ]);
    }

    
    public function test_can_create_list_custom_fields()
    {
        $fieldsData = [
            'list_custom_fields' => [
                [
                    'name' => 'Status',
                    'value' => 'Draft',
                    'show' => true,
                    'required' => true,
                    'list_items' => ['Draft', 'Review', 'Approved', 'Rejected'],
                ],
            ],
        ];

        $response = $this->postJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/custom_fields",
            $fieldsData
        );

        $response->assertStatus(201)
            ->assertJsonCount(0, 'data.text_custom_fields')
            ->assertJsonCount(1, 'data.list_custom_fields');

        $field = EnvelopeCustomField::where('template_id', $this->template->id)
            ->where('field_type', 'list')
            ->first();

        $this->assertNotNull($field);
        $this->assertEquals('Status', $field->name);
        $this->assertEquals(['Draft', 'Review', 'Approved', 'Rejected'], $field->list_items);
    }

    
    public function test_can_create_both_text_and_list_fields_together()
    {
        $fieldsData = [
            'text_custom_fields' => [
                ['name' => 'Text Field', 'value' => 'Value', 'show' => true, 'required' => false],
            ],
            'list_custom_fields' => [
                [
                    'name' => 'List Field',
                    'value' => 'Option1',
                    'show' => true,
                    'required' => true,
                    'list_items' => ['Option1', 'Option2'],
                ],
            ],
        ];

        $response = $this->postJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/custom_fields",
            $fieldsData
        );

        $response->assertStatus(201)
            ->assertJsonCount(1, 'data.text_custom_fields')
            ->assertJsonCount(1, 'data.list_custom_fields');

        $this->assertEquals(2, EnvelopeCustomField::where('template_id', $this->template->id)->count());
    }

    
    public function test_can_update_custom_fields()
    {
        // Create existing fields
        $textField = EnvelopeCustomField::create([
            'template_id' => $this->template->id,
            'field_id' => 'field-123',
            'name' => 'Original Name',
            'value' => 'Original Value',
            'field_type' => 'text',
            'show' => true,
            'required' => false,
        ]);

        $updateData = [
            'text_custom_fields' => [
                [
                    'field_id' => 'field-123',
                    'name' => 'Updated Name',
                    'value' => 'Updated Value',
                    'required' => true,
                ],
            ],
        ];

        $response = $this->putJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/custom_fields",
            $updateData
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('envelope_custom_fields', [
            'field_id' => 'field-123',
            'name' => 'Updated Name',
            'value' => 'Updated Value',
            'required' => true,
        ]);
    }

    
    public function test_can_delete_all_custom_fields()
    {
        // Create fields
        EnvelopeCustomField::create([
            'template_id' => $this->template->id,
            'field_id' => 'field-1',
            'name' => 'Field 1',
            'field_type' => 'text',
        ]);

        EnvelopeCustomField::create([
            'template_id' => $this->template->id,
            'field_id' => 'field-2',
            'name' => 'Field 2',
            'field_type' => 'list',
            'list_items' => ['A', 'B'],
        ]);

        $response = $this->deleteJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/custom_fields"
        );

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['deleted_count' => 2],
            ]);

        $this->assertDatabaseMissing('envelope_custom_fields', [
            'template_id' => $this->template->id,
        ]);
    }

    
    public function test_validates_required_name_field()
    {
        $invalidData = [
            'text_custom_fields' => [
                [
                    // Missing name
                    'value' => 'Some value',
                    'show' => true,
                ],
            ],
        ];

        $response = $this->postJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/custom_fields",
            $invalidData
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['text_custom_fields.0.name']);
    }

    
    public function test_handles_empty_custom_fields_gracefully()
    {
        $response = $this->getJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/custom_fields"
        );

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data.text_custom_fields')
            ->assertJsonCount(0, 'data.list_custom_fields');
    }

    
    public function test_update_requires_field_id()
    {
        $invalidData = [
            'text_custom_fields' => [
                [
                    // Missing field_id for update
                    'name' => 'Updated Name',
                    'value' => 'Updated Value',
                ],
            ],
        ];

        $response = $this->putJson(
            "/api/v2.1/accounts/{$this->account->id}/templates/{$this->template->template_id}/custom_fields",
            $invalidData
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['text_custom_fields.0.field_id']);
    }
}
