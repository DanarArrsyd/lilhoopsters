<?php

use App\Livewire\Coach\Roster;
use App\Models\Attendance;
use App\Models\Child;
use App\Models\Coach;
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
    $this->schedule  = Schedule::factory()->create(['coach_id' => $this->coach->id, 'is_active' => true]);
    $this->child     = Child::factory()->create();
    $this->enrollment = Enrollment::factory()->program()->approved()->create([
        'child_id'    => $this->child->id,
        'schedule_id' => $this->schedule->id,
    ]);
});

it('renders the roster page', function () {
    $this->actingAs($this->coachUser)
        ->get(route('coach.roster'))
        ->assertOk();
});

it('shows enrolled students for a schedule', function () {
    Livewire::actingAs($this->coachUser)
        ->test(Roster::class)
        ->set('scheduleId', $this->schedule->id)
        ->set('date', now()->toDateString())
        ->assertSee($this->child->name);
});

it('shows recorded attendance status', function () {
    Attendance::factory()->present()->create([
        'child_id'    => $this->child->id,
        'schedule_id' => $this->schedule->id,
        'attended_at' => now(),
    ]);

    Livewire::actingAs($this->coachUser)
        ->test(Roster::class)
        ->set('scheduleId', $this->schedule->id)
        ->set('date', now()->toDateString())
        ->assertSee('Present');
});

it('shows not recorded for students without attendance', function () {
    Livewire::actingAs($this->coachUser)
        ->test(Roster::class)
        ->set('scheduleId', $this->schedule->id)
        ->set('date', now()->toDateString())
        ->assertSee('Not recorded');
});

it('cannot view roster for other coach schedule', function () {
    $other = Coach::factory()->create(['user_id' => User::factory()->withRole('coach')->approved()->create()->id]);
    // Private schedules are owner-only; a regular one is shared and viewable.
    $otherSchedule = Schedule::factory()->create(['coach_id' => $other->id, 'type' => 'private']);

    Livewire::actingAs($this->coachUser)
        ->test(Roster::class)
        ->set('scheduleId', $otherSchedule->id)
        ->assertForbidden();
});
