<?php

use App\Livewire\Coach\QrScanner;
use App\Models\Attendance;
use App\Models\Child;
use App\Models\Coach;
use App\Models\CoachSession;
use App\Models\Enrollment;
use App\Models\LeaveRequest;
use App\Models\MakeUpClass;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Freeze to mid-morning; live scanning is only allowed inside the window.
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

    $parentUser    = User::factory()->withRole('parent')->approved()->create();
    $this->child   = Child::factory()->create(['user_id' => $parentUser->id]);

    // Scan/marking requires an approved *program* enrollment for the schedule.
    $this->enrollment = Enrollment::factory()->program()->approved()->create([
        'child_id'    => $this->child->id,
        'schedule_id' => $this->schedule->id,
    ]);

    // activateScanner() now requires the coach to be checked in today.
    CoachSession::create([
        'schedule_id'   => $this->schedule->id,
        'coach_id'      => $this->coach->id,
        'session_date'  => today(),
        'role'          => 'primary',
        'checked_in_at' => now(),
    ]);
});

it('renders qr scanner page', function () {
    $this->actingAs($this->coachUser)
        ->get(route('coach.qr-scanner'))
        ->assertOk();
});

it('can activate scanner with valid schedule', function () {
    Livewire::actingAs($this->coachUser)
        ->test(QrScanner::class)
        ->set('scanDate', now()->toDateString())
        ->set('scheduleId', $this->schedule->id)
        ->call('activateScanner')
        ->assertSet('scannerActive', true);
});

it('cannot scan a regular schedule the coach did not check into, even after checking into another one', function () {
    // The coach checked into $this->schedule in beforeEach, but never
    // checked into this second regular schedule.
    $otherSchedule = Schedule::factory()->create([
        'day_of_week' => strtolower(now()->format('l')),
        'start_time'  => now()->subMinutes(10)->format('H:i:s'),
        'end_time'    => now()->addMinutes(50)->format('H:i:s'),
        'is_active'   => true,
        'type'        => 'regular',
    ]);

    Livewire::actingAs($this->coachUser)
        ->test(QrScanner::class)
        ->set('scanDate', now()->toDateString())
        ->set('scheduleId', $otherSchedule->id)
        ->call('activateScanner')
        ->assertForbidden();
});

it('refuses to mark a child no_show when they have an approved leave for that date', function () {
    LeaveRequest::create([
        'child_id'      => $this->child->id,
        'enrollment_id' => $this->enrollment->id,
        'schedule_id'   => $this->schedule->id,
        'leave_date'    => now()->toDateString(),
        'type'          => 'sick',
        'status'        => 'approved',
    ]);

    Livewire::actingAs($this->coachUser)
        ->test(QrScanner::class)
        ->set('scheduleId', $this->schedule->id)
        ->call('markNoShow', $this->child->id);

    expect(Attendance::where('child_id', $this->child->id)->count())->toBe(0);
});

it('checks in a child booked for an approved make-up class on this exact schedule + date, burning a session and completing the booking', function () {
    // A different child, NOT enrolled in $this->schedule, booked to make up
    // a missed session here today.
    $makeUpChild = Child::factory()->create();
    $originalEnrollment = Enrollment::factory()->program()->approved()->create([
        'child_id'           => $makeUpChild->id,
        'total_sessions'     => 8,
        'remaining_sessions' => 8,
    ]);
    $makeUpClass = MakeUpClass::factory()->approved()->create([
        'child_id'           => $makeUpChild->id,
        'enrollment_id'      => $originalEnrollment->id,
        'target_schedule_id' => $this->schedule->id,
        'target_date'        => now()->toDateString(),
    ]);

    Livewire::actingAs($this->coachUser)
        ->test(QrScanner::class)
        ->set('scheduleId', $this->schedule->id)
        ->call('processQr', $makeUpChild->qr_identifier)
        ->assertSet('lastScanStatus', 'success');

    $attendance = Attendance::where('child_id', $makeUpChild->id)->first();
    expect($attendance)->not->toBeNull();
    expect($attendance->status)->toBe('make_up');
    expect($attendance->make_up_class_id)->toBe($makeUpClass->id);
    expect($attendance->enrollment_id)->toBe($originalEnrollment->id);

    // Attending burns a session on the ORIGINAL enrollment, same as present/no_show would.
    expect($originalEnrollment->fresh()->remaining_sessions)->toBe(7);

    // The booking is closed out — it shouldn't sit "approved" forever.
    expect($makeUpClass->fresh()->status)->toBe('completed');
});

