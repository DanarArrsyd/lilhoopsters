<?php

use App\Livewire\Portal\Home;
use App\Models\Child;
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
});

it('renders the home page', function () {
    $this->actingAs($this->parent)->get(route('parent.home'))->assertOk();
});

it('redirects the old dashboard route to home', function () {
    $this->actingAs($this->parent)->get(route('parent.dashboard'))
        ->assertRedirect(route('parent.home'));
});

it('returns 404 for routes removed from navigation', function () {
    $this->actingAs($this->parent)->get('/parent/events')->assertNotFound();
});

it('renders the players, leaves, makeup, private, payments, attendance, and report-cards pages', function () {
    $this->actingAs($this->parent)->get(route('parent.players'))->assertOk();
    $this->actingAs($this->parent)->get(route('parent.leaves'))->assertOk();
    $this->actingAs($this->parent)->get(route('parent.makeup'))->assertOk();
    $this->actingAs($this->parent)->get(route('parent.private'))->assertOk();
    $this->actingAs($this->parent)->get(route('parent.payments'))->assertOk();
    $this->actingAs($this->parent)->get(route('parent.attendance'))->assertOk();
    $this->actingAs($this->parent)->get(route('parent.report-cards'))->assertOk();
});

it('shows an empty state when the parent has no children', function () {
    Livewire::actingAs($this->parent)
        ->test(Home::class)
        ->assertSee('Add your first player');
});

it('defaults to the first child and can switch children', function () {
    $first  = Child::factory()->create(['user_id' => $this->parent->id, 'status' => 'active', 'name' => 'Aisyah']);
    $second = Child::factory()->create(['user_id' => $this->parent->id, 'status' => 'active', 'name' => 'Bayu']);

    Livewire::actingAs($this->parent)
        ->test(Home::class)
        ->assertSet('activeChildId', $first->id)
        ->call('switchChild', $second->id)
        ->assertSet('activeChildId', $second->id);
});

it('refuses to switch to a child that does not belong to the parent', function () {
    $other = User::factory()->withRole('parent')->approved()->create();
    $notMine = Child::factory()->create(['user_id' => $other->id, 'status' => 'active']);

    Livewire::actingAs($this->parent)
        ->test(Home::class)
        ->call('switchChild', $notMine->id);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

it('shows the next session for the active child', function () {
    $child = Child::factory()->create(['user_id' => $this->parent->id, 'status' => 'active']);
    $location = \App\Models\Location::factory()->create(['name' => 'GOR Senayan']);
    $program  = \App\Models\Program::factory()->create(['name' => 'MVP']);
    $schedule = \App\Models\Schedule::factory()->create([
        'location_id' => $location->id,
        'program_id'  => $program->id,
        'day_of_week' => strtolower(now()->format('l')),
        'is_active'   => true,
    ]);
    \App\Models\Enrollment::factory()->create([
        'child_id' => $child->id, 'schedule_id' => $schedule->id,
        'type' => 'program', 'status' => 'approved',
        'started_at' => now()->subMonth(), 'expires_at' => now()->addMonth(),
    ]);

    Livewire::actingAs($this->parent)
        ->test(Home::class)
        ->assertSee('MVP')
        ->assertSee('GOR Senayan');
});

it('opens the active childs QR code when the child has an active enrollment', function () {
    $child = Child::factory()->create(['user_id' => $this->parent->id, 'status' => 'active']);
    $schedule = \App\Models\Schedule::factory()->create();
    \App\Models\Enrollment::factory()->create([
        'child_id' => $child->id, 'schedule_id' => $schedule->id,
        'type' => 'program', 'status' => 'approved',
    ]);

    Livewire::actingAs($this->parent)
        ->test(Home::class)
        ->call('openQr')
        ->assertSet('showQr', true)
        ->assertSee($child->name);
});

it('does not open the QR code when the child has no active enrollment', function () {
    Child::factory()->create(['user_id' => $this->parent->id, 'status' => 'active']);

    Livewire::actingAs($this->parent)
        ->test(Home::class)
        ->call('openQr')
        ->assertSet('showQr', false)
        ->assertSee(__('messages.portal.home.qr_no_package'));
});

it('shows quick action links to all portal pages', function () {
    Child::factory()->create(['user_id' => $this->parent->id, 'status' => 'active']);

    Livewire::actingAs($this->parent)
        ->test(Home::class)
        ->assertSee(route('parent.enroll'), false)
        ->assertSee(route('parent.leaves'), false)
        ->assertSee(route('parent.makeup'), false)
        ->assertSee(route('parent.private'), false)
        ->assertSee(route('parent.payments'), false)
        ->assertSee(route('parent.attendance'), false)
        ->assertSee(route('parent.report-cards'), false);
});

it('shows a banner when an event is open for registration', function () {
    $child = Child::factory()->create(['user_id' => $this->parent->id, 'status' => 'active']);
    \App\Models\Event::factory()->create([
        'name' => 'Summer Camp 2026',
        'is_active' => true, 'is_registerable' => true,
        'location_id' => null,
        'start_date' => now()->addDays(5), 'end_date' => now()->addDays(10),
    ]);

    Livewire::actingAs($this->parent)
        ->test(Home::class)
        ->assertSee('Summer Camp 2026');
});
