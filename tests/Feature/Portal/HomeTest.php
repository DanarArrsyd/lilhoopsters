<?php

use App\Livewire\Portal\Home;
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

    $this->parent = User::factory()->withRole('parent')->approved()->create();
});

it('renders the home page', function () {
    $this->actingAs($this->parent)->get(route('parent.home'))->assertOk();
});

it('shows an empty state when the parent has no children', function () {
    Livewire::actingAs($this->parent)
        ->test(Home::class)
        ->assertSee('Add your first player');
});

it('defaults to the first child and can switch children', function () {
    $first  = Child::factory()->create(['user_id' => $this->parent->id, 'status' => 'active', 'name' => 'Aisyah']);
    $second = Child::factory()->create(['user_id' => $this->parent->id, 'status' => 'active', 'name' => 'Bayu']);

    Livewire::actingAs($this->parent)
        ->test(Home::class)
        ->assertSet('activeChildId', $first->id)
        ->call('switchChild', $second->id)
        ->assertSet('activeChildId', $second->id);
});

it('refuses to switch to a child that does not belong to the parent', function () {
    $other = User::factory()->withRole('parent')->approved()->create();
    $notMine = Child::factory()->create(['user_id' => $other->id, 'status' => 'active']);

    Livewire::actingAs($this->parent)
        ->test(Home::class)
        ->call('switchChild', $notMine->id);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
