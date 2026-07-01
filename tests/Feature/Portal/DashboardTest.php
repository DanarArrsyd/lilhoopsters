<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

it('redirects the old dashboard route to home for parent', function () {
    $this->actingAs($this->parent)
        ->get(route('parent.dashboard'))
        ->assertRedirect(route('parent.home'));
});

it('non-parent cannot access parent dashboard', function () {
    $admin = User::factory()->withRole('admin')->approved()->create();

    $this->actingAs($admin)
        ->get(route('parent.dashboard'))
        ->assertForbidden();
});
