<?php

use App\Livewire\Portal\MyPlayers;
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

it('shows only own children', function () {
    Child::factory()->create(['user_id' => $this->parent->id, 'name' => 'My Child']);

    $other = User::factory()->withRole('parent')->approved()->create();
    Child::factory()->create(['user_id' => $other->id, 'name' => 'Other Child']);

    Livewire::actingAs($this->parent)
        ->test(MyPlayers::class)
        ->assertSee('My Child')
        ->assertDontSee('Other Child');
});

it('can add a new child', function () {
    Livewire::actingAs($this->parent)
        ->test(MyPlayers::class)
        ->call('openCreate')
        ->set('name', 'Budi Santoso')
        ->set('birthDate', '2022-03-15')
        ->set('gender', 'male')
        ->call('save');

    $this->assertDatabaseHas('children', [
        'user_id' => $this->parent->id,
        'name'    => 'Budi Santoso',
        'gender'  => 'male',
        'status'  => 'unregistered',
    ]);
});

it('validates required fields on create', function () {
    Livewire::actingAs($this->parent)
        ->test(MyPlayers::class)
        ->call('openCreate')
        ->call('save')
        ->assertHasErrors(['name', 'birthDate', 'gender']);
});

it('validates birth date must be in the past', function () {
    Livewire::actingAs($this->parent)
        ->test(MyPlayers::class)
        ->call('openCreate')
        ->set('name', 'Budi')
        ->set('birthDate', now()->addDays(1)->format('Y-m-d'))
        ->set('gender', 'male')
        ->call('save')
        ->assertHasErrors(['birthDate']);
});

it('can edit an existing child', function () {
    $child = Child::factory()->create(['user_id' => $this->parent->id, 'school' => 'SD Lama']);

    Livewire::actingAs($this->parent)
        ->test(MyPlayers::class)
        ->call('openEdit', $child->id)
        ->set('school', 'SD Baru')
        ->call('save');

    expect($child->fresh()->school)->toBe('SD Baru');
});

it('cannot edit another parent child', function () {
    $other = User::factory()->withRole('parent')->approved()->create();
    $child = Child::factory()->create(['user_id' => $other->id]);

    Livewire::actingAs($this->parent)
        ->test(MyPlayers::class)
        ->call('openEdit', $child->id);

    // Should throw a 404/ModelNotFound since ownedChild scopes to current user
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

it('can update jersey info for active child', function () {
    $child = Child::factory()->active()->create(['user_id' => $this->parent->id]);

    Livewire::actingAs($this->parent)
        ->test(MyPlayers::class)
        ->call('openEdit', $child->id)
        ->set('jerseyName', 'BUDI')
        ->set('jerseyNumber', '23')
        ->call('save');

    expect($child->fresh()->jersey_name)->toBe('BUDI');
    expect($child->fresh()->jersey_number)->toBe('23');
});

it('does not update jersey for unregistered child', function () {
    $child = Child::factory()->create(['user_id' => $this->parent->id, 'status' => 'unregistered']);

    Livewire::actingAs($this->parent)
        ->test(MyPlayers::class)
        ->call('openEdit', $child->id)
        ->set('jerseyName', 'BUDI')
        ->call('save');

    expect($child->fresh()->jersey_name)->toBeNull();
});

it('can cancel form without saving', function () {
    Livewire::actingAs($this->parent)
        ->test(MyPlayers::class)
        ->call('openCreate')
        ->set('name', 'Unsaved Child')
        ->call('cancel')
        ->assertSet('showForm', false);

    $this->assertDatabaseMissing('children', ['name' => 'Unsaved Child']);
});
