<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Envelope;
use App\Models\EnvelopeRecipient;
use App\Models\EnvelopeTab;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * Recipient Tab Feature Tests
 *
 * Tests API endpoints for recipient tab operations (GET/POST).
 */
class RecipientTabTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Account $account;
    protected Envelope $envelope;
    protected EnvelopeRecipient $recipient;

    protected function setUp(): void
    {
        parent::setUp();

        // Run passport install
        $this->artisan('passport:install', ['--no-interaction' => true]);

        // Create user and account
        $this->account = Account::factory()->create();
        $this->user = User::factory()->create([
            'account_id' => $this->account->id,
        ]);

        // Authenticate user
        Passport::actingAs($this->user);

        // Create envelope
        $this->envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
        ]);

        // Create recipient
        $this->recipient = EnvelopeRecipient::factory()->create([
            'envelope_id' => $this->envelope->id,
            'recipient_type' => 'signer',
            'status' => 'created',
        ]);

        // Create document
        $this->envelope->documents()->create([
            'document_id' => 'doc1',
            'name' => 'Test Document',
            'file_extension' => 'pdf',
            'order' => 1,
        ]);
    }

    /**
     * Test GET recipient tabs returns all tabs
     */
    public function test_get_recipient_tabs_returns_all_tabs(): void
    {
        // Create tabs for recipient
        EnvelopeTab::factory()->count(3)->create([
            'envelope_id' => $this->envelope->id,
            'recipient_id' => $this->recipient->recipient_id,
            'document_id' => 'doc1',
        ]);

        // Make GET request
        $response = $this->getJson(sprintf(
            '/api/v2.1/accounts/%s/envelopes/%s/recipients/%s/tabs',
            $this->account->account_id,
            $this->envelope->envelope_id,
            $this->recipient->recipient_id
        ));

        // Assertions
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'recipient_id',
                'total_tabs',
                'tabs' => [
                    '*' => [
                        'tab_id',
                        'tab_type',
                        'tab_label',
                        'document_id',
                        'page_number',
                        'x_position',
                        'y_position',
                        'width',
                        'height',
                        'required',
                        'locked',
                        'value',
                        'status',
                    ],
                ],
            ],
            'message',
        ]);

        $data = $response->json('data');
        $this->assertEquals($this->recipient->recipient_id, $data['recipient_id']);
        $this->assertEquals(3, $data['total_tabs']);
        $this->assertCount(3, $data['tabs']);
    }

    /**
     * Test POST recipient tabs adds tabs
     */
    public function test_post_recipient_tabs_adds_tabs(): void
    {
        $tabsData = [
            'tabs' => [
                [
                    'tab_type' => 'signature',
                    'document_id' => 'doc1',
                    'page_number' => 1,
                    'x_position' => 100,
                    'y_position' => 200,
                    'tab_label' => 'Sign Here',
                ],
                [
                    'tab_type' => 'date_signed',
                    'document_id' => 'doc1',
                    'page_number' => 1,
                    'x_position' => 300,
                    'y_position' => 200,
                    'tab_label' => 'Date',
                ],
            ],
        ];

        // Make POST request
        $response = $this->postJson(sprintf(
            '/api/v2.1/accounts/%s/envelopes/%s/recipients/%s/tabs',
            $this->account->account_id,
            $this->envelope->envelope_id,
            $this->recipient->recipient_id
        ), $tabsData);

        // Assertions
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'recipient_id',
                'added_count',
                'tabs',
            ],
            'message',
        ]);

        $data = $response->json('data');
        $this->assertEquals($this->recipient->recipient_id, $data['recipient_id']);
        $this->assertEquals(2, $data['added_count']);

        // Verify tabs were created in database
        $this->assertDatabaseCount('envelope_tabs', 2);
        $this->assertDatabaseHas('envelope_tabs', [
            'recipient_id' => $this->recipient->recipient_id,
            'tab_type' => 'signature',
            'tab_label' => 'Sign Here',
        ]);
    }

    /**
     * Test GET recipient tabs returns empty for no tabs
     */
    public function test_get_recipient_tabs_returns_empty_for_no_tabs(): void
    {
        // Make GET request (no tabs created)
        $response = $this->getJson(sprintf(
            '/api/v2.1/accounts/%s/envelopes/%s/recipients/%s/tabs',
            $this->account->account_id,
            $this->envelope->envelope_id,
            $this->recipient->recipient_id
        ));

        // Assertions
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(0, $data['total_tabs']);
        $this->assertEmpty($data['tabs']);
    }

    /**
     * Test POST recipient tabs validates required fields
     */
    public function test_post_recipient_tabs_validates_required_fields(): void
    {
        $invalidData = [
            'tabs' => [
                [
                    'tab_type' => 'signature',
                    // Missing: document_id, page_number, x_position, y_position
                ],
            ],
        ];

        // Make POST request with invalid data
        $response = $this->postJson(sprintf(
            '/api/v2.1/accounts/%s/envelopes/%s/recipients/%s/tabs',
            $this->account->account_id,
            $this->envelope->envelope_id,
            $this->recipient->recipient_id
        ), $invalidData);

        // Assertions
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'tabs.0.document_id',
            'tabs.0.page_number',
            'tabs.0.x_position',
            'tabs.0.y_position',
        ]);
    }

    /**
     * Test POST recipient tabs associates with recipient
     */
    public function test_post_recipient_tabs_associates_with_recipient(): void
    {
        // Create another recipient
        $otherRecipient = EnvelopeRecipient::factory()->create([
            'envelope_id' => $this->envelope->id,
            'recipient_type' => 'signer',
            'status' => 'created',
        ]);

        $tabsData = [
            'tabs' => [
                [
                    'tab_type' => 'signature',
                    'document_id' => 'doc1',
                    'page_number' => 1,
                    'x_position' => 100,
                    'y_position' => 200,
                ],
            ],
        ];

        // Add tabs to first recipient
        $this->postJson(sprintf(
            '/api/v2.1/accounts/%s/envelopes/%s/recipients/%s/tabs',
            $this->account->account_id,
            $this->envelope->envelope_id,
            $this->recipient->recipient_id
        ), $tabsData);

        // Verify tab is only associated with first recipient
        $this->assertDatabaseHas('envelope_tabs', [
            'recipient_id' => $this->recipient->recipient_id,
            'tab_type' => 'signature',
        ]);
        $this->assertDatabaseMissing('envelope_tabs', [
            'recipient_id' => $otherRecipient->recipient_id,
        ]);
    }

    /**
     * Test recipient tabs are deleted with recipient
     */
    public function test_recipient_tabs_are_deleted_with_recipient(): void
    {
        // Create tabs for recipient
        EnvelopeTab::factory()->count(2)->create([
            'envelope_id' => $this->envelope->id,
            'recipient_id' => $this->recipient->recipient_id,
            'document_id' => 'doc1',
        ]);

        // Verify tabs exist
        $this->assertDatabaseCount('envelope_tabs', 2);

        // Delete recipient
        $this->deleteJson(sprintf(
            '/api/v2.1/accounts/%s/envelopes/%s/recipients/%s',
            $this->account->account_id,
            $this->envelope->envelope_id,
            $this->recipient->recipient_id
        ));

        // Verify tabs were deleted (cascade delete)
        $this->assertDatabaseCount('envelope_tabs', 0);
    }

    /**
     * Test POST recipient tabs fails for signed recipient
     */
    public function test_post_recipient_tabs_fails_for_signed_recipient(): void
    {
        // Update recipient to signed status
        $this->recipient->update([
            'status' => 'signed',
            'signed_date_time' => now(),
        ]);

        $tabsData = [
            'tabs' => [
                [
                    'tab_type' => 'signature',
                    'document_id' => 'doc1',
                    'page_number' => 1,
                    'x_position' => 100,
                    'y_position' => 200,
                ],
            ],
        ];

        // Make POST request
        $response = $this->postJson(sprintf(
            '/api/v2.1/accounts/%s/envelopes/%s/recipients/%s/tabs',
            $this->account->account_id,
            $this->envelope->envelope_id,
            $this->recipient->recipient_id
        ), $tabsData);

        // Assertions
        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
        ]);
    }

    /**
     * Test GET recipient tabs requires authentication
     */
    public function test_get_recipient_tabs_requires_authentication(): void
    {
        // Log out user
        Passport::actingAs(null);

        // Make GET request without authentication
        $response = $this->getJson(sprintf(
            '/api/v2.1/accounts/%s/envelopes/%s/recipients/%s/tabs',
            $this->account->account_id,
            $this->envelope->envelope_id,
            $this->recipient->recipient_id
        ));

        // Assertions
        $response->assertStatus(401);
    }
}
