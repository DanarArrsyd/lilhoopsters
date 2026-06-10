<?php

use App\Livewire\Admin\Programs;
use App\Models\Program;
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

it('renders programs page for admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.programs'))
        ->assertOk();
});

it('non-admin cannot access programs page', function () {
    $parent = User::factory()->withRole('parent')->approved()->create();

    $this->actingAs($parent)
        ->get(route('admin.programs'))
        ->assertForbidden();
});

it('shows existing programs', function () {
    Program::factory()->create(['name' => 'Junior']);

    Livewire::actingAs($this->admin)
        ->test(Programs::class)
        ->assertSee('Junior');
});

it('can create a program', function () {
    Livewire::actingAs($this->admin)
        ->test(Programs::class)
        ->call('openCreate')
        ->set('name', 'Rookie')
        ->set('min_age_months', 18)
        ->set('max_age_months', 36)
        ->call('save')
        ->assertHasNoErrors();

    expect(Program::where('name', 'Rookie')->exists())->toBeTrue();
});

it('validates required fields on create', function () {
    Livewire::actingAs($this->admin)
        ->test(Programs::class)
        ->call('openCreate')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name']);
});

it('validates max age must be greater than min age', function () {
    Livewire::actingAs($this->admin)
        ->test(Programs::class)
        ->call('openCreate')
        ->set('name', 'MVP')
        ->set('min_age_months', 60)
        ->set('max_age_months', 30)
        ->call('save')
        ->assertHasErrors(['max_age_months']);
});

it('can update a program', function () {
    $program = Program::factory()->create(['name' => 'Old Name']);

    Livewire::actingAs($this->admin)
        ->test(Programs::class)
        ->call('openEdit', $program->id)
        ->set('name', 'MVP+')
        ->call('save')
        ->assertHasNoErrors();

    expect($program->fresh()->name)->toBe('MVP+');
});

it('can toggle program active status', function () {
    $program = Program::factory()->create(['is_active' => true]);

    Livewire::actingAs($this->admin)
        ->test(Programs::class)
        ->call('toggleActive', $program->id);

    expect($program->fresh()->is_active)->toBeFalse();
});

it('can delete a program', function () {
    $program = Program::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(Programs::class)
        ->call('confirmDelete', $program->id);

    expect(Program::find($program->id))->toBeNull();
});
