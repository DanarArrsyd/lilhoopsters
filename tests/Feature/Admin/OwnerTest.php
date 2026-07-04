<?php
// tests/Feature/Admin/OwnerTest.php

use App\Livewire\Admin\Owner;
use App\Models\Attendance;
use App\Models\Child;
use App\Models\Coach;
use App\Models\CoachSession;
use App\Models\Enrollment;
use App\Models\Location;
use App\Models\Package;
use App\Models\Program;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\Transaction;
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

    $this->admin    = User::factory()->withRole('admin')->approved()->create();
    $this->location = Location::factory()->create();
    $this->package  = Package::factory()->regular()->create(['location_id' => $this->location->id]);
});

it('renders owner insights page for admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.owner'))
        ->assertOk();
});

it('non-admin cannot access owner insights', function () {
    $parent = User::factory()->withRole('parent')->approved()->create();

    $this->actingAs($parent)
        ->get(route('admin.owner'))
        ->assertForbidden();
});

it('shows owner insights link in sidebar', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.reports'))
        ->assertSee(route('admin.owner'));
});

it('counts active members and flags expiring enrollments', function () {
    $child = Child::factory()->create();
    Enrollment::factory()->program()->approved()->create([
        'child_id'           => $child->id,
        'package_id'         => $this->package->id,
        'expires_at'         => today()->addDays(5),   // within 14 days → expiring
        'remaining_sessions' => 2,
    ]);

    $renewal = Livewire::actingAs($this->admin)->test(Owner::class)->viewData('renewal');

    expect($renewal['active_members'])->toBe(1);
    expect($renewal['expiring_count'])->toBe(1);
    expect($renewal['expiring_list'])->toHaveCount(1);
});

it('excludes expired enrollments from active members', function () {
    $child = Child::factory()->create();
    Enrollment::factory()->program()->approved()->create([
        'child_id'           => $child->id,
        'package_id'         => $this->package->id,
        'expires_at'         => today()->subDay(),    // already lapsed
        'remaining_sessions' => 0,
    ]);

    $renewal = Livewire::actingAs($this->admin)->test(Owner::class)->viewData('renewal');

    expect($renewal['active_members'])->toBe(0);
});

it('sums outstanding pending payments', function () {
    Transaction::factory()->create([            // pending (factory default)
        'package_id' => $this->package->id,
        'amount'     => 150000,
    ]);
    Transaction::factory()->paid()->create([    // paid — excluded
        'package_id' => $this->package->id,
        'amount'     => 999000,
        'paid_at'    => now(),
    ]);

    $ar = Livewire::actingAs($this->admin)->test(Owner::class)->viewData('ar');

    expect($ar['outstanding'])->toBe(150000);
    expect($ar['count'])->toBe(1);
});

it('aggregates coach sessions and hours for the selected month', function () {
    $coach    = Coach::factory()->create();
    $schedule = Schedule::factory()->create(['location_id' => $this->location->id]);

    CoachSession::create([
        'schedule_id'    => $schedule->id,
        'coach_id'       => $coach->id,
        'session_date'   => now()->startOfMonth()->addDays(2)->toDateString(),
        'role'           => 'primary',
        'checked_in_at'  => now()->startOfMonth()->addDays(2)->setTime(9, 0),
        'checked_out_at' => now()->startOfMonth()->addDays(2)->setTime(11, 0),  // 2 hours
    ]);

    $payroll = Livewire::actingAs($this->admin)->test(Owner::class)->viewData('payroll');

    expect($payroll)->toHaveCount(1);
    expect($payroll->first()['sessions'])->toBe(1);
    expect($payroll->first()['hours'])->toBe(2.0);
});

it('computes class capacity utilization', function () {
    $schedule = Schedule::factory()->create([
        'location_id'  => $this->location->id,
        'max_capacity' => 10,
    ]);

    Enrollment::factory()->program()->approved()->count(4)->create([
        'schedule_id' => $schedule->id,
        'package_id'  => $this->package->id,
    ]);

    $capacity = Livewire::actingAs($this->admin)->test(Owner::class)->viewData('capacity');

    expect($capacity['total_cap'])->toBe(10);
    expect($capacity['total_book'])->toBe(4);
    expect($capacity['overall'])->toBe(40.0);
});

