<?php

use App\Livewire\Admin\ReportCards;
use App\Models\Child;
use App\Models\Coach;
use App\Models\Enrollment;
use App\Models\ReportCard;
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

    $this->admin    = User::factory()->withRole('admin')->approved()->create();
    $this->child    = Child::factory()->create();
    $this->coach    = Coach::factory()->create();
    $this->enrollment = Enrollment::factory()->approved()->create(['child_id' => $this->child->id]);
});

it('renders report cards page', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.report-cards'))
        ->assertOk();
});

it('shows empty state when no cards', function () {
    Livewire::actingAs($this->admin)
        ->test(ReportCards::class)
        ->assertSee('No report cards found');
});

it('can create a report card', function () {
    Livewire::actingAs($this->admin)
        ->test(ReportCards::class)
        ->call('openForm')
        ->set('childId', $this->child->id)
        ->set('enrollmentId', $this->enrollment->id)
        ->set('coachId', $this->coach->id)
        ->set('periodLabel', 'Q2 2025')
        ->set('periodStart', '2025-04-01')
        ->set('periodEnd', '2025-06-30')
        ->call('create');

    expect(ReportCard::where('child_id', $this->child->id)->count())->toBe(1);
    expect(ReportCard::first()->status)->toBe('draft');
});

it('validates required fields on create', function () {
    Livewire::actingAs($this->admin)
        ->test(ReportCards::class)
        ->call('openForm')
        ->call('create')
        ->assertHasErrors(['childId', 'enrollmentId', 'coachId', 'periodLabel', 'periodStart', 'periodEnd']);
});

it('can publish a submitted report card', function () {
    $card = ReportCard::factory()->submitted()->create([
        'child_id' => $this->child->id,
        'coach_id' => $this->coach->id,
    ]);

    Livewire::actingAs($this->admin)
        ->test(ReportCards::class)
        ->call('confirmPublish', $card->id)
        ->call('publish');

    expect($card->fresh()->status)->toBe('published');
    expect($card->fresh()->published_at)->not->toBeNull();
});

it('shows report cards in list', function () {
    ReportCard::factory()->create([
        'child_id' => $this->child->id,
        'coach_id' => $this->coach->id,
    ]);

    Livewire::actingAs($this->admin)
        ->test(ReportCards::class)
        ->assertSee($this->child->name);
});
