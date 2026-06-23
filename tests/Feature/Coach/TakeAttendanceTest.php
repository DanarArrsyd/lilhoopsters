<?php

use App\Livewire\Coach\TakeAttendance;
use App\Models\Attendance;
use App\Models\Child;
use App\Models\Coach;
use App\Models\CoachSession;
use App\Models\Enrollment;
use App\Models\Role;
use App\Models\Schedule;
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

    $this->schedule = Schedule::factory()->create([
        'coach_id'    => $this->coach->id,
        'day_of_week' => 'monday',
        'is_active'   => true,
    ]);

    $this->child1 = Child::factory()->create();
    $this->child2 = Child::factory()->create();

    $this->enrollment1 = Enrollment::factory()->approved()->create([
        'child_id'    => $this->child1->id,
        'schedule_id' => $this->schedule->id,
    ]);
    $this->enrollment2 = Enrollment::factory()->approved()->create([
        'child_id'    => $this->child2->id,
        'schedule_id' => $this->schedule->id,
    ]);

    // Taking attendance on a regular schedule requires the coach to be checked
    // in today (authorizeCoach), so create that session.
    CoachSession::create([
        'schedule_id'   => $this->schedule->id,
        'coach_id'      => $this->coach->id,
        'session_date'  => today(),
        'role'          => 'primary',
        'checked_in_at' => now(),
    ]);
});

it('renders the attendance page', function () {
    $this->actingAs($this->coachUser)
        ->get(route('coach.attendance'))
        ->assertOk();
});

it('loads roster of approved students for a schedule', function () {
    Livewire::actingAs($this->coachUser)
        ->test(TakeAttendance::class)
        ->set('scheduleId', $this->schedule->id)
        ->set('date', now()->toDateString())
        ->call('loadRoster')
        ->assertSee($this->child1->name)
        ->assertSee($this->child2->name);
});

it('does not load roster for schedules from other coaches', function () {
    $otherCoachUser = User::factory()->withRole('coach')->approved()->create();
    $otherCoach     = Coach::factory()->create(['user_id' => $otherCoachUser->id]);
    $otherSchedule  = Schedule::factory()->create(['coach_id' => $otherCoach->id]);

    Livewire::actingAs($this->coachUser)
        ->test(TakeAttendance::class)
        ->set('scheduleId', $otherSchedule->id)
        ->assertForbidden();
});

it('can set student status', function () {
    $component = Livewire::actingAs($this->coachUser)
        ->test(TakeAttendance::class)
        ->set('scheduleId', $this->schedule->id)
        ->set('date', now()->toDateString())
        ->call('loadRoster');

    $component->call('setStatus', $this->child1->id, 'no_show');

    $roster = $component->get('roster');
    $row = collect($roster)->firstWhere('child_id', $this->child1->id);
    expect($row['status'])->toBe('no_show');
});

it('saves attendance records', function () {
    Livewire::actingAs($this->coachUser)
        ->test(TakeAttendance::class)
        ->set('scheduleId', $this->schedule->id)
        ->set('date', now()->toDateString())
        ->call('loadRoster')
        ->call('setStatus', $this->child1->id, 'present')
        ->call('setStatus', $this->child2->id, 'no_show')
        ->call('saveAttendance');

    expect(Attendance::count())->toBe(2);
    expect(
        Attendance::where('child_id', $this->child2->id)->value('status')
    )->toBe('no_show');
});

it('updates existing attendance on re-save', function () {
    Attendance::create([
        'child_id'      => $this->child1->id,
        'enrollment_id' => $this->enrollment1->id,
        'schedule_id'   => $this->schedule->id,
        'coach_id'      => $this->coach->id,
        'status'        => 'present',
        'source'        => 'manual',
        'attended_at'   => now()->toDateString(),
    ]);

    Livewire::actingAs($this->coachUser)
        ->test(TakeAttendance::class)
        ->set('scheduleId', $this->schedule->id)
        ->set('date', now()->toDateString())
        ->call('loadRoster')
        ->call('setStatus', $this->child1->id, 'sick')
        ->call('saveAttendance');

    expect(Attendance::where('child_id', $this->child1->id)->value('status'))->toBe('sick');
    expect(Attendance::where('child_id', $this->child1->id)->count())->toBe(1);
});

it('pre-fills status from existing attendance when loading roster', function () {
    Attendance::create([
        'child_id'      => $this->child1->id,
        'enrollment_id' => $this->enrollment1->id,
        'schedule_id'   => $this->schedule->id,
        'coach_id'      => $this->coach->id,
        'status'        => 'permit',
        'source'        => 'manual',
        'attended_at'   => now()->toDateString(),
    ]);

    $component = Livewire::actingAs($this->coachUser)
        ->test(TakeAttendance::class)
        ->set('scheduleId', $this->schedule->id)
        ->set('date', now()->toDateString())
        ->call('loadRoster');

    $roster = $component->get('roster');
    $row = collect($roster)->firstWhere('child_id', $this->child1->id);
    expect($row['status'])->toBe('permit');
});

it('excludes pending enrollments from roster', function () {
    $pendingChild = Child::factory()->create();
    Enrollment::factory()->create([
        'child_id'    => $pendingChild->id,
        'schedule_id' => $this->schedule->id,
        'status'      => 'pending',
    ]);

    Livewire::actingAs($this->coachUser)
        ->test(TakeAttendance::class)
        ->set('scheduleId', $this->schedule->id)
        ->set('date', now()->toDateString())
        ->call('loadRoster')
        ->assertDontSee($pendingChild->name);
});
