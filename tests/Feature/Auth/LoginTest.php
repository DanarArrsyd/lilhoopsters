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

test('login page renders', function () {
    $this->get('/login')->assertOk()->assertViewIs('auth.login');
});

test('authenticated users are redirected from login', function () {
    $role = Role::where('name', 'parent')->first();
    $user = User::factory()->approved()->create(['role_id' => $role->id]);

    $this->actingAs($user)->get('/login')->assertRedirect();
});

test('admin is redirected to admin dashboard after login', function () {
    $role = Role::where('name', 'admin')->first();
    $user = User::factory()->approved()->create(['role_id' => $role->id]);

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('admin.dashboard'));
});

test('parent is redirected to parent dashboard after login', function () {
    $role = Role::where('name', 'parent')->first();
    $user = User::factory()->approved()->create(['role_id' => $role->id]);

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('parent.dashboard'));
});

test('pending user is redirected to pending page', function () {
    $role = Role::where('name', 'parent')->first();
    $user = User::factory()->create(['role_id' => $role->id, 'registration_status' => 'pending']);

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('pending'));
});

test('wrong credentials return validation error', function () {
    $this->post('/login', ['email' => 'nobody@example.com', 'password' => 'wrong'])
        ->assertSessionHasErrors('email');
});

test('logout clears session and redirects to login', function () {
    $role = Role::where('name', 'parent')->first();
    $user = User::factory()->approved()->create(['role_id' => $role->id]);

    $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));
    $this->assertGuest();
});
