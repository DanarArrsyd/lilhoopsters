<?php

use App\Models\Child;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\Location;
use App\Models\Package;
use App\Models\Program;
use App\Models\Role;
use App\Models\Schedule;
use App\Services\EventService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::insert([
        ['name' => 'super_admin', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'admin',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'coach',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'parent',      'created_at' => now(), 'updated_at' => now()],
    ]);

    $this->location = Location::factory()->create();
    $this->program  = Program::factory()->create();
    $this->schedule = Schedule::factory()->create([
        'location_id' => $this->location->id,
        'program_id'  => $this->program->id,
    ]);
    $this->package  = Package::factory()->regular()->create(['location_id' => $this->location->id]);

    $this->event = Event::factory()->create([
        'location_id' => $this->location->id,
        'program_id'  => $this->program->id,
        'start_date'  => today()->addWeek(),
        'end_date'    => today()->addWeeks(3),
        'is_active'   => true,
    ]);

    $this->enrollment = Enrollment::factory()->program()->approved()->create([
        'child_id'    => Child::factory()->create()->id,
        'package_id'  => $this->package->id,
        'schedule_id' => $this->schedule->id,
        'expires_at'  => today()->addWeeks(2),
    ]);
});

it('pushes expiry forward by the event length for an affected enrollment', function () {
    $originalExpiry = $this->enrollment->expires_at;

    $count = EventService::applyFreeze($this->event);

    expect($count)->toBe(1);
    expect($this->enrollment->fresh()->expires_at->toDateString())
        ->toBe($originalExpiry->copy()->addDays($this->event->dayCount())->toDateString());
});

it('is idempotent — calling applyFreeze twice does not double-shift expiry', function () {
    EventService::applyFreeze($this->event);
    $afterFirst = $this->enrollment->fresh()->expires_at;

    EventService::applyFreeze($this->event);
    $afterSecond = $this->enrollment->fresh()->expires_at;

    expect($afterSecond->toDateString())->toBe($afterFirst->toDateString());
});

it('reverses the freeze back to the original expiry', function () {
    $originalExpiry = $this->enrollment->expires_at->toDateString();

    EventService::applyFreeze($this->event);
    expect($this->enrollment->fresh()->expires_at->toDateString())->not->toBe($originalExpiry);

    EventService::reverseFreeze($this->event);
    expect($this->enrollment->fresh()->expires_at->toDateString())->toBe($originalExpiry);
});
