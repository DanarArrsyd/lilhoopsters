<?php
// tests/Feature/Admin/EventTest.php

use App\Livewire\Admin\Events;
use App\Livewire\Portal\AttendanceHistory;
use App\Models\Child;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\Location;
use App\Models\Package;
use App\Models\Program;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\User;
use App\Services\EventService;
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
    $this->parent   = User::factory()->withRole('parent')->approved()->create();
    $this->location = Location::factory()->create();
    $this->program  = Program::factory()->create();
    $this->package  = Package::factory()->regular()->create(['location_id' => $this->location->id]);
    $this->schedule = Schedule::factory()->create([
        'location_id' => $this->location->id,
        'program_id'  => $this->program->id,
        'day_of_week' => 'monday',
    ]);
    $this->child    = Child::factory()->create(['user_id' => $this->parent->id]);

    $this->enrollment = Enrollment::factory()->program()->approved()->create([
        'child_id'    => $this->child->id,
        'schedule_id' => $this->schedule->id,
        'package_id'  => $this->package->id,
        'started_at'  => today()->subWeeks(2),
        'expires_at'  => today()->addDays(30),
    ]);
});

it('extends expiry by the event length for affected enrollments', function () {
    $event = Event::create([
        'name'       => 'Summer Champ',
        'start_date' => today()->addDay(),
        'end_date'   => today()->addDays(14),   // 14-day event
        'is_active'  => true,
    ]);

    EventService::applyFreeze($event);

    expect($this->enrollment->fresh()->expires_at->toDateString())
        ->toBe(today()->addDays(44)->toDateString()); // 30 + 14
    expect($event->enrollments()->count())->toBe(1);
});

it('is idempotent — applying twice does not double-extend', function () {
    $event = Event::create([
        'name' => 'Camp', 'start_date' => today()->addDay(), 'end_date' => today()->addDays(14), 'is_active' => true,
    ]);

    EventService::applyFreeze($event);
    EventService::applyFreeze($event);

    expect($this->enrollment->fresh()->expires_at->toDateString())
        ->toBe(today()->addDays(44)->toDateString());
});

it('reverses the freeze and restores the original expiry', function () {
    $event = Event::create([
        'name' => 'Camp', 'start_date' => today()->addDay(), 'end_date' => today()->addDays(14), 'is_active' => true,
    ]);

    EventService::applyFreeze($event);
    EventService::reverseFreeze($event);

    expect($this->enrollment->fresh()->expires_at->toDateString())
        ->toBe(today()->addDays(30)->toDateString());
    expect($event->enrollments()->count())->toBe(0);
});

it('only affects enrollments within the event scope', function () {
    $otherLocation = Location::factory()->create();

    $event = Event::create([
        'name'        => 'Cikarang only', 'start_date' => today()->addDay(), 'end_date' => today()->addDays(14),
        'location_id' => $otherLocation->id, 'is_active' => true,
    ]);

    EventService::applyFreeze($event);

    // Our enrollment is at a different location → untouched.
    expect($this->enrollment->fresh()->expires_at->toDateString())
        ->toBe(today()->addDays(30)->toDateString());
});

it('admin creating an active event freezes packages', function () {
    Livewire::actingAs($this->admin)->test(Events::class)
        ->call('openCreate')
        ->set('name', 'Summer Champ')
        ->set('start_date', today()->addDay()->toDateString())
        ->set('end_date', today()->addDays(14)->toDateString())
        ->call('save')
        ->assertHasNoErrors();

    expect($this->enrollment->fresh()->expires_at->toDateString())
        ->toBe(today()->addDays(44)->toDateString());
});

it('admin deleting an event reverts the freeze', function () {
    $event = Event::create([
        'name' => 'Camp', 'start_date' => today()->addDay(), 'end_date' => today()->addDays(14), 'is_active' => true,
    ]);
    EventService::applyFreeze($event);

    Livewire::actingAs($this->admin)->test(Events::class)->call('confirmDelete', $event->id);

    expect($this->enrollment->fresh()->expires_at->toDateString())
        ->toBe(today()->addDays(30)->toDateString());
});

it('skips event dates on the parent attendance calendar', function () {
    Event::create([
        'name' => 'Camp', 'start_date' => today()->subWeek(), 'end_date' => today()->addWeek(), 'is_active' => true,
    ]);

    $sessions = Livewire::actingAs($this->parent)->test(AttendanceHistory::class)->viewData('sessions');

    // No generated session should fall inside the event window.
    $inEvent = $sessions->filter(fn($s) =>
        $s['date']->betweenIncluded(today()->subWeek(), today()->addWeek())
    );
    expect($inEvent)->toHaveCount(0);
});
