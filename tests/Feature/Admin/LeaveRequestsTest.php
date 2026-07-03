<?php

use App\Livewire\Admin\LeaveRequests;
use App\Models\AuditLog;
use App\Models\Child;
use App\Models\Enrollment;
use App\Models\LeaveRequest;
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

    $this->admin    = User::factory()->withRole('admin')->approved()->create();
    $this->child    = Child::factory()->create();
    $this->schedule = Schedule::factory()->create();
    $this->enrollment = Enrollment::factory()->approved()->create([
        'child_id'    => $this->child->id,
        'schedule_id' => $this->schedule->id,
    ]);

    $this->leaveRequest = LeaveRequest::factory()->create([
        'child_id'      => $this->child->id,
        'enrollment_id' => $this->enrollment->id,
        'schedule_id'   => $this->schedule->id,
        'status'        => 'pending',
    ]);
});

it('renders leave requests page', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.leave-requests'))
        ->assertOk();
});

it('shows pending leave requests by default', function () {
    Livewire::actingAs($this->admin)
        ->test(LeaveRequests::class)
        ->assertSee($this->child->name);
});

it('can approve a leave request', function () {
    Livewire::actingAs($this->admin)
        ->test(LeaveRequests::class)
        ->call('openReview', $this->leaveRequest->id, 'approve')
        ->assertSet('showReview', true)
        ->set('adminNotes', 'OK disetujui')
        ->call('saveReview');

    expect($this->leaveRequest->fresh()->status)->toBe('approved');
    expect($this->leaveRequest->fresh()->reviewed_by)->toBe($this->admin->id);
    expect($this->leaveRequest->fresh()->admin_notes)->toBe('OK disetujui');
});

it('can reject a leave request', function () {
    Livewire::actingAs($this->admin)
        ->test(LeaveRequests::class)
        ->call('openReview', $this->leaveRequest->id, 'reject')
        ->call('saveReview');

    expect($this->leaveRequest->fresh()->status)->toBe('rejected');
});

it('can filter by status tab', function () {
    $approved = LeaveRequest::factory()->approved()->create([
        'child_id'      => $this->child->id,
        'enrollment_id' => $this->enrollment->id,
    ]);

    Livewire::actingAs($this->admin)
        ->test(LeaveRequests::class)
        ->set('filterStatus', 'approved')
        ->assertSee($this->child->name);
});

it('can search by child name', function () {
    $otherChild = Child::factory()->create(['name' => 'Budi Santoso']);
    $otherEnroll = Enrollment::factory()->approved()->create(['child_id' => $otherChild->id]);
    LeaveRequest::factory()->create(['child_id' => $otherChild->id, 'enrollment_id' => $otherEnroll->id, 'status' => 'pending']);

    Livewire::actingAs($this->admin)
        ->test(LeaveRequests::class)
        ->set('search', 'Budi')
        ->assertSee('Budi Santoso')
        ->assertDontSee($this->child->name);
});

it('records an audit log when a leave request is approved', function () {
    $leaveRequest = LeaveRequest::factory()->create(['status' => 'pending']);

    Livewire::actingAs($this->admin)
        ->test(LeaveRequests::class)
        ->call('openReview', $leaveRequest->id, 'approve')
        ->call('saveReview');

    $log = AuditLog::where('action', 'leave_request.approved')->first();
    expect($log)->not->toBeNull();
    expect($log->subject_id)->toBe($leaveRequest->id);
});

it('records an audit log when a leave request is rejected', function () {
    $leaveRequest = LeaveRequest::factory()->create(['status' => 'pending']);

    Livewire::actingAs($this->admin)
        ->test(LeaveRequests::class)
        ->call('openReview', $leaveRequest->id, 'reject')
        ->call('saveReview');

    $log = AuditLog::where('action', 'leave_request.rejected')->first();
    expect($log)->not->toBeNull();
    expect($log->subject_id)->toBe($leaveRequest->id);
});
