<?php

use App\Livewire\Admin\Locations;
use App\Models\Location;
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

    $this->admin = User::factory()->withRole('admin')->approved()->create();
});

it('renders locations page for admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.locations'))
        ->assertOk();
});

it('non-admin cannot access locations page', function () {
    $parent = User::factory()->withRole('parent')->approved()->create();

    $this->actingAs($parent)
        ->get(route('admin.locations'))
        ->assertForbidden();
});

it('shows existing locations', function () {
    Location::factory()->create(['name' => 'Pakubuwono Court']);

    Livewire::actingAs($this->admin)
        ->test(Locations::class)
        ->assertSee('Pakubuwono Court');
});

it('can search locations by name', function () {
    Location::factory()->create(['name' => 'Kemang Court']);
    Location::factory()->create(['name' => 'Senayan Arena']);

    Livewire::actingAs($this->admin)
        ->test(Locations::class)
        ->set('search', 'Kemang')
        ->assertSee('Kemang Court')
        ->assertDontSee('Senayan Arena');
});

it('can create a location', function () {
    Livewire::actingAs($this->admin)
        ->test(Locations::class)
        ->call('openCreate')
        ->set('name', 'Kemang Court')
        ->set('address', 'Jl. Kemang Raya No. 1')
        ->set('city', 'Jakarta Selatan')
        ->call('save')
        ->assertHasNoErrors();

    expect(Location::where('name', 'Kemang Court')->exists())->toBeTrue();
});

it('validates required fields on create', function () {
    Livewire::actingAs($this->admin)
        ->test(Locations::class)
        ->call('openCreate')
        ->call('save')
        ->assertHasErrors(['name', 'address']);
});

it('can update a location', function () {
    $location = Location::factory()->create(['name' => 'Old Name']);

    Livewire::actingAs($this->admin)
        ->test(Locations::class)
        ->call('openEdit', $location->id)
        ->set('name', 'New Name')
        ->call('save')
        ->assertHasNoErrors();

    expect($location->fresh()->name)->toBe('New Name');
});

it('can toggle location active status', function () {
    $location = Location::factory()->create(['is_active' => true]);

    Livewire::actingAs($this->admin)
        ->test(Locations::class)
        ->call('toggleActive', $location->id);

    expect($location->fresh()->is_active)->toBeFalse();
});

it('can delete a location', function () {
    $location = Location::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(Locations::class)
        ->call('confirmDelete', $location->id);

    expect(Location::find($location->id))->toBeNull();
});
