<?php

use App\Livewire\Portal\Dashboard;
use App\Models\Child;
use App\Models\Enrollment;
use App\Models\Location;
use App\Models\Package;
use App\Models\Role;
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

    $this->parent = User::factory()->withRole('parent')->approved()->create();
});

it('renders dashboard for parent', function () {
    $this->actingAs($this->parent)
        ->get(route('parent.dashboard'))
        ->assertOk();
});

it('non-parent cannot access parent dashboard', function () {
    $admin = User::factory()->withRole('admin')->approved()->create();

    $this->actingAs($admin)
        ->get(route('parent.dashboard'))
        ->assertForbidden();
});

it('shows correct active children count', function () {
    Child::factory()->active()->create(['user_id' => $this->parent->id]);
    Child::factory()->active()->create(['user_id' => $this->parent->id]);
    Child::factory()->create(['user_id' => $this->parent->id, 'status' => 'unregistered']);

    // Another parent's child — should not count
    $other = User::factory()->withRole('parent')->approved()->create();
    Child::factory()->active()->create(['user_id' => $other->id]);

    $component = Livewire::actingAs($this->parent)->test(Dashboard::class);

    expect($component->get('stats')['active_children'])->toBe(2);
});

it('shows correct pending children count', function () {
    Child::factory()->create(['user_id' => $this->parent->id, 'status' => 'pending']);
    Child::factory()->active()->create(['user_id' => $this->parent->id]);

    $component = Livewire::actingAs($this->parent)->test(Dashboard::class);

    expect($component->get('stats')['pending_children'])->toBe(1);
});

it('shows correct pending enrollments count', function () {
    $location = Location::factory()->create();
    $package  = Package::factory()->create(['location_id' => $location->id]);
    $child    = Child::factory()->active()->create(['user_id' => $this->parent->id]);

    Enrollment::factory()->create(['child_id' => $child->id, 'package_id' => $package->id, 'status' => 'pending']);
    Enrollment::factory()->approved()->create(['child_id' => $child->id, 'package_id' => $package->id]);

    // Another parent's enrollment — should not count
    $other      = User::factory()->withRole('parent')->approved()->create();
    $otherChild = Child::factory()->active()->create(['user_id' => $other->id]);
    Enrollment::factory()->create(['child_id' => $otherChild->id, 'package_id' => $package->id, 'status' => 'pending']);

    $component = Livewire::actingAs($this->parent)->test(Dashboard::class);

    expect($component->get('stats')['pending_enrollments'])->toBe(1);
});

it('shows correct pending payments count', function () {
    $location = Location::factory()->create();
    $package  = Package::factory()->create(['location_id' => $location->id]);

    Transaction::factory()->create(['user_id' => $this->parent->id, 'package_id' => $package->id, 'status' => 'pending']);
    Transaction::factory()->paid()->create(['user_id' => $this->parent->id, 'package_id' => $package->id]);

    // Another parent's transaction — should not count
    $other = User::factory()->withRole('parent')->approved()->create();
    Transaction::factory()->create(['user_id' => $other->id, 'package_id' => $package->id, 'status' => 'pending']);

    $component = Livewire::actingAs($this->parent)->test(Dashboard::class);

    expect($component->get('stats')['pending_payments'])->toBe(1);
});
