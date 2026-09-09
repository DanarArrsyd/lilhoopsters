<?php

use App\Livewire\Coach\QrScanner;
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
    $this->travelTo(now()->setTime(10, 0, 0));

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
        'day_of_week' => strtolower(now()->format('l')),
        'start_time'  => now()->subMinutes(10)->format('H:i:s'),
        'end_time'    => now()->addMinutes(50)->format('H:i:s'),
        'is_active'   => true,
    ]);

    $parentUser  = User::factory()->withRole('parent')->approved()->create();
    $this->child = Child::factory()->create(['user_id' => $parentUser->id]);

    CoachSession::create([
        'schedule_id'   => $this->schedule->id,
        'coach_id'      => $this->coach->id,
        'session_date'  => today(),
        'role'          => 'primary',
        'checked_in_at' => now(),
    ]);
});

it('burns one session from the quota when a present attendance is recorded', function () {
    $enrollment = Enrollment::factory()->program()->approved()->create([
        'child_id'           => $this->child->id,
        'schedule_id'        => $this->schedule->id,
        'total_sessions'     => 8,
        'remaining_sessions' => 8,
    ]);

    Livewire::actingAs($this->coachUser)
        ->test(QrScanner::class)
        ->set('scheduleId', $this->schedule->id)
        ->call('markPresent', $this->child->id);

    expect($enrollment->fresh()->remaining_sessions)->toBe(7);
});

it('also burns a session for a no_show — a missed slot without an excuse still costs one', function () {
    $enrollment = Enrollment::factory()->program()->approved()->create([
        'child_id'           => $this->child->id,
        'schedule_id'        => $this->schedule->id,
        'total_sessions'     => 8,
        'remaining_sessions' => 8,
    ]);

    Livewire::actingAs($this->coachUser)
        ->test(QrScanner::class)
        ->set('scheduleId', $this->schedule->id)
        ->call('markNoShow', $this->child->id);

    expect($enrollment->fresh()->remaining_sessions)->toBe(7);
});

it('restores the session when a present record is undone', function () {
    $enrollment = Enrollment::factory()->program()->approved()->create([
        'child_id'           => $this->child->id,
        'schedule_id'        => $this->schedule->id,
        'total_sessions'     => 8,
        'remaining_sessions' => 8,
    ]);

    $component = Livewire::actingAs($this->coachUser)
        ->test(QrScanner::class)
        ->set('scheduleId', $this->schedule->id);

    $component->call('markPresent', $this->child->id);
    expect($enrollment->fresh()->remaining_sessions)->toBe(7);

    $component->call('undoPresent', $this->child->id);
    expect($enrollment->fresh()->remaining_sessions)->toBe(8);
});

it('never lets the quota go negative once it hits zero', function () {
    $enrollment = Enrollment::factory()->program()->approved()->create([
        'child_id'           => $this->child->id,
        'schedule_id'        => $this->schedule->id,
        'total_sessions'     => 1,
        'remaining_sessions' => 0,
    ]);

    // Out of sessions — the enrollment is no longer "active", so QrScanner
    // must not offer or record attendance against it at all.
    Livewire::actingAs($this->coachUser)
        ->test(QrScanner::class)
        ->set('scheduleId', $this->schedule->id)
        ->call('markPresent', $this->child->id);

    expect($enrollment->fresh()->remaining_sessions)->toBe(0);
    expect(Attendance::where('child_id', $this->child->id)->count())->toBe(0);
});

it('refuses to scan a child whose package already expired by date', function () {
    Enrollment::factory()->program()->approved()->create([
        'child_id'    => $this->child->id,
        'schedule_id' => $this->schedule->id,
        'expires_at'  => now()->subDay(),
    ]);

    $result = Livewire::actingAs($this->coachUser)
        ->test(QrScanner::class)
        ->set('scheduleId', $this->schedule->id)
        ->call('processQr', $this->child->qr_identifier);

    $result->assertSet('lastScanStatus', 'not_enrolled');
    expect(Attendance::where('child_id', $this->child->id)->count())->toBe(0);
});

it('leaves an unlimited (date-only) package quota untouched', function () {
    $enrollment = Enrollment::factory()->program()->approved()->create([
        'child_id'           => $this->child->id,
        'schedule_id'        => $this->schedule->id,
        'total_sessions'     => null,
        'remaining_sessions' => null,
    ]);

    Livewire::actingAs($this->coachUser)
        ->test(QrScanner::class)
        ->set('scheduleId', $this->schedule->id)
        ->call('markPresent', $this->child->id);

    expect($enrollment->fresh()->remaining_sessions)->toBeNull();
});
