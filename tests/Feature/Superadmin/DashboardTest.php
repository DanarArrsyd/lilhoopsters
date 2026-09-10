<?php

use App\Livewire\Superadmin\Dashboard;
use App\Models\Enrollment;
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

    $this->superAdmin = User::factory()->withRole('super_admin')->approved()->create();
});

it('excludes exhausted and expired enrollments from the active enrollments stat', function () {
    Enrollment::factory()->program()->approved()->create([
        'total_sessions'     => 8,
        'remaining_sessions' => 3,
    ]);
    Enrollment::factory()->program()->approved()->create([
        'total_sessions'     => 8,
        'remaining_sessions' => 0, // exhausted — must not count as active
    ]);
    Enrollment::factory()->program()->approved()->create([
        'expires_at' => now()->subDay(), // expired — must not count as active
    ]);

    Livewire::actingAs($this->superAdmin)
        ->test(Dashboard::class)
        ->assertViewHas('stats', fn ($stats) => $stats['enrollments'] === 1);
});
