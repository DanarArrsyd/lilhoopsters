<?php

use App\Livewire\Coach\ReportCards;
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

    $this->coachUser = User::factory()->withRole('coach')->approved()->create();
    $this->coach     = Coach::factory()->create(['user_id' => $this->coachUser->id]);
    $this->child     = Child::factory()->create();

    $this->card = ReportCard::factory()->create([
        'child_id' => $this->child->id,
        'coach_id' => $this->coach->id,
        'status'   => 'draft',
    ]);
});

it('renders coach report cards page', function () {
    $this->actingAs($this->coachUser)
        ->get(route('coach.report-cards'))
        ->assertOk();
});

it('shows only own report cards', function () {
    $otherCoach = Coach::factory()->create();
    $otherCard  = ReportCard::factory()->create(['coach_id' => $otherCoach->id]);

    Livewire::actingAs($this->coachUser)
        ->test(ReportCards::class)
        ->assertSee($this->child->name)
        ->assertDontSee($otherCard->child->name);
});

it('can open score modal and save scores', function () {
    $scores = [];
    foreach (['dribbling', 'passing', 'shooting', 'defense', 'attitude', 'discipline'] as $cat) {
        $scores[$cat] = ['score' => 4, 'notes' => 'Good'];
    }

    Livewire::actingAs($this->coachUser)
        ->test(ReportCards::class)
        ->call('openScoreModal', $this->card->id)
        ->set('scores', $scores)
        ->call('saveScores');

    expect(ReportCardScore::where('report_card_id', $this->card->id)->count())->toBe(6);
    expect($this->card->fresh()->status)->toBe('submitted');
});

it('validates scores are 1-5', function () {
    $scores = [];
    foreach (['dribbling', 'passing', 'shooting', 'defense', 'attitude', 'discipline'] as $cat) {
        $scores[$cat] = ['score' => 6, 'notes' => ''];
    }

    Livewire::actingAs($this->coachUser)
        ->test(ReportCards::class)
        ->call('openScoreModal', $this->card->id)
        ->set('scores', $scores)
        ->call('saveScores')
        ->assertHasErrors(['scores.dribbling.score']);
});

it('cannot see another coachs report card in list', function () {
    $otherCoach = Coach::factory()->create();
    $otherCard  = ReportCard::factory()->create(['coach_id' => $otherCoach->id]);

    // Own card appears, other coach's card should not be visible in the list
    Livewire::actingAs($this->coachUser)
        ->test(ReportCards::class)
        ->assertSee($this->child->name)
        ->assertDontSee($otherCard->child->name);
});