it('ranks action center by money impact, outstanding above renewal risk', function () {
    // Outstanding Rp 500k (pending transaction)
    Transaction::factory()->create([
        'package_id' => $this->package->id,
        'amount'     => 500000,
        'status'     => 'pending',
    ]);

    // Expiring enrollment worth Rp 200k (renewal value)
    $pkg   = Package::factory()->regular()->create(['location_id' => $this->location->id, 'price' => 200000]);
    $child = Child::factory()->create();
    Enrollment::factory()->program()->approved()->create([
        'child_id'           => $child->id,
        'package_id'         => $pkg->id,
        'expires_at'         => today()->addDays(5),
        'remaining_sessions' => 2,
    ]);

    $insights = Livewire::actingAs($this->admin)->test(Owner::class)->viewData('insights');

    expect($insights['actions'])->not->toBeEmpty();
    // 500k outstanding ranks above 200k renewal risk
    expect($insights['actions'][0]['money'])->toBe(500000);
    expect($insights['actions'][1]['money'])->toBe(200000);
});

it('flags cashflow as bad when an invoice is overdue', function () {
    Transaction::factory()->create([
        'package_id' => $this->package->id,
        'amount'     => 150000,
        'status'     => 'pending',
        'expired_at' => now()->subDay(), // past due
    ]);

    $insights = Livewire::actingAs($this->admin)->test(Owner::class)->viewData('insights');

    $cashflow = collect($insights['health'])->firstWhere('key', 'cashflow');
    expect($cashflow['status'])->toBe('bad');
    expect($insights['actions'][0]['severity'])->toBe('danger');
});

it('computes the 30-day revenue trend', function () {
    Transaction::factory()->paid()->create([
        'package_id' => $this->package->id,
        'amount'     => 1000000,
        'paid_at'    => now()->subDays(5),   // current window
    ]);
    Transaction::factory()->paid()->create([
        'package_id' => $this->package->id,
        'amount'     => 500000,
        'paid_at'    => now()->subDays(45),  // previous window
    ]);

    $insights = Livewire::actingAs($this->admin)->test(Owner::class)->viewData('insights');

    expect($insights['trends']['revenue']['value'])->toBe(1000000);
    expect($insights['trends']['revenue']['delta']['dir'])->toBe('up');
    expect($insights['trends']['revenue']['delta']['pct'])->toBe(100.0);
});

it('shows no actions when everything is clear', function () {
    $insights = Livewire::actingAs($this->admin)->test(Owner::class)->viewData('insights');

    expect($insights['actions'])->toBeEmpty();
});

it('shows the attendance tab with overall rate and breakdowns', function () {
    $program  = Program::factory()->create(['name' => 'Junior League']);
    $location = Location::factory()->create(['name' => 'Cikarang Court']);
    $schedule = Schedule::factory()->create([
        'program_id'  => $program->id,
        'location_id' => $location->id,
    ]);

    Attendance::factory()->present()->create(['schedule_id' => $schedule->id, 'attended_at' => now()]);
    Attendance::factory()->present()->create(['schedule_id' => $schedule->id, 'attended_at' => now()]);
    Attendance::factory()->present()->create(['schedule_id' => $schedule->id, 'attended_at' => now()]);
    Attendance::factory()->absent()->create(['schedule_id' => $schedule->id, 'attended_at' => now()]);

    Livewire::actingAs($this->admin)
        ->test(Owner::class)
        ->assertSee('Junior League')
        ->assertSee('Cikarang Court')
        ->assertSee('75%'); // 3 present / 4 total
});

it('excludes make-up attendances from the attendance rate', function () {
    $schedule = Schedule::factory()->create();

    Attendance::factory()->present()->create(['schedule_id' => $schedule->id, 'attended_at' => now()]);
    Attendance::factory()->create(['schedule_id' => $schedule->id, 'attended_at' => now(), 'status' => 'make_up']);

    $attendance = Livewire::actingAs($this->admin)->test(Owner::class)->viewData('attendance');

    expect($attendance['overall'])->toBe(100.0); // 1 present / 1 total, make_up excluded from both sides
    expect($attendance['total'])->toBe(1);
});

it('shows an empty state when there is no attendance in the last 30 days', function () {
    Livewire::actingAs($this->admin)
        ->test(Owner::class)
        ->assertSee('No attendance records in the last 30 days');
});
