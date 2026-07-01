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

it('shows the selected child program and hides another parents child', function () {
    // The session-calendar view defaults to the parent's active enrollment
    // and renders its program/location. Another parent's child must not leak.
    $other      = User::factory()->withRole('parent')->approved()->create();
    $otherChild = Child::factory()->active()->create(['user_id' => $other->id, 'name' => 'Outsider Kid']);

    Livewire::actingAs($this->parent)
        ->test(AttendanceHistory::class)
        ->assertSee($this->enrollment->schedule->program->name)
        ->assertSee($this->enrollment->schedule->location->name)
        ->assertDontSee('Outsider Kid');
});

it('lets a parent switch between their own children', function () {
    // The child selector renders only when the parent has more than one child.
    $child2 = Child::factory()->active()->create(['user_id' => $this->parent->id, 'name' => 'Second Kiddo']);

    Livewire::actingAs($this->parent)
        ->test(AttendanceHistory::class)
        ->assertSee($this->child->name)
        ->assertSee('Second Kiddo');
});

it('defaults the child filter to the first active child', function () {
    Livewire::actingAs($this->parent)
        ->test(AttendanceHistory::class)
        ->assertSet('filterChildId', (string) $this->child->id);
});
