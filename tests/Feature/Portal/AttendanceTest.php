<?php

use App\Livewire\Portal\AttendanceHistory;
use App\Models\Attendance;
use App\Models\Child;
use App\Models\Coach;
use App\Models\Enrollment;
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

    $location       = Location::factory()->create();
    $program        = Program::factory()->create();
    $coachUser      = User::factory()->withRole('coach')->approved()->create();
    $coach          = Coach::factory()->create(['user_id' => $coachUser->id]);
    $this->schedule = Schedule::factory()->create([
        'location_id' => $location->id,
        'program_id'  => $program->id,
        'coach_id'    => $coach->id,
    ]);
    $package        = Package::factory()->regular()->create(['location_id' => $location->id]);

    $this->child      = Child::factory()->active()->create(['user_id' => $this->parent->id]);
    $this->enrollment = Enrollment::factory()->approved()->create([
        'child_id'    => $this->child->id,
        'schedule_id' => $this->schedule->id,
        'package_id'  => $package->id,
        'type'        => 'program',
    ]);
});

it('renders attendance page for parent', function () {
    $this->actingAs($this->parent)
        ->get(route('parent.attendance'))
        ->assertOk();
});

it('non-parent cannot access attendance page', function () {
    $admin = User::factory()->withRole('admin')->approved()->create();

    $this->actingAs($admin)
        ->get(route('parent.attendance'))
        ->assertForbidden();
});

it('shows only own childrens attendance', function () {
    Attendance::factory()->present()->create([
        'child_id'      => $this->child->id,
        'enrollment_id' => $this->enrollment->id,
        'schedule_id'   => $this->schedule->id,
        'attended_at'   => now()->subDays(1),
    ]);

    $other       = User::factory()->withRole('parent')->approved()->create();
    $otherChild  = Child::factory()->active()->create(['user_id' => $other->id]);
    $otherEnroll = Enrollment::factory()->approved()->create([
        'child_id'    => $otherChild->id,
        'schedule_id' => $this->schedule->id,
    ]);
    Attendance::factory()->present()->create([
        'child_id'      => $otherChild->id,
        'enrollment_id' => $otherEnroll->id,
        'schedule_id'   => $this->schedule->id,
        'attended_at'   => now()->subDays(2),
    ]);

    Livewire::actingAs($this->parent)
        ->test(AttendanceHistory::class)
        ->assertSee($this->child->name)
        ->assertDontSee($otherChild->name);
});

it('can filter by status', function () {
    Attendance::factory()->present()->create([
        'child_id'      => $this->child->id,
        'enrollment_id' => $this->enrollment->id,
        'schedule_id'   => $this->schedule->id,
        'attended_at'   => now()->subDays(3),
    ]);
    Attendance::factory()->absent()->create([
        'child_id'      => $this->child->id,
        'enrollment_id' => $this->enrollment->id,
        'schedule_id'   => $this->schedule->id,
        'attended_at'   => now()->subDays(1),
    ]);

    Livewire::actingAs($this->parent)
        ->test(AttendanceHistory::class)
        ->set('filterStatus', 'no_show')
        ->assertSee(now()->subDays(1)->format('d M Y'))
        ->assertDontSee(now()->subDays(3)->format('d M Y'));
});

it('shows summary stats for active children', function () {
    Attendance::factory()->present()->create([
        'child_id'      => $this->child->id,
        'enrollment_id' => $this->enrollment->id,
        'schedule_id'   => $this->schedule->id,
        'attended_at'   => now()->subDays(1),
    ]);
    Attendance::factory()->absent()->create([
        'child_id'      => $this->child->id,
        'enrollment_id' => $this->enrollment->id,
        'schedule_id'   => $this->schedule->id,
        'attended_at'   => now()->subDays(2),
    ]);

    Livewire::actingAs($this->parent)
        ->test(AttendanceHistory::class)
        ->assertSee('50% present')  // 1 present out of 2 total
        ->assertSee($this->child->name);
});
