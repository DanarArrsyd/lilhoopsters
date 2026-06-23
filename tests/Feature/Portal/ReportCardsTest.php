<?php

use App\Livewire\Portal\ReportCards;
use App\Models\Child;
use App\Models\Coach;
use App\Models\ReportCard;
use App\Models\ReportCardScore;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::insert([
        ['name' => 'super_admin', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'admin',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'coach',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'parent',      'created_at' => now(), 'updated_at' => now()],
    ]);

    $this->parent = User::factory()->withRole('parent')->approved()->create();
    $this->child  = Child::factory()->create(['user_id' => $this->parent->id]);
    $this->coach  = Coach::factory()->create();

    // Distinct period labels — the factory picks one of four at random, so
    // pin them or the published/draft labels can collide and flake the
    // assertSee / assertDontSee checks.
    $this->publishedCard = ReportCard::factory()->published()->create([
        'child_id'     => $this->child->id,
        'coach_id'     => $this->coach->id,
        'period_label' => 'Published Q1 2025',
    ]);

    $this->draftCard = ReportCard::factory()->create([
        'child_id'     => $this->child->id,
        'coach_id'     => $this->coach->id,
        'status'       => 'draft',
        'period_label' => 'Draft Q4 2025',
    ]);
});

it('renders parent report cards page', function () {
    $this->actingAs($this->parent)
        ->get(route('parent.report-cards'))
        ->assertOk();
});

it('only shows published cards', function () {
    Livewire::actingAs($this->parent)
        ->test(ReportCards::class)
        ->assertSee($this->publishedCard->period_label)
        ->assertDontSee($this->draftCard->period_label);
});

it('cannot see another parents report card', function () {
    $otherParent = User::factory()->withRole('parent')->approved()->create();
    $otherChild  = Child::factory()->create(['user_id' => $otherParent->id]);
    $otherCard   = ReportCard::factory()->published()->create([
        'child_id' => $otherChild->id,
        'coach_id' => $this->coach->id,
    ]);

    Livewire::actingAs($this->parent)
        ->test(ReportCards::class)
        ->assertDontSee($otherChild->name);
});

it('can open detail modal for own published card', function () {
    ReportCardScore::factory()->create([
        'report_card_id' => $this->publishedCard->id,
        'category'       => 'dribbling',
        'score'          => 4,
    ]);

    Livewire::actingAs($this->parent)
        ->test(ReportCards::class)
        ->call('openDetail', $this->publishedCard->id)
        ->assertSet('showDetail', true)
        ->assertSet('detailCardId', $this->publishedCard->id);
});

it('cannot open detail for another parents card', function () {
    $otherParent = User::factory()->withRole('parent')->approved()->create();
    $otherChild  = Child::factory()->create(['user_id' => $otherParent->id]);
    $otherCard   = ReportCard::factory()->published()->create([
        'child_id' => $otherChild->id,
        'coach_id' => $this->coach->id,
    ]);

    Livewire::actingAs($this->parent)
        ->test(ReportCards::class)
        ->call('openDetail', $otherCard->id)
        ->assertSet('showDetail', false);
});
