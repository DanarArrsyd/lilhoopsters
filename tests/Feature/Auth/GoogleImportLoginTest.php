<?php
// tests/Feature/Auth/GoogleImportLoginTest.php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::insert([
        ['name' => 'super_admin', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'admin',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'coach',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'parent',      'created_at' => now(), 'updated_at' => now()],
    ]);
});

function fakeGoogleUser(string $id, string $email, string $name): void
{
    $abstract = Mockery::mock(\Laravel\Socialite\Two\User::class);
    $abstract->shouldReceive('getId')->andReturn($id);
    $abstract->shouldReceive('getEmail')->andReturn($email);
    $abstract->shouldReceive('getName')->andReturn($name);
    $abstract->shouldReceive('getAvatar')->andReturn(null);

    $provider = Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
    $provider->shouldReceive('user')->andReturn($abstract);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
}

it('logs in an imported parent straight away via Google (no approval)', function () {
    // Simulates a member previously added through Import Members (approved).
    $parentRoleId = Role::where('name', 'parent')->value('id');
    $imported = User::factory()->create([
        'role_id'             => $parentRoleId,
        'email'               => 'budi@gmail.com',
        'google_id'           => null,
        'registration_status' => 'approved',
        'is_active'           => true,
    ]);

    fakeGoogleUser('G-123', 'budi@gmail.com', 'Budi Santoso');

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('parent.dashboard'));

    $this->assertAuthenticatedAs($imported->fresh());
    expect($imported->fresh()->google_id)->toBe('G-123'); // linked on first login
});

it('still sends a brand-new Google user to the pending screen', function () {
    fakeGoogleUser('G-999', 'stranger@gmail.com', 'Stranger');

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('pending'));

    expect(User::where('email', 'stranger@gmail.com')->first()->registration_status)->toBe('pending');
});
