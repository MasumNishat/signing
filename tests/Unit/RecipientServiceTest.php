<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\Envelope;
use App\Models\EnvelopeRecipient;
use App\Models\EnvelopeTab;
use App\Services\RecipientService;
use App\Services\TabService;
use App\Exceptions\Custom\BusinessLogicException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Recipient Service Unit Tests
 *
 * Tests business logic for recipient operations including tab management.
 */
class RecipientServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RecipientService $recipientService;
    protected Account $account;
    protected Envelope $envelope;

    protected function setUp(): void
    {
        parent::setUp();

        $this->recipientService = app(RecipientService::class);

        // Create test account
        $this->account = Account::factory()->create();

        // Create test envelope
        $this->envelope = Envelope::factory()->create([
            'account_id' => $this->account->id,
            'status' => 'draft',
        ]);
    }

    /**
     * Test add recipient tabs creates tabs successfully
     */
    public function test_add_recipient_tabs_creates_tabs_successfully(): void
    {
        // Create a recipient
        $recipient = EnvelopeRecipient::factory()->create([
            'envelope_id' => $this->envelope->id,
            'recipient_type' => 'signer',
            'status' => 'created',
        ]);

        // Create a document for the envelope
        $document = $this->envelope->documents()->create([
            'document_id' => 'doc1',
            'name' => 'Test Document',
            'file_extension' => 'pdf',
            'order' => 1,
        ]);

        // Tab data to add
        $tabsData = [
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
        ];

        // Add tabs to recipient
        $result = $this->recipientService->addRecipientTabs($recipient, $tabsData);

        // Assertions
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('signature', $result[0]['tab_type']);
        $this->assertEquals('date_signed', $result[1]['tab_type']);

        // Verify tabs were saved to database
        $this->assertDatabaseCount('envelope_tabs', 2);
        $this->assertDatabaseHas('envelope_tabs', [
            'recipient_id' => $recipient->recipient_id,
            'tab_type' => 'signature',
            'tab_label' => 'Sign Here',
        ]);
        $this->assertDatabaseHas('envelope_tabs', [
            'recipient_id' => $recipient->recipient_id,
            'tab_type' => 'date_signed',
            'tab_label' => 'Date',
        ]);
    }

    /**
     * Test add recipient tabs fails for signed recipient
     */
    public function test_add_recipient_tabs_fails_for_signed_recipient(): void
    {
        // Create a recipient who has already signed
        $recipient = EnvelopeRecipient::factory()->create([
            'envelope_id' => $this->envelope->id,
            'recipient_type' => 'signer',
            'status' => 'signed',
            'signed_date_time' => now(),
        ]);

        // Create a document
        $document = $this->envelope->documents()->create([
            'document_id' => 'doc1',
            'name' => 'Test Document',
            'file_extension' => 'pdf',
            'order' => 1,
        ]);

        // Tab data to add
        $tabsData = [
            [
                'tab_type' => 'signature',
                'document_id' => 'doc1',
                'page_number' => 1,
                'x_position' => 100,
                'y_position' => 200,
            ],
        ];

        // Expect exception
        $this->expectException(BusinessLogicException::class);
        $this->expectExceptionMessage('Cannot add tabs for recipient who has already signed');

        // Try to add tabs to signed recipient
        $this->recipientService->addRecipientTabs($recipient, $tabsData);
    }

    /**
     * Test add recipient tabs rollback on error
     */
    public function test_add_recipient_tabs_rollback_on_error(): void
    {
        // Create a recipient
        $recipient = EnvelopeRecipient::factory()->create([
            'envelope_id' => $this->envelope->id,
            'recipient_type' => 'signer',
            'status' => 'created',
        ]);

        // Invalid tab data (missing required fields)
        $tabsData = [
            [
                'tab_type' => 'signature',
                // Missing document_id, page_number, x_position, y_position
            ],
        ];

        // Expect exception
        $this->expectException(\Exception::class);

        // Try to add invalid tabs
        try {
            $this->recipientService->addRecipientTabs($recipient, $tabsData);
        } catch (\Exception $e) {
            // Verify no tabs were created due to rollback
            $this->assertDatabaseCount('envelope_tabs', 0);
            throw $e;
        }
    }

    /**
     * Test add recipient tabs associates with correct recipient
     */
    public function test_add_recipient_tabs_associates_with_correct_recipient(): void
    {
        // Create two recipients
        $recipient1 = EnvelopeRecipient::factory()->create([
            'envelope_id' => $this->envelope->id,
            'recipient_type' => 'signer',
            'status' => 'created',
        ]);

        $recipient2 = EnvelopeRecipient::factory()->create([
            'envelope_id' => $this->envelope->id,
            'recipient_type' => 'signer',
            'status' => 'created',
        ]);

        // Create a document
        $document = $this->envelope->documents()->create([
            'document_id' => 'doc1',
            'name' => 'Test Document',
            'file_extension' => 'pdf',
            'order' => 1,
        ]);

        // Tab data
        $tabsData = [
            [
                'tab_type' => 'signature',
                'document_id' => 'doc1',
                'page_number' => 1,
                'x_position' => 100,
                'y_position' => 200,
            ],
        ];

        // Add tabs to recipient1
        $result = $this->recipientService->addRecipientTabs($recipient1, $tabsData);

        // Verify tab is associated with recipient1 only
        $this->assertEquals($recipient1->recipient_id, $result[0]['recipient_id']);
        $this->assertDatabaseHas('envelope_tabs', [
            'recipient_id' => $recipient1->recipient_id,
            'tab_type' => 'signature',
        ]);
        $this->assertDatabaseMissing('envelope_tabs', [
            'recipient_id' => $recipient2->recipient_id,
        ]);
    }
}
