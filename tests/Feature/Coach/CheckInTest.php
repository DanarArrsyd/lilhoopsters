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
    // Freeze to a mid-morning moment so schedule windows never wrap midnight.
    $this->travelTo(now()->setTime(10, 0, 0));

    Role::insert([
        ['name' => 'super_admin', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'admin',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'coach',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'parent',      'created_at' => now(), 'updated_at' => now()],
    ]);

    $this->coachUser = User::factory()->withRole('coach')->approved()->create();
    $this->coach     = Coach::factory()->create(['user_id' => $this->coachUser->id]);
    $this->location  = Location::factory()->create(['is_active' => true]);

    // A regular schedule happening right now, so the check-in window is open.
    $this->schedule = Schedule::factory()->create([
        'location_id' => $this->location->id,
        'type'        => 'regular',
        'day_of_week' => strtolower(now()->format('l')),
        'start_time'  => now()->subMinutes(10)->format('H:i:s'),
        'end_time'    => now()->addMinutes(50)->format('H:i:s'),
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
        'start_time'  => now()->subMinutes(10)->format('H:i:s'),
        'end_time'    => now()->addMinutes(50)->format('H:i:s'),
        'is_active'   => true,
    ]);

    Livewire::actingAs($this->coachUser)
        ->test(CheckIn::class)
        ->call('checkIn', $private->id);

    expect(CoachSession::where('coach_id', $this->coach->id)->count())->toBe(0);
});

it('blocks check-in more than 30 minutes before the schedule starts', function () {
    $early = Schedule::factory()->create([
        'location_id' => $this->location->id,
        'type'        => 'regular',
        'day_of_week' => strtolower(now()->format('l')),
        'start_time'  => now()->addHours(3)->format('H:i:s'),
        'end_time'    => now()->addHours(4)->format('H:i:s'),
        'is_active'   => true,
    ]);

    Livewire::actingAs($this->coachUser)
        ->test(CheckIn::class)
        ->call('checkIn', $early->id);

    expect(CoachSession::where('schedule_id', $early->id)->count())->toBe(0);
});

it('blocks check-in after the schedule has ended', function () {
    $ended = Schedule::factory()->create([
        'location_id' => $this->location->id,
        'type'        => 'regular',
        'day_of_week' => strtolower(now()->format('l')),
        'start_time'  => now()->subHours(3)->format('H:i:s'),
        'end_time'    => now()->subHours(2)->format('H:i:s'),
        'is_active'   => true,
    ]);

    Livewire::actingAs($this->coachUser)
        ->test(CheckIn::class)
        ->call('checkIn', $ended->id);

    expect(CoachSession::where('schedule_id', $ended->id)->count())->toBe(0);
});

it('auto-closes a stale open session at its scheduled end time', function () {
    $ended = Schedule::factory()->create([
        'location_id' => $this->location->id,
        'type'        => 'regular',
        'day_of_week' => strtolower(now()->format('l')),
        'start_time'  => now()->subHours(3)->format('H:i:s'),
        'end_time'    => now()->subHours(2)->format('H:i:s'),
        'is_active'   => true,
    ]);

    $session = CoachSession::create([
        'schedule_id'   => $ended->id,
        'coach_id'      => $this->coach->id,
        'session_date'  => today(),
        'role'          => 'primary',
        'checked_in_at' => now()->subHours(3),
    ]);

    CoachSession::autoCloseStale();

    $session->refresh();
    expect($session->checked_out_at)->not->toBeNull();
    expect($session->auto_closed)->toBeTrue();
});
