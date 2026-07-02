<?php

use App\Livewire\Portal\LeaveRequests;
use App\Models\Child;
use App\Models\Coach;
use App\Models\Enrollment;
use App\Models\LeaveRequest;
use App\Models\Location;
use App\Models\Package;
use App\Models\Program;
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

    $this->parent = User::factory()->withRole('parent')->approved()->create();

    $location        = Location::factory()->create();
    $program         = Program::factory()->create();
    $coachUser       = User::factory()->withRole('coach')->approved()->create();
    $coach           = Coach::factory()->create(['user_id' => $coachUser->id]);
    $this->schedule  = Schedule::factory()->create([
        'location_id' => $location->id,
        'program_id'  => $program->id,
        'coach_id'    => $coach->id,
        'is_active'   => true,
    ]);
    $package         = Package::factory()->regular()->create(['location_id' => $location->id]);

    $this->child      = Child::factory()->active()->create(['user_id' => $this->parent->id]);
    $this->enrollment = Enrollment::factory()->approved()->create([
        'child_id'    => $this->child->id,
        'schedule_id' => $this->schedule->id,
        'package_id'  => $package->id,
        'type'        => 'program',
    ]);
});

it('shows only own childrens leave requests', function () {
    LeaveRequest::create([
        'child_id'      => $this->child->id,
        'enrollment_id' => $this->enrollment->id,
        'schedule_id'   => $this->schedule->id,
        'leave_date'    => now()->subDay()->format('Y-m-d'),
        'type'          => 'sick',
    ]);

    $other = User::factory()->withRole('parent')->approved()->create();
    Child::factory()->active()->create(['user_id' => $other->id, 'name' => 'Outsider Kid']);

    Livewire::actingAs($this->parent)
        ->test(LeaveRequests::class)
        ->assertSee(now()->subDay()->format('d M Y'))
        ->assertDontSee('Outsider Kid');
});

it('can submit a sick leave request', function () {
    // leave_date must fall within the last 7 days through today (a missed session).
    $leaveDate = now()->format('Y-m-d');

    Livewire::actingAs($this->parent)
        ->test(LeaveRequests::class)
        ->call('openCreate')
        ->set('selectedChildId', $this->child->id)
        ->set('enrollmentId', $this->enrollment->id)
        ->set('leaveDate', $leaveDate)
        ->set('type', 'sick')
        ->set('reason', 'High fever')
        ->call('submit');

    $this->assertDatabaseHas('leave_requests', [
        'child_id'      => $this->child->id,
        'enrollment_id' => $this->enrollment->id,
        'schedule_id'   => $this->schedule->id,
        'leave_date'    => $leaveDate,
        'type'          => 'sick',
        'reason'        => 'High fever',
        'status'        => 'pending',
    ]);
});

it('validates required fields', function () {
    Livewire::actingAs($this->parent)
        ->test(LeaveRequests::class)
        ->call('openCreate')
        ->call('submit')
        ->assertHasErrors(['enrollmentId', 'leaveDate', 'type']);
});

it('cannot submit a future leave date', function () {
    Livewire::actingAs($this->parent)
        ->test(LeaveRequests::class)
        ->call('openCreate')
        ->set('selectedChildId', $this->child->id)
        ->set('enrollmentId', $this->enrollment->id)
        ->set('leaveDate', now()->addDays(3)->format('Y-m-d'))
        ->set('type', 'sick')
        ->call('submit')
        ->assertHasErrors(['leaveDate']);
});

it('prevents duplicate leave request for same date and enrollment', function () {
    $leaveDate = now()->addDays(3)->format('Y-m-d');

    LeaveRequest::create([
        'child_id'      => $this->child->id,
        'enrollment_id' => $this->enrollment->id,
        'schedule_id'   => $this->schedule->id,
        'leave_date'    => $leaveDate,
        'type'          => 'sick',
    ]);

    Livewire::actingAs($this->parent)
        ->test(LeaveRequests::class)
        ->call('openCreate')
        ->set('enrollmentId', $this->enrollment->id)
        ->set('leaveDate', $leaveDate)
        ->set('type', 'sick')
        ->call('submit')
        ->assertHasErrors(['leaveDate']);
});

it('cannot submit for another parents enrollment', function () {
    $other       = User::factory()->withRole('parent')->approved()->create();
    $otherChild  = Child::factory()->active()->create(['user_id' => $other->id]);
    $otherEnroll = Enrollment::factory()->approved()->create([
        'child_id'    => $otherChild->id,
        'schedule_id' => $this->schedule->id,
        'type'        => 'program',
    ]);

    Livewire::actingAs($this->parent)
        ->test(LeaveRequests::class)
        ->call('openCreate')
        ->set('selectedChildId', $this->child->id)
        ->set('enrollmentId', $otherEnroll->id)
        ->set('leaveDate', now()->format('Y-m-d'))
        ->set('type', 'permit')
        ->call('submit');
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

it('can filter by child', function () {
    $location2   = Location::factory()->create();
    $package2    = Package::factory()->regular()->create(['location_id' => $location2->id]);
    $child2      = Child::factory()->active()->create(['user_id' => $this->parent->id, 'name' => 'Unique Child Two']);
    $enrollment2 = Enrollment::factory()->approved()->create([
        'child_id'    => $child2->id,
        'schedule_id' => $this->schedule->id,
        'package_id'  => $package2->id,
        'type'        => 'program',
    ]);

    // Leave request for child1 on day +3
    LeaveRequest::create([
        'child_id'      => $this->child->id,
        'enrollment_id' => $this->enrollment->id,
        'schedule_id'   => $this->schedule->id,
        'leave_date'    => now()->addDays(3)->format('Y-m-d'),
        'type'          => 'sick',
    ]);

    // Leave request for child2 on day +5
    LeaveRequest::create([
        'child_id'      => $child2->id,
        'enrollment_id' => $enrollment2->id,
        'schedule_id'   => $this->schedule->id,
        'leave_date'    => now()->addDays(5)->format('Y-m-d'),
        'type'          => 'permit',
    ]);

    // Filter by child1 — should see child1's leave date, not child2's
    Livewire::actingAs($this->parent)
        ->test(LeaveRequests::class)
        ->set('filterChildId', $this->child->id)
        ->assertSee(now()->addDays(3)->format('d M Y'))
        ->assertDontSee(now()->addDays(5)->format('d M Y'));
});

it('can cancel the form', function () {
    Livewire::actingAs($this->parent)
        ->test(LeaveRequests::class)
        ->call('openCreate')
        ->assertSet('showForm', true)
        ->call('cancel')
        ->assertSet('showForm', false);
});
