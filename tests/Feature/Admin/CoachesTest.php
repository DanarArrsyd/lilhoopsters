<?php

use App\Livewire\Admin\Coaches;
use App\Models\Coach;
use App\Models\Location;
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

it('renders coaches page for admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.coaches'))
        ->assertOk();
});

it('non-admin cannot access coaches page', function () {
    $parent = User::factory()->withRole('parent')->approved()->create();

    $this->actingAs($parent)
        ->get(route('admin.coaches'))
        ->assertForbidden();
});

it('shows existing coaches', function () {
    $coach = Coach::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(Coaches::class)
        ->assertSee($coach->user->name);
});

it('can create a coach with user account', function () {
    Livewire::actingAs($this->admin)
        ->test(Coaches::class)
        ->call('openCreate')
        ->set('coach_name', 'Coach Budi')
        ->set('coach_email', 'budi@example.com')
        ->set('coach_password', 'secret123')
        ->set('phone', '081234567890')
        ->call('save')
        ->assertHasNoErrors();

    expect(User::where('email', 'budi@example.com')->exists())->toBeTrue();
    expect(Coach::whereHas('user', fn($q) => $q->where('email', 'budi@example.com'))->exists())->toBeTrue();
});

it('validates required fields on create', function () {
    Livewire::actingAs($this->admin)
        ->test(Coaches::class)
        ->call('openCreate')
        ->call('save')
        ->assertHasErrors(['coach_name', 'coach_email', 'coach_password', 'phone']);
});

it('can update coach details', function () {
    $coach = Coach::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(Coaches::class)
        ->call('openEdit', $coach->id)
        ->set('coach_name', 'Updated Name')
        ->set('phone', '089999999999')
        ->call('save')
        ->assertHasNoErrors();

    expect($coach->user->fresh()->name)->toBe('Updated Name');
    expect($coach->fresh()->phone)->toBe('089999999999');
});

it('can assign locations to a coach', function () {
    $location = Location::factory()->create();
    $coach    = Coach::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(Coaches::class)
        ->call('openEdit', $coach->id)
        ->set('selectedLocations', [$location->id])
        ->call('save')
        ->assertHasNoErrors();

    expect($coach->fresh()->locations->pluck('id')->toArray())->toContain($location->id);
});

it('can toggle coach active status', function () {
    $coach = Coach::factory()->create(['is_active' => true]);

    Livewire::actingAs($this->admin)
        ->test(Coaches::class)
        ->call('toggleActive', $coach->id);

    expect($coach->fresh()->is_active)->toBeFalse();
});
