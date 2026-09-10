<?php

use App\Models\Child;
use App\Models\Enrollment;
use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::insert([
        ['name' => 'super_admin', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'admin',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'coach',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'parent',      'created_at' => now(), 'updated_at' => now()],
    ]);

    $parentUser  = User::factory()->withRole('parent')->approved()->create();
    $this->child = Child::factory()->create(['user_id' => $parentUser->id]);
    $this->enrollment = Enrollment::factory()->approved()->create(['child_id' => $this->child->id]);
});

it('auto-approves a pending leave request whose review window has passed', function () {
    $leaveRequest = LeaveRequest::create([
        'child_id'        => $this->child->id,
        'enrollment_id'   => $this->enrollment->id,
        'schedule_id'     => $this->enrollment->schedule_id ?? \App\Models\Schedule::factory()->create()->id,
        'leave_date'      => now()->toDateString(),
        'type'            => 'sick',
        'status'          => 'pending',
        'auto_approve_at' => now()->subHour(), // window already passed
    ]);

    $this->artisan('leaves:auto-approve')->assertSuccessful();

    expect($leaveRequest->fresh()->status)->toBe('auto_approved');
    expect($leaveRequest->fresh()->reviewed_at)->not->toBeNull();
});

it('leaves a pending request untouched while its review window is still open', function () {
    $leaveRequest = LeaveRequest::create([
        'child_id'        => $this->child->id,
        'enrollment_id'   => $this->enrollment->id,
        'schedule_id'     => $this->enrollment->schedule_id ?? \App\Models\Schedule::factory()->create()->id,
        'leave_date'      => now()->toDateString(),
        'type'            => 'sick',
        'status'          => 'pending',
        'auto_approve_at' => now()->addHour(), // window not passed yet
    ]);

    $this->artisan('leaves:auto-approve')->assertSuccessful();

    expect($leaveRequest->fresh()->status)->toBe('pending');
});

it('does not touch an already-reviewed leave request even if its window passed', function () {
    $leaveRequest = LeaveRequest::create([
        'child_id'        => $this->child->id,
        'enrollment_id'   => $this->enrollment->id,
        'schedule_id'     => $this->enrollment->schedule_id ?? \App\Models\Schedule::factory()->create()->id,
        'leave_date'      => now()->toDateString(),
        'type'            => 'sick',
        'status'          => 'rejected',
        'auto_approve_at' => now()->subHour(),
    ]);

    $this->artisan('leaves:auto-approve')->assertSuccessful();

    expect($leaveRequest->fresh()->status)->toBe('rejected');
});
