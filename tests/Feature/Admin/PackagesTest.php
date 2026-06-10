<?php

use App\Livewire\Admin\Packages;
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
    $this->location = Location::factory()->create(['name' => 'Pakubuwono']);
});

it('renders packages page for admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.packages'))
        ->assertOk();
});

it('non-admin cannot access packages page', function () {
    $parent = User::factory()->withRole('parent')->approved()->create();

    $this->actingAs($parent)
        ->get(route('admin.packages'))
        ->assertForbidden();
});

it('shows packages with location name', function () {
    Package::factory()->create([
        'location_id' => $this->location->id,
        'name'        => 'Regular Apr–Mei',
    ]);

    Livewire::actingAs($this->admin)
        ->test(Packages::class)
        ->assertSee('Regular Apr–Mei')
        ->assertSee('Pakubuwono');
});

it('can filter packages by location', function () {
    $other = Location::factory()->create(['name' => 'Kemang']);
    Package::factory()->create(['location_id' => $this->location->id, 'name' => 'Paku Package']);
    Package::factory()->create(['location_id' => $other->id,         'name' => 'Kemang Package']);

    Livewire::actingAs($this->admin)
        ->test(Packages::class)
        ->set('filterLocation', $this->location->id)
        ->assertSee('Paku Package')
        ->assertDontSee('Kemang Package');
});

it('can create a package', function () {
    Livewire::actingAs($this->admin)
        ->test(Packages::class)
        ->call('openCreate')
        ->set('location_id', $this->location->id)
        ->set('name', 'Registration Fee')
        ->set('type', 'registration')
        ->set('price', 350000)
        ->call('save')
        ->assertHasNoErrors();

    expect(Package::where('name', 'Registration Fee')->exists())->toBeTrue();
});

it('validates required fields on create', function () {
    Livewire::actingAs($this->admin)
        ->test(Packages::class)
        ->call('openCreate')
        ->set('name', '')
        ->set('price', 0)
        ->call('save')
        ->assertHasErrors(['location_id', 'name', 'price']);
});

it('can update a package', function () {
    $package = Package::factory()->create([
        'location_id' => $this->location->id,
        'name'        => 'Old Package',
    ]);

    Livewire::actingAs($this->admin)
        ->test(Packages::class)
        ->call('openEdit', $package->id)
        ->set('name', 'New Package')
        ->call('save')
        ->assertHasNoErrors();

    expect($package->fresh()->name)->toBe('New Package');
});

it('can toggle package active status', function () {
    $package = Package::factory()->create(['location_id' => $this->location->id, 'is_active' => true]);

    Livewire::actingAs($this->admin)
        ->test(Packages::class)
        ->call('toggleActive', $package->id);

    expect($package->fresh()->is_active)->toBeFalse();
});

it('can delete a package', function () {
    $package = Package::factory()->create(['location_id' => $this->location->id]);

    Livewire::actingAs($this->admin)
        ->test(Packages::class)
        ->call('confirmDelete', $package->id);

    expect(Package::find($package->id))->toBeNull();
});
