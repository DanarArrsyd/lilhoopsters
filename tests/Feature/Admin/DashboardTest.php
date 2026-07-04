<?php

use App\Livewire\Admin\Dashboard;
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

    $this->admin = User::factory()->withRole('admin')->approved()->create();
});

it('renders dashboard page for admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk();
});

it('shows correct pending registrations count', function () {
    User::factory()->withRole('parent')->create(['registration_status' => 'pending']);
    User::factory()->withRole('parent')->create(['registration_status' => 'pending']);
    User::factory()->withRole('parent')->approved()->create();

    $component = Livewire::actingAs($this->admin)->test(Dashboard::class);

    expect($component->get('stats')['pending_registrations'])->toBe(2);
});

it('shows correct active players count', function () {
    Child::factory()->active()->create();
    Child::factory()->active()->create();
    Child::factory()->create(['status' => 'unregistered']);

    $component = Livewire::actingAs($this->admin)->test(Dashboard::class);

    expect($component->get('stats')['active_players'])->toBe(2);
});

it('shows correct pending enrollments count', function () {
    $location = Location::factory()->create();
    $package  = Package::factory()->create(['location_id' => $location->id]);
    Enrollment::factory()->create(['package_id' => $package->id, 'status' => 'pending']);
    Enrollment::factory()->create(['package_id' => $package->id, 'status' => 'pending']);
    Enrollment::factory()->approved()->create(['package_id' => $package->id]);

    $component = Livewire::actingAs($this->admin)->test(Dashboard::class);

    expect($component->get('stats')['pending_enrollments'])->toBe(2);
});

it('shows correct pending payments count', function () {
    $location = Location::factory()->create();
    $package  = Package::factory()->create(['location_id' => $location->id]);
    Transaction::factory()->create(['package_id' => $package->id, 'status' => 'pending']);
    Transaction::factory()->paid()->create(['package_id' => $package->id]);

    $component = Livewire::actingAs($this->admin)->test(Dashboard::class);

    expect($component->get('stats')['pending_payments'])->toBe(1);
});

it('shows a maps button for a schedule location that has a maps url on the week calendar', function () {
    $location = \App\Models\Location::factory()->create(['maps_url' => 'https://maps.google.com/?q=admin-test']);
    $program  = \App\Models\Program::factory()->create();
    \App\Models\Schedule::factory()->create([
        'location_id' => $location->id,
        'program_id'  => $program->id,
        'day_of_week' => strtolower(now()->format('l')),
        'is_active'   => true,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertSee('https://maps.google.com/?q=admin-test', false);
});
