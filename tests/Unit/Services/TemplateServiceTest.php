<?php

use App\Models\Account;
use App\Models\Template;
use App\Models\User;
use App\Services\TemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->account = Account::factory()->create();
    $this->user = User::factory()->create(['account_id' => $this->account->id]);
    $this->service = new TemplateService();
});

describe('Template Creation', function () {
    test('creates template with basic data', function () {
        $template = $this->service->createTemplate($this->account->id, [
            'name' => 'Test Template',
            'description' => 'Test description',
        ], $this->user->id);

        expect($template->name)->toBe('Test Template')
            ->and($template->description)->toBe('Test description')
            ->and($template->account_id)->toBe($this->account->id);
    });

    test('generates unique template_id', function () {
        $template1 = $this->service->createTemplate($this->account->id, ['name' => 'Template 1'], $this->user->id);
        $template2 = $this->service->createTemplate($this->account->id, ['name' => 'Template 2'], $this->user->id);

        expect($template1->template_id)->not()->toBe($template2->template_id);
    });

    test('sets shared status correctly', function () {
        $template = $this->service->createTemplate($this->account->id, [
            'name' => 'Shared Template',
            'shared' => true,
        ], $this->user->id);

        expect($template->shared)->toBeTrue();
    });

    test('creates template with version 1', function () {
        $template = $this->service->createTemplate($this->account->id, ['name' => 'Template'], $this->user->id);

        expect($template->version)->toBe(1);
    });
});

describe('Template Retrieval', function () {
    test('gets template by id', function () {
        $created = Template::factory()->create(['account_id' => $this->account->id]);

        $template = $this->service->getTemplate($created->template_id);

        expect($template->id)->toBe($created->id);
    });

    test('lists templates with pagination', function () {
        Template::factory()->count(15)->create(['account_id' => $this->account->id]);

        $result = $this->service->listTemplates($this->account->id, ['per_page' => 10]);

        expect($result->count())->toBe(10)
            ->and($result->total())->toBe(15);
    });

    test('filters templates by shared status', function () {
        Template::factory()->count(5)->create(['account_id' => $this->account->id, 'shared' => true]);
        Template::factory()->count(3)->create(['account_id' => $this->account->id, 'shared' => false]);

        $result = $this->service->listTemplates($this->account->id, ['shared' => true]);

        expect($result->total())->toBe(5);
    });

    test('searches templates by name', function () {
        Template::factory()->create(['account_id' => $this->account->id, 'name' => 'Contract Template']);
        Template::factory()->create(['account_id' => $this->account->id, 'name' => 'Invoice Template']);

        $result = $this->service->listTemplates($this->account->id, ['search_text' => 'Contract']);

        expect($result->total())->toBe(1);
    });
});

describe('Template Modification', function () {
    test('updates template name', function () {
        $template = Template::factory()->create(['account_id' => $this->account->id, 'name' => 'Old Name']);

        $updated = $this->service->updateTemplate($template->template_id, ['name' => 'New Name']);

        expect($updated->name)->toBe('New Name');
    });

    test('updates template description', function () {
        $template = Template::factory()->create(['account_id' => $this->account->id]);

        $updated = $this->service->updateTemplate($template->template_id, [
            'description' => 'Updated description',
        ]);

        expect($updated->description)->toBe('Updated description');
    });

    test('updates shared status', function () {
        $template = Template::factory()->create(['account_id' => $this->account->id, 'shared' => false]);

        $updated = $this->service->updateTemplate($template->template_id, ['shared' => true]);

        expect($updated->shared)->toBeTrue();
    });

    test('deletes template', function () {
        $template = Template::factory()->create(['account_id' => $this->account->id]);

        $result = $this->service->deleteTemplate($template->template_id);

        expect($result)->toBeTrue();
        $this->assertSoftDeleted('templates', ['id' => $template->id]);
    });
});

describe('Envelope Creation from Template', function () {
    test('creates envelope from template', function () {
        $template = Template::factory()->create([
            'account_id' => $this->account->id,
            'email_subject' => 'Template Subject',
            'email_blurb' => 'Template Message',
        ]);

        $envelope = $this->service->createEnvelopeFromTemplate($template->template_id, $this->account->id, [], $this->user->id);

        expect($envelope->email_subject)->toBe('Template Subject')
            ->and($envelope->email_blurb)->toBe('Template Message')
            ->and($envelope->status)->toBe('draft');
    });

    test('overrides template data with custom data', function () {
        $template = Template::factory()->create([
            'account_id' => $this->account->id,
            'email_subject' => 'Template Subject',
        ]);

        $envelope = $this->service->createEnvelopeFromTemplate($template->template_id, $this->account->id, [
            'email_subject' => 'Custom Subject',
        ], $this->user->id);

        expect($envelope->email_subject)->toBe('Custom Subject');
    });
});

describe('Template Sharing', function () {
    test('shares template with user', function () {
        $template = Template::factory()->create(['account_id' => $this->account->id]);
        $otherUser = User::factory()->create(['account_id' => $this->account->id]);

        $this->service->shareTemplate($template->template_id, [$otherUser->id]);

        expect($template->fresh()->sharedAccess)->toHaveCount(1);
    });

    test('unshares template from user', function () {
        $template = Template::factory()->create(['account_id' => $this->account->id]);
        $otherUser = User::factory()->create(['account_id' => $this->account->id]);

        $this->service->shareTemplate($template->template_id, [$otherUser->id]);
        $this->service->unshareTemplate($template->template_id, [$otherUser->id]);

        expect($template->fresh()->sharedAccess)->toHaveCount(0);
    });
});
