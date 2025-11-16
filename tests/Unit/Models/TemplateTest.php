<?php

use App\Models\Account;
use App\Models\Template;
use App\Models\User;
use App\Models\EnvelopeDocument;
use App\Models\EnvelopeRecipient;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->account = Account::factory()->create();
    $this->user = User::factory()->create(['account_id' => $this->account->id]);
});

describe('Template Attributes', function () {
    test('generates unique template_id on creation', function () {
        $template1 = Template::factory()->create();
        $template2 = Template::factory()->create();

        expect($template1->template_id)->not()->toBe($template2->template_id)
            ->and($template1->template_id)->not()->toBeNull()
            ->and($template2->template_id)->not()->toBeNull();
    });

    test('sets default version to 1', function () {
        $template = Template::factory()->create(['version' => null]);

        expect($template->version)->toBe(1);
    });

    test('sets default shared to false', function () {
        $template = Template::factory()->create(['shared' => null]);

        expect($template->shared)->toBeFalse();
    });

    test('allows custom version number', function () {
        $template = Template::factory()->create(['version' => 5]);

        expect($template->version)->toBe(5);
    });
});

describe('Template Relationships', function () {
    test('has documents relationship', function () {
        $template = Template::factory()->create();
        EnvelopeDocument::factory()->count(3)->create(['template_id' => $template->id]);

        expect($template->documents)->toHaveCount(3);
    });

    test('has recipients relationship', function () {
        $template = Template::factory()->create();
        EnvelopeRecipient::factory()->count(4)->create(['template_id' => $template->id]);

        expect($template->recipients)->toHaveCount(4);
    });

    test('belongs to account', function () {
        $account = Account::factory()->create();
        $template = Template::factory()->create(['account_id' => $account->id]);

        expect($template->account->id)->toBe($account->id);
    });

    test('belongs to owner user', function () {
        $user = User::factory()->create();
        $template = Template::factory()->create(['owner_user_id' => $user->id]);

        expect($template->owner->id)->toBe($user->id);
    });
});

describe('Template Query Scopes', function () {
    test('shared scope returns only shared templates', function () {
        Template::factory()->count(5)->create(['shared' => true]);
        Template::factory()->count(3)->create(['shared' => false]);

        $shared = Template::shared()->get();

        expect($shared)->toHaveCount(5);
    });

    test('forAccount scope filters by account', function () {
        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();

        Template::factory()->count(5)->create(['account_id' => $account1->id]);
        Template::factory()->count(3)->create(['account_id' => $account2->id]);

        $templates = Template::forAccount($account1->id)->get();

        expect($templates)->toHaveCount(5);
    });

    test('ownedBy scope filters by owner', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Template::factory()->count(4)->create(['owner_user_id' => $user1->id]);
        Template::factory()->count(2)->create(['owner_user_id' => $user2->id]);

        $templates = Template::ownedBy($user1->id)->get();

        expect($templates)->toHaveCount(4);
    });
});

describe('Template Soft Deletes', function () {
    test('soft deletes template', function () {
        $template = Template::factory()->create();

        $template->delete();

        expect($template->trashed())->toBeTrue();
        $this->assertSoftDeleted('templates', ['id' => $template->id]);
    });

    test('can restore soft deleted template', function () {
        $template = Template::factory()->create();
        $template->delete();

        $template->restore();

        expect($template->trashed())->toBeFalse();
        $this->assertDatabaseHas('templates', [
            'id' => $template->id,
            'deleted_at' => null,
        ]);
    });

    test('withTrashed includes soft deleted templates', function () {
        Template::factory()->count(3)->create();
        $deleted = Template::factory()->create();
        $deleted->delete();

        $all = Template::withTrashed()->get();

        expect($all)->toHaveCount(4);
    });

    test('onlyTrashed returns only soft deleted templates', function () {
        Template::factory()->count(3)->create();
        $deleted = Template::factory()->create();
        $deleted->delete();

        $trashed = Template::onlyTrashed()->get();

        expect($trashed)->toHaveCount(1);
    });
});
