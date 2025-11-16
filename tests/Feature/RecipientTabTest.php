<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Account;
use App\Models\User;
use App\Models\Envelope;
use App\Models\EnvelopeRecipient;
use App\Models\EnvelopeTab;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;

class RecipientTabTest extends TestCase
{
    use RefreshDatabase;

    protected Account $account;
    protected User $user;
    protected Envelope $envelope;
    protected EnvelopeRecipient $recipient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\FileTypeSeeder::class);

        $this->account = Account::factory()->create();
        $this->user = User::factory()->create([
            'account_id' => $this->account->account_id,
        ]);

        $this->envelope = Envelope::factory()->create([
            'account_id' => $this->account->account_id,
            'created_by' => $this->user->user_id,
            'status' => 'draft',
        ]);

        $this->recipient = EnvelopeRecipient::factory()->create([
            'envelope_id' => $this->envelope->envelope_id,
            'recipient_id' => Str::uuid(),
            'email' => 'recipient@example.com',
        ]);

        Passport::actingAs($this->user);
    }

    /** @test */
    public function user_can_get_all_tabs_for_recipient()
    {
        // Create tabs for recipient
        EnvelopeTab::factory()->count(3)->create([
            'envelope_id' => $this->envelope->envelope_id,
            'recipient_id' => $this->recipient->recipient_id,
            'tab_type' => 'signature',
        ]);

        EnvelopeTab::factory()->count(2)->create([
            'envelope_id' => $this->envelope->envelope_id,
            'recipient_id' => $this->recipient->recipient_id,
            'tab_type' => 'text',
        ]);

        $response = $this->getJson(
            "/api/v2.1/accounts/{$this->account->account_id}/recipients/{$this->recipient->recipient_id}/tabs"
        );

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'signatureTabs' => [],
                'textTabs' => [],
            ],
            'meta',
        ]);

        $this->assertCount(3, $response->json('data.signatureTabs'));
        $this->assertCount(2, $response->json('data.textTabs'));
    }

    /** @test */
    public function user_can_add_tabs_to_recipient()
    {
        $tabsData = [
            'signatureTabs' => [
                [
                    'tab_label' => 'Signature 1',
                    'document_id' => '1',
                    'page_number' => 1,
                    'x_position' => 100,
                    'y_position' => 200,
                    'width' => 200,
                    'height' => 50,
                    'required' => true,
                ],
            ],
            'textTabs' => [
                [
                    'tab_label' => 'Name',
                    'document_id' => '1',
                    'page_number' => 1,
                    'x_position' => 100,
                    'y_position' => 300,
                    'width' => 150,
                    'height' => 30,
                    'required' => true,
                ],
            ],
        ];

        $response = $this->postJson(
            "/api/v2.1/accounts/{$this->account->account_id}/recipients/{$this->recipient->recipient_id}/tabs",
            $tabsData
        );

        $response->assertCreated();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'signatureTabs',
                'textTabs',
            ],
            'meta',
        ]);

        $this->assertDatabaseHas('envelope_tabs', [
            'envelope_id' => $this->envelope->envelope_id,
            'recipient_id' => $this->recipient->recipient_id,
            'tab_type' => 'signature',
            'tab_label' => 'Signature 1',
        ]);

        $this->assertDatabaseHas('envelope_tabs', [
            'envelope_id' => $this->envelope->envelope_id,
            'recipient_id' => $this->recipient->recipient_id,
            'tab_type' => 'text',
            'tab_label' => 'Name',
        ]);
    }

    /** @test */
    public function adding_tabs_requires_valid_positioning()
    {
        $tabsData = [
            'signatureTabs' => [
                [
                    'tab_label' => 'Signature',
                    'document_id' => '1',
                    'page_number' => 1,
                    'x_position' => -10, // Invalid
                    'y_position' => 200,
                ],
            ],
        ];

        $response = $this->postJson(
            "/api/v2.1/accounts/{$this->account->account_id}/recipients/{$this->recipient->recipient_id}/tabs",
            $tabsData
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['signatureTabs.0.x_position']);
    }

    /** @test */
    public function tabs_are_grouped_by_type_in_response()
    {
        // Create various tab types
        EnvelopeTab::factory()->create([
            'envelope_id' => $this->envelope->envelope_id,
            'recipient_id' => $this->recipient->recipient_id,
            'tab_type' => 'signature',
        ]);

        EnvelopeTab::factory()->create([
            'envelope_id' => $this->envelope->envelope_id,
            'recipient_id' => $this->recipient->recipient_id,
            'tab_type' => 'text',
        ]);

        EnvelopeTab::factory()->create([
            'envelope_id' => $this->envelope->envelope_id,
            'recipient_id' => $this->recipient->recipient_id,
            'tab_type' => 'date',
        ]);

        $response = $this->getJson(
            "/api/v2.1/accounts/{$this->account->account_id}/recipients/{$this->recipient->recipient_id}/tabs"
        );

        $response->assertOk();
        $data = $response->json('data');

        $this->assertArrayHasKey('signatureTabs', $data);
        $this->assertArrayHasKey('textTabs', $data);
        $this->assertArrayHasKey('dateTabs', $data);

        $this->assertCount(1, $data['signatureTabs']);
        $this->assertCount(1, $data['textTabs']);
        $this->assertCount(1, $data['dateTabs']);
    }
}
