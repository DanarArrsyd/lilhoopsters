<?php

use App\Models\Child;
use App\Models\Enrollment;
use App\Models\Location;
use App\Models\Program;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\User;
use App\Support\ChildSchedulePlanner;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-07-06')); // a Monday

    Role::insert([
        ['name' => 'super_admin', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'admin',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'coach',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'parent',      'created_at' => now(), 'updated_at' => now()],
    ]);

    $this->parentUser = User::factory()->withRole('parent')->approved()->create();
    $this->child = Child::factory()->create(['user_id' => $this->parentUser->id, 'status' => 'active']);

    $location = Location::factory()->create();
    $program  = Program::factory()->create();
    $this->schedule = Schedule::factory()->create([
        'location_id' => $location->id,
        'program_id'  => $program->id,
        'day_of_week' => 'monday',
        'start_time'  => '16:00:00',
        'end_time'    => '17:00:00',
        'is_active'   => true,
    ]);
});

afterEach(fn () => Carbon::setTestNow());

it('returns the next valid session for a child with an active enrollment', function () {
    Enrollment::factory()->create([
        'child_id'    => $this->child->id,
        'schedule_id' => $this->schedule->id,
        'type'        => 'program',
        'status'      => 'approved',
        'started_at'  => '2026-06-01',
        'expires_at'  => '2026-12-31',
    ]);

    $next = ChildSchedulePlanner::nextSession($this->child->fresh());

    expect($next)->not->toBeNull()
        ->and($next['program'])->toBe($this->schedule->program->name)
        ->and($next['date']->toDateString())->toBe('2026-07-06');
});

it('returns null when the child has no approved program enrollment', function () {
    expect(ChildSchedulePlanner::nextSession($this->child->fresh()))->toBeNull();
});

it('excludes sessions before the enrollment start date', function () {
    Enrollment::factory()->create([
        'child_id'    => $this->child->id,
        'schedule_id' => $this->schedule->id,
        'type'        => 'program',
        'status'      => 'approved',
        'started_at'  => '2026-07-13', // next Monday
        'expires_at'  => '2026-12-31',
    ]);

    $next = ChildSchedulePlanner::nextSession($this->child->fresh());

    expect($next['date']->toDateString())->toBe('2026-07-13');
});

it('builds a week map of sessions keyed by day name', function () {
    Enrollment::factory()->create([
        'child_id'    => $this->child->id,
        'schedule_id' => $this->schedule->id,
        'type'        => 'program',
        'status'      => 'approved',
        'started_at'  => '2026-06-01',
        'expires_at'  => '2026-12-31',
    ]);

    $week = ChildSchedulePlanner::weekSessions($this->child->fresh());

    expect($week->has('monday'))->toBeTrue()
        ->and($week->get('monday'))->toHaveCount(1);
});
