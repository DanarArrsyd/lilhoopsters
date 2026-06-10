<?php

use App\Livewire\Admin\Players;
use App\Models\Child;
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

it('renders players page for admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.players'))
        ->assertOk();
});

it('non-admin cannot access players page', function () {
    $parent = User::factory()->withRole('parent')->approved()->create();

    $this->actingAs($parent)
        ->get(route('admin.players'))
        ->assertForbidden();
});

it('shows all players with parent name', function () {
    $child = Child::factory()->create(['name' => 'Rafa Santoso']);

    Livewire::actingAs($this->admin)
        ->test(Players::class)
        ->assertSee('Rafa Santoso');
});

it('can search players by name', function () {
    Child::factory()->create(['name' => 'Rafa Santoso']);
    Child::factory()->create(['name' => 'Zara Dewi']);

    Livewire::actingAs($this->admin)
        ->test(Players::class)
        ->set('search', 'Rafa')
        ->assertSee('Rafa Santoso')
        ->assertDontSee('Zara Dewi');
});

it('can filter players by status', function () {
    Child::factory()->active()->create(['name' => 'Active Player']);
    Child::factory()->create(['name' => 'Pending Player', 'status' => 'pending']);

    Livewire::actingAs($this->admin)
        ->test(Players::class)
        ->set('filterStatus', 'active')
        ->assertSee('Active Player')
        ->assertDontSee('Pending Player');
});

it('shows player status badge', function () {
    Child::factory()->active()->create(['name' => 'Active Kid']);

    Livewire::actingAs($this->admin)
        ->test(Players::class)
        ->assertSee('Active Kid')
        ->assertSee('active');
});
