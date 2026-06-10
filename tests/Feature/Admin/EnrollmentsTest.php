<?php

use App\Livewire\Admin\Enrollments;
use App\Models\Child;
use App\Models\Enrollment;
use App\Models\Location;
use App\Models\Package;
use App\Models\Role;
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
    $this->package  = Package::factory()->registration()->create(['location_id' => $this->location->id]);
    $this->child    = Child::factory()->create(['status' => 'unregistered']);
});

it('renders enrollments page for admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.enrollments'))
        ->assertOk();
});

it('non-admin cannot access enrollments page', function () {
    $parent = User::factory()->withRole('parent')->approved()->create();

    $this->actingAs($parent)
        ->get(route('admin.enrollments'))
        ->assertForbidden();
});

it('shows enrollments with child and package info', function () {
    Enrollment::factory()->create([
        'child_id'   => $this->child->id,
        'package_id' => $this->package->id,
        'type'       => 'registration',
        'status'     => 'pending',
    ]);

    Livewire::actingAs($this->admin)
        ->test(Enrollments::class)
        ->assertSee($this->child->name)
        ->assertSee($this->package->name);
});

it('can filter enrollments by status', function () {
    $childB = Child::factory()->create();
    Enrollment::factory()->create([
        'child_id' => $this->child->id, 'package_id' => $this->package->id,
        'type' => 'registration', 'status' => 'pending',
    ]);
    Enrollment::factory()->approved()->create([
        'child_id' => $childB->id, 'package_id' => $this->package->id,
        'type' => 'registration',
    ]);

    Livewire::actingAs($this->admin)
        ->test(Enrollments::class)
        ->set('filterStatus', 'pending')
        ->assertSee($this->child->name)
        ->assertDontSee($childB->name);
});

it('approving registration enrollment activates child', function () {
    $enrollment = Enrollment::factory()->create([
        'child_id'   => $this->child->id,
        'package_id' => $this->package->id,
        'type'       => 'registration',
        'status'     => 'pending',
    ]);

    Livewire::actingAs($this->admin)
        ->test(Enrollments::class)
        ->call('approve', $enrollment->id);

    expect($enrollment->fresh()->status)->toBe('approved');
    expect($enrollment->fresh()->approved_by)->toBe($this->admin->id);
    expect($this->child->fresh()->status)->toBe('active');
});

it('approving program enrollment sets started_at', function () {
    $child   = Child::factory()->active()->create();
    $package = Package::factory()->regular()->create(['location_id' => $this->location->id]);

    $enrollment = Enrollment::factory()->program()->create([
        'child_id'   => $child->id,
        'package_id' => $package->id,
        'status'     => 'pending',
    ]);

    Livewire::actingAs($this->admin)
        ->test(Enrollments::class)
        ->call('approve', $enrollment->id);

    expect($enrollment->fresh()->status)->toBe('approved');
    expect($enrollment->fresh()->started_at)->not->toBeNull();
});

it('can reject an enrollment', function () {
    $enrollment = Enrollment::factory()->create([
        'child_id'   => $this->child->id,
        'package_id' => $this->package->id,
        'type'       => 'registration',
        'status'     => 'pending',
    ]);

    Livewire::actingAs($this->admin)
        ->test(Enrollments::class)
        ->call('reject', $enrollment->id);

    expect($enrollment->fresh()->status)->toBe('rejected');
});
