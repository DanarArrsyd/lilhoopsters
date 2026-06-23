<?php

use App\Livewire\Coach\CheckIn;
use App\Models\Coach;
use App\Models\CoachSession;
use App\Models\Location;
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
    $this->location  = Location::factory()->create(['is_active' => true]);

    // A regular schedule that falls on today, so the coach can check in.
    $this->schedule = Schedule::factory()->create([
        'location_id' => $this->location->id,
        'type'        => 'regular',
        'day_of_week' => strtolower(now()->format('l')),
        'is_active'   => true,
    ]);
});

it('renders the check-in page', function () {
    $this->actingAs($this->coachUser)
        ->get(route('coach.checkin'))
        ->assertOk();
});

it('can check in to a session as primary coach', function () {
    Livewire::actingAs($this->coachUser)
        ->test(CheckIn::class)
        ->call('checkIn', $this->schedule->id);

    $session = CoachSession::where('coach_id', $this->coach->id)
        ->where('schedule_id', $this->schedule->id)
        ->whereDate('session_date', today())
        ->first();

    expect($session)->not->toBeNull();
    expect($session->role)->toBe('primary');
});

it('cannot check in twice to the same session', function () {
    CoachSession::create([
        'schedule_id'   => $this->schedule->id,
        'coach_id'      => $this->coach->id,
        'session_date'  => today(),
        'role'          => 'primary',
        'checked_in_at' => now(),
    ]);

    Livewire::actingAs($this->coachUser)
        ->test(CheckIn::class)
        ->call('checkIn', $this->schedule->id);

    expect(CoachSession::where('coach_id', $this->coach->id)
        ->where('schedule_id', $this->schedule->id)
        ->whereDate('session_date', today())
        ->count())->toBe(1);
});

it('can check out of a session', function () {
    $session = CoachSession::create([
        'schedule_id'   => $this->schedule->id,
        'coach_id'      => $this->coach->id,
        'session_date'  => today(),
        'role'          => 'primary',
        'checked_in_at' => now(),
    ]);

    Livewire::actingAs($this->coachUser)
        ->test(CheckIn::class)
        ->call('checkOut', $session->id);

    expect($session->fresh()->checked_out_at)->not->toBeNull();
});

it('cannot check in to another coachs private schedule', function () {
    $otherCoachUser = User::factory()->withRole('coach')->approved()->create();
    $otherCoach     = Coach::factory()->create(['user_id' => $otherCoachUser->id]);

    $private = Schedule::factory()->create([
        'location_id' => $this->location->id,
        'type'        => 'private',
        'coach_id'    => $otherCoach->id,
        'day_of_week' => strtolower(now()->format('l')),
        'is_active'   => true,
    ]);

    Livewire::actingAs($this->coachUser)
        ->test(CheckIn::class)
        ->call('checkIn', $private->id);

    expect(CoachSession::where('coach_id', $this->coach->id)->count())->toBe(0);
});
