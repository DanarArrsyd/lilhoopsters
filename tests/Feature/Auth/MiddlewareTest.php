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
});

test('unauthenticated user cannot access admin dashboard', function () {
    $this->get('/admin/dashboard')->assertRedirect('/login');
});

test('parent cannot access admin dashboard', function () {
    $role = Role::where('name', 'parent')->first();
    $user = User::factory()->approved()->create(['role_id' => $role->id]);

    $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();
});

test('admin can access admin dashboard', function () {
    $role = Role::where('name', 'admin')->first();
    $user = User::factory()->approved()->create(['role_id' => $role->id]);

    $this->actingAs($user)->get('/admin/dashboard')->assertOk();
});

test('pending user is redirected to pending page on protected routes', function () {
    $role = Role::where('name', 'admin')->first();
    $user = User::factory()->create(['role_id' => $role->id, 'registration_status' => 'pending']);

    $this->actingAs($user)->get('/admin/dashboard')->assertRedirect(route('pending'));
});

test('inactive user is forbidden', function () {
    $role = Role::where('name', 'admin')->first();
    $user = User::factory()->approved()->create(['role_id' => $role->id, 'is_active' => false]);

    $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();
});

test('coach can access coach dashboard', function () {
    $role = Role::where('name', 'coach')->first();
    $user = User::factory()->approved()->create(['role_id' => $role->id]);

    $this->actingAs($user)->get('/coach/dashboard')->assertOk();
});

test('parent can access parent dashboard', function () {
    $role = Role::where('name', 'parent')->first();
    $user = User::factory()->approved()->create(['role_id' => $role->id]);

    $this->actingAs($user)->get('/parent/dashboard')->assertOk();
});
