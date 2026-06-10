<?php

use App\Livewire\Admin\AdminParents;
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

it('renders parents page for admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.parents'))
        ->assertOk();
});

it('non-admin cannot access parents page', function () {
    $parent = User::factory()->withRole('parent')->approved()->create();

    $this->actingAs($parent)
        ->get(route('admin.parents'))
        ->assertForbidden();
});

it('shows parent users', function () {
    $parent = User::factory()->withRole('parent')->create([
        'name'                => 'Budi Santoso',
        'registration_status' => 'pending',
    ]);

    Livewire::actingAs($this->admin)
        ->test(AdminParents::class)
        ->assertSee('Budi Santoso');
});

it('can filter parents by status', function () {
    User::factory()->withRole('parent')->create([
        'name'                => 'Pending Parent',
        'registration_status' => 'pending',
    ]);
    User::factory()->withRole('parent')->approved()->create(['name' => 'Approved Parent']);

    Livewire::actingAs($this->admin)
        ->test(AdminParents::class)
        ->set('filterStatus', 'pending')
        ->assertSee('Pending Parent')
        ->assertDontSee('Approved Parent');
});

it('can search parents by name', function () {
    User::factory()->withRole('parent')->create(['name' => 'Budi Santoso']);
    User::factory()->withRole('parent')->create(['name' => 'Sari Dewi']);

    Livewire::actingAs($this->admin)
        ->test(AdminParents::class)
        ->set('search', 'Budi')
        ->assertSee('Budi Santoso')
        ->assertDontSee('Sari Dewi');
});

it('can approve a parent registration', function () {
    $parent = User::factory()->withRole('parent')->create([
        'registration_status' => 'pending',
    ]);

    Livewire::actingAs($this->admin)
        ->test(AdminParents::class)
        ->call('approve', $parent->id);

    expect($parent->fresh()->registration_status)->toBe('approved');
});

it('can reject a parent registration', function () {
    $parent = User::factory()->withRole('parent')->create([
        'registration_status' => 'pending',
    ]);

    Livewire::actingAs($this->admin)
        ->test(AdminParents::class)
        ->call('reject', $parent->id);

    expect($parent->fresh()->registration_status)->toBe('rejected');
});
