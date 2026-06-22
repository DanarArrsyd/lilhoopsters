<?php
// tests/Feature/Admin/OwnerTest.php

use App\Livewire\Admin\Owner;
use App\Models\Child;
use App\Models\Coach;
use App\Models\CoachSession;
use App\Models\Enrollment;
use App\Models\Location;
use App\Models\Package;
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
