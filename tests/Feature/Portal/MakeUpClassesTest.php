<?php

use App\Livewire\Portal\MakeUpClasses;
use App\Models\Child;
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
    Role::insert([
        ['name' => 'super_admin', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'admin',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'coach',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'parent',      'created_at' => now(), 'updated_at' => now()],
    ]);

    $this->parent = User::factory()->withRole('parent')->approved()->create();
    $this->child  = Child::factory()->create(['user_id' => $this->parent->id]);

    $this->enrollment = Enrollment::factory()->approved()->create([
        'child_id' => $this->child->id,
    ]);

    $this->schedule = Schedule::factory()->create(['is_active' => true]);

    $this->leaveRequest = LeaveRequest::factory()->approved()->create([
        'child_id'      => $this->child->id,
        'enrollment_id' => $this->enrollment->id,
    ]);
});

it('shows empty state when no requests', function () {
    Livewire::actingAs($this->parent)
        ->test(MakeUpClasses::class)
        ->assertSee('No make-up class requests yet');
});

it('can submit a make-up class request', function () {
    Livewire::actingAs($this->parent)
        ->test(MakeUpClasses::class)
        ->call('openForm')
        ->set('leaveRequestId', $this->leaveRequest->id)
        ->set('targetScheduleId', $this->schedule->id)
        ->set('targetDate', now()->addDays(7)->toDateString())
        ->call('submit');

    expect(MakeUpClass::where('child_id', $this->child->id)->count())->toBe(1);
    expect(MakeUpClass::first()->status)->toBe('pending');
});

it('cannot submit duplicate make-up for same leave request', function () {
    MakeUpClass::factory()->create([
        'child_id'         => $this->child->id,
        'enrollment_id'    => $this->enrollment->id,
        'leave_request_id' => $this->leaveRequest->id,
    ]);

    Livewire::actingAs($this->parent)
        ->test(MakeUpClasses::class)
        ->call('openForm')
        ->set('leaveRequestId', $this->leaveRequest->id)
        ->set('targetScheduleId', $this->schedule->id)
        ->set('targetDate', now()->addDays(7)->toDateString())
        ->call('submit')
        ->assertHasErrors(['leaveRequestId']);
});

it('cannot use another parents leave request', function () {
    $otherParent  = User::factory()->withRole('parent')->approved()->create();
    $otherChild   = Child::factory()->create(['user_id' => $otherParent->id]);
    $otherEnroll  = Enrollment::factory()->approved()->create(['child_id' => $otherChild->id]);
    $otherLeave   = LeaveRequest::factory()->approved()->create([
        'child_id'      => $otherChild->id,
        'enrollment_id' => $otherEnroll->id,
    ]);

    Livewire::actingAs($this->parent)
        ->test(MakeUpClasses::class)
        ->call('openForm')
        ->set('leaveRequestId', $otherLeave->id)
        ->set('targetScheduleId', $this->schedule->id)
        ->set('targetDate', now()->addDays(7)->toDateString())
        ->call('submit');

    // scoped query returns null → error added, no record created
    expect(MakeUpClass::where('child_id', $otherChild->id)->count())->toBe(0);
});

it('requires all fields', function () {
    Livewire::actingAs($this->parent)
        ->test(MakeUpClasses::class)
        ->call('openForm')
        ->call('submit')
        ->assertHasErrors(['leaveRequestId', 'targetScheduleId', 'targetDate']);
});
