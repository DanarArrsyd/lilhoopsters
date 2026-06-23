<?php
// tests/Feature/ParentEmailNotificationTest.php

use App\Livewire\Admin\LeaveRequests as AdminLeaveRequests;
use App\Livewire\Admin\Payments;
use App\Mail\ParentNotification;
use App\Models\Attendance;
use App\Models\Child;
use App\Models\Coach;
use App\Models\Enrollment;
use App\Models\LeaveRequest;
use App\Models\Location;
use App\Models\Package;
use App\Models\Program;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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
    $this->parent   = User::factory()->withRole('parent')->approved()->create(['email' => 'parent@example.com']);
    $this->child    = Child::factory()->create(['user_id' => $this->parent->id]);

    $location       = Location::factory()->create();
    $program        = Program::factory()->create();
    $coach          = Coach::factory()->create();
    $this->package  = Package::factory()->regular()->create(['location_id' => $location->id]);
    $this->schedule = Schedule::factory()->create([
        'location_id' => $location->id,
        'program_id'  => $program->id,
        'coach_id'    => $coach->id,
    ]);
    $this->enrollment = Enrollment::factory()->program()->approved()->create([
        'child_id'    => $this->child->id,
        'schedule_id' => $this->schedule->id,
        'package_id'  => $this->package->id,
    ]);
});

it('emails a receipt to the parent when a payment is verified', function () {
    Mail::fake();

    $txn = Transaction::factory()->create([
        'user_id'    => $this->parent->id,
        'child_id'   => $this->child->id,
        'package_id' => $this->package->id,
        'amount'     => 300000,
        'status'     => 'pending',
    ]);

    Livewire::actingAs($this->admin)->test(Payments::class)->call('verify', $txn->id);

    Mail::assertQueued(ParentNotification::class, fn($m) => $m->hasTo('parent@example.com'));
});

it('emails the parent when their child checks in', function () {
    Mail::fake();

    Attendance::create([
        'child_id'      => $this->child->id,
        'enrollment_id' => $this->enrollment->id,
        'schedule_id'   => $this->schedule->id,
        'status'        => 'present',
        'source'        => 'qr',
        'attended_at'   => now(),
    ]);

    Mail::assertQueued(ParentNotification::class, fn($m) => $m->hasTo('parent@example.com'));
});

it('does not email on a non-present attendance', function () {
    Mail::fake();

    Attendance::create([
        'child_id'      => $this->child->id,
        'enrollment_id' => $this->enrollment->id,
        'schedule_id'   => $this->schedule->id,
        'status'        => 'no_show',
        'source'        => 'manual',
        'attended_at'   => now(),
    ]);

    Mail::assertNotQueued(ParentNotification::class);
});

it('emails the parent when a leave request is decided', function () {
    Mail::fake();

    $leave = LeaveRequest::create([
        'child_id'      => $this->child->id,
        'enrollment_id' => $this->enrollment->id,
        'schedule_id'   => $this->schedule->id,
        'leave_date'    => now()->toDateString(),
        'type'          => 'sick',
    ]);

    Livewire::actingAs($this->admin)->test(AdminLeaveRequests::class)
        ->call('openReview', $leave->id, 'approve')
        ->call('saveReview');

    Mail::assertQueued(ParentNotification::class, fn($m) => $m->hasTo('parent@example.com'));
});