it('reopens a make-up booking and restores the session when its attendance is undone', function () {
    $makeUpChild = Child::factory()->create();
    $originalEnrollment = Enrollment::factory()->program()->approved()->create([
        'child_id'           => $makeUpChild->id,
        'total_sessions'     => 8,
        'remaining_sessions' => 8,
    ]);
    $makeUpClass = MakeUpClass::factory()->approved()->create([
        'child_id'           => $makeUpChild->id,
        'enrollment_id'      => $originalEnrollment->id,
        'target_schedule_id' => $this->schedule->id,
        'target_date'        => now()->toDateString(),
    ]);

    $component = Livewire::actingAs($this->coachUser)
        ->test(QrScanner::class)
        ->set('scheduleId', $this->schedule->id);

    $component->call('processQr', $makeUpChild->qr_identifier);
    expect($makeUpClass->fresh()->status)->toBe('completed');
    expect($originalEnrollment->fresh()->remaining_sessions)->toBe(7);

    $component->call('undoPresent', $makeUpChild->id);
    expect($makeUpClass->fresh()->status)->toBe('approved');
    expect($originalEnrollment->fresh()->remaining_sessions)->toBe(8);
});

it('cannot activate the scanner after the session has ended today', function () {
    $ended = Schedule::factory()->create([
        'coach_id'    => $this->coach->id,
        'day_of_week' => strtolower(now()->format('l')),
        'start_time'  => now()->subHours(3)->format('H:i:s'),
        'end_time'    => now()->subHours(2)->format('H:i:s'),
        'is_active'   => true,
    ]);

    CoachSession::create([
        'schedule_id'   => $ended->id,
        'coach_id'      => $this->coach->id,
        'session_date'  => today(),
        'role'          => 'primary',
        'checked_in_at' => now()->subHours(3),
    ]);

    Livewire::actingAs($this->coachUser)
        ->test(QrScanner::class)
        ->set('scanDate', now()->toDateString())
        ->set('scheduleId', $ended->id)
        ->call('activateScanner')
        ->assertSet('scannerActive', false);
});

it('requires schedule to activate scanner', function () {
    Livewire::actingAs($this->coachUser)
        ->test(QrScanner::class)
        ->call('activateScanner')
        ->assertHasErrors(['scheduleId'])
        ->assertSet('scannerActive', false);
});

it('records attendance on valid qr scan', function () {
    Livewire::actingAs($this->coachUser)
        ->test(QrScanner::class)
        ->set('scanDate', now()->toDateString())
        ->set('scheduleId', $this->schedule->id)
        ->call('activateScanner')
        ->call('processQr', $this->child->qr_identifier);

    expect(Attendance::where('child_id', $this->child->id)->count())->toBe(1);
    expect(Attendance::first()->status)->toBe('present');
});

it('shows success message after valid scan', function () {
    Livewire::actingAs($this->coachUser)
        ->test(QrScanner::class)
        ->set('scanDate', now()->toDateString())
        ->set('scheduleId', $this->schedule->id)
        ->call('activateScanner')
        ->call('processQr', $this->child->qr_identifier)
        ->assertSet('lastScanStatus', 'success');
});

it('rejects unknown qr code', function () {
    Livewire::actingAs($this->coachUser)
        ->test(QrScanner::class)
        ->set('scanDate', now()->toDateString())
        ->set('scheduleId', $this->schedule->id)
        ->call('activateScanner')
        ->call('processQr', 'unknown-qr-value-xyz')
        ->assertSet('lastScanStatus', 'not_found');

    expect(Attendance::count())->toBe(0);
});

it('rejects child not enrolled in this schedule', function () {
    $otherChild = Child::factory()->create();

    Livewire::actingAs($this->coachUser)
        ->test(QrScanner::class)
        ->set('scanDate', now()->toDateString())
        ->set('scheduleId', $this->schedule->id)
        ->call('activateScanner')
        ->call('processQr', $otherChild->qr_identifier)
        ->assertSet('lastScanStatus', 'not_enrolled');

    expect(Attendance::count())->toBe(0);
});

it('rejects duplicate scan on same date', function () {
    Attendance::create([
        'child_id'      => $this->child->id,
        'enrollment_id' => $this->enrollment->id,
        'schedule_id'   => $this->schedule->id,
        'attended_at'   => now()->toDateString(),
        'status'        => 'present',
        'source'        => 'manual',
    ]);

    Livewire::actingAs($this->coachUser)
        ->test(QrScanner::class)
        ->set('scanDate', now()->toDateString())
        ->set('scheduleId', $this->schedule->id)
        ->call('activateScanner')
        ->call('processQr', $this->child->qr_identifier)
        ->assertSet('lastScanStatus', 'duplicate');

    expect(Attendance::count())->toBe(1);
});
