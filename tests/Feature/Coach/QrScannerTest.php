<?php

use App\Livewire\Coach\QrScanner;
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

    $this->schedule = Schedule::factory()->create([
        'coach_id'  => $this->coach->id,
        'is_active' => true,
    ]);

    $parentUser    = User::factory()->withRole('parent')->approved()->create();
    $this->child   = Child::factory()->create(['user_id' => $parentUser->id]);

    $this->enrollment = Enrollment::factory()->approved()->create([
        'child_id'    => $this->child->id,
        'schedule_id' => $this->schedule->id,
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
        ->set('scheduleId', $this->schedule->id)
        ->set('scanDate', now()->toDateString())
        ->call('activateScanner')
        ->assertSet('scannerActive', true);
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
        ->set('scheduleId', $this->schedule->id)
        ->set('scanDate', now()->toDateString())
        ->call('activateScanner')
        ->call('processQr', $this->child->qr_identifier);

    expect(Attendance::where('child_id', $this->child->id)->count())->toBe(1);
    expect(Attendance::first()->status)->toBe('present');
});

it('shows success message after valid scan', function () {
    Livewire::actingAs($this->coachUser)
        ->test(QrScanner::class)
        ->set('scheduleId', $this->schedule->id)
        ->set('scanDate', now()->toDateString())
        ->call('activateScanner')
        ->call('processQr', $this->child->qr_identifier)
        ->assertSet('lastScanStatus', 'success');
});

it('rejects unknown qr code', function () {
    Livewire::actingAs($this->coachUser)
        ->test(QrScanner::class)
        ->set('scheduleId', $this->schedule->id)
        ->set('scanDate', now()->toDateString())
        ->call('activateScanner')
        ->call('processQr', 'unknown-qr-value-xyz')
        ->assertSet('lastScanStatus', 'not_found');

    expect(Attendance::count())->toBe(0);
});

it('rejects child not enrolled in this schedule', function () {
    $otherChild = Child::factory()->create();

    Livewire::actingAs($this->coachUser)
        ->test(QrScanner::class)
        ->set('scheduleId', $this->schedule->id)
        ->set('scanDate', now()->toDateString())
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
        ->set('scheduleId', $this->schedule->id)
        ->set('scanDate', now()->toDateString())
        ->call('activateScanner')
        ->call('processQr', $this->child->qr_identifier)
        ->assertSet('lastScanStatus', 'duplicate');

    expect(Attendance::count())->toBe(1);
});
