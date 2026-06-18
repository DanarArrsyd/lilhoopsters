<?php
// tests/Feature/Admin/ReportsTest.php

use App\Models\Location;
use App\Models\Package;
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

    $this->admin    = User::factory()->withRole('admin')->approved()->create();
    $this->location = Location::factory()->create();
    $this->package  = Package::factory()->regular()->create(['location_id' => $this->location->id]);
});

it('renders reports page for admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.reports'))
        ->assertOk();
});

it('non-admin cannot access reports page', function () {
    $parent = User::factory()->withRole('parent')->approved()->create();

    $this->actingAs($parent)
        ->get(route('admin.reports'))
        ->assertForbidden();
});

it('shows reports link in sidebar', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.reports'));

    $response->assertSee(route('admin.reports'));
});
