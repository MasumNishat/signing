<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\EnvelopeRecipient;
use App\Models\EnvelopeTab;
use App\Services\RecipientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class RecipientTabServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RecipientService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RecipientService();
        $this->seed(\Database\Seeders\FileTypeSeeder::class);
    }

    /** @test */
    public function it_can_get_all_tabs_for_recipient()
    {
        // Create recipient with tabs
        $recipient = EnvelopeRecipient::factory()->create([
            'recipient_id' => Str::uuid(),
        ]);

        EnvelopeTab::factory()->count(3)->create([
            'envelope_id' => $recipient->envelope_id,
            'recipient_id' => $recipient->recipient_id,
            'tab_type' => 'signature',
        ]);

        EnvelopeTab::factory()->count(2)->create([
            'envelope_id' => $recipient->envelope_id,
            'recipient_id' => $recipient->recipient_id,
            'tab_type' => 'text',
        ]);

        // Get tabs
        $tabs = $this->service->getRecipientTabs($recipient->recipient_id);

        $this->assertCount(5, $tabs);
        $this->assertTrue($tabs->every(fn($tab) => $tab->recipient_id === $recipient->recipient_id));
    }

    /** @test */
    public function it_can_add_tabs_to_recipient()
    {
        $recipient = EnvelopeRecipient::factory()->create([
            'recipient_id' => Str::uuid(),
        ]);

        $tabsData = [
            [
                'tab_type' => 'signature',
                'tab_label' => 'Signature 1',
                'document_id' => '1',
                'page_number' => 1,
                'x_position' => 100,
                'y_position' => 200,
                'width' => 200,
                'height' => 50,
                'required' => true,
            ],
            [
                'tab_type' => 'text',
                'tab_label' => 'Name',
                'document_id' => '1',
                'page_number' => 1,
                'x_position' => 100,
                'y_position' => 300,
                'width' => 150,
                'height' => 30,
                'required' => true,
            ],
        ];

        $tabs = $this->service->addRecipientTabs($recipient->recipient_id, $tabsData);

        $this->assertCount(2, $tabs);
        $this->assertEquals('signature', $tabs[0]->tab_type);
        $this->assertEquals('text', $tabs[1]->tab_type);
        $this->assertTrue($tabs->every(fn($tab) => $tab->recipient_id === $recipient->recipient_id));
    }

    /** @test */
    public function it_validates_tab_positioning()
    {
        $recipient = EnvelopeRecipient::factory()->create([
            'recipient_id' => Str::uuid(),
        ]);

        $tabsData = [
            [
                'tab_type' => 'signature',
                'tab_label' => 'Signature',
                'document_id' => '1',
                'page_number' => 1,
                'x_position' => -10, // Invalid negative position
                'y_position' => 200,
                'width' => 200,
                'height' => 50,
            ],
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->service->addRecipientTabs($recipient->recipient_id, $tabsData);
    }

    /** @test */
    public function it_groups_tabs_by_type()
    {
        $recipient = EnvelopeRecipient::factory()->create([
            'recipient_id' => Str::uuid(),
        ]);

        // Create various tab types
        EnvelopeTab::factory()->count(3)->create([
            'envelope_id' => $recipient->envelope_id,
            'recipient_id' => $recipient->recipient_id,
            'tab_type' => 'signature',
        ]);

        EnvelopeTab::factory()->count(2)->create([
            'envelope_id' => $recipient->envelope_id,
            'recipient_id' => $recipient->recipient_id,
            'tab_type' => 'text',
        ]);

        EnvelopeTab::factory()->count(1)->create([
            'envelope_id' => $recipient->envelope_id,
            'recipient_id' => $recipient->recipient_id,
            'tab_type' => 'date',
        ]);

        $tabs = $this->service->getRecipientTabs($recipient->recipient_id);
        $grouped = $tabs->groupBy('tab_type');

        $this->assertCount(3, $grouped);
        $this->assertCount(3, $grouped->get('signature'));
        $this->assertCount(2, $grouped->get('text'));
        $this->assertCount(1, $grouped->get('date'));
    }
}
