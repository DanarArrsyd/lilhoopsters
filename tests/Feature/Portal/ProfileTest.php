<?php

use App\Livewire\Portal\Profile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::insert([
        ['name' => 'super_admin', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'admin',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'coach',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'parent',      'created_at' => now(), 'updated_at' => now()],
    ]);

    $this->parent = User::factory()->withRole('parent')->approved()->create([
        'password' => 'oldpassword',
    ]);
});

it('renders profile page for parent', function () {
    $this->actingAs($this->parent)
        ->get(route('parent.profile'))
        ->assertOk();
});

it('non-parent cannot access profile page', function () {
    $admin = User::factory()->withRole('admin')->approved()->create();

    $this->actingAs($admin)
        ->get(route('parent.profile'))
        ->assertForbidden();
});

it('loads current profile data on mount', function () {
    $this->parent->update([
        'whatsapp_number' => '08123456789',
        'occupation'      => 'Engineer',
    ]);

    $component = Livewire::actingAs($this->parent)->test(Profile::class);

    $component->assertSet('name', $this->parent->name)
              ->assertSet('whatsappNumber', '08123456789')
              ->assertSet('occupation', 'Engineer');
});

it('can update profile info', function () {
    Livewire::actingAs($this->parent)
        ->test(Profile::class)
        ->set('name', 'Updated Name')
        ->set('whatsappNumber', '08999999999')
        ->set('address', 'Jl. Baru No. 1')
        ->set('occupation', 'Designer')
        ->call('saveProfile');

    $this->parent->refresh();
    expect($this->parent->name)->toBe('Updated Name');
    expect($this->parent->whatsapp_number)->toBe('08999999999');
    expect($this->parent->address)->toBe('Jl. Baru No. 1');
    expect($this->parent->occupation)->toBe('Designer');
});

it('validates name is required', function () {
    Livewire::actingAs($this->parent)
        ->test(Profile::class)
        ->set('name', '')
        ->call('saveProfile')
        ->assertHasErrors(['name']);
});

it('can change password with correct current password', function () {
    Livewire::actingAs($this->parent)
        ->test(Profile::class)
        ->set('currentPassword', 'oldpassword')
        ->set('newPassword', 'newpassword123')
        ->set('newPasswordConfirmation', 'newpassword123')
        ->call('changePassword');

    expect(Hash::check('newpassword123', $this->parent->fresh()->password))->toBeTrue();
});

it('rejects wrong current password', function () {
    Livewire::actingAs($this->parent)
        ->test(Profile::class)
        ->set('currentPassword', 'wrongpassword')
        ->set('newPassword', 'newpassword123')
        ->set('newPasswordConfirmation', 'newpassword123')
        ->call('changePassword')
        ->assertHasErrors(['currentPassword']);
});

it('requires new password confirmation to match', function () {
    Livewire::actingAs($this->parent)
        ->test(Profile::class)
        ->set('currentPassword', 'oldpassword')
        ->set('newPassword', 'newpassword123')
        ->set('newPasswordConfirmation', 'differentpassword')
        ->call('changePassword')
        ->assertHasErrors(['newPassword']); // 'same' rule error lands on newPassword
});

it('requires new password to be at least 8 characters', function () {
    Livewire::actingAs($this->parent)
        ->test(Profile::class)
        ->set('currentPassword', 'oldpassword')
        ->set('newPassword', 'short')
        ->set('newPasswordConfirmation', 'short')
        ->call('changePassword')
        ->assertHasErrors(['newPassword']);
});

it('clears password fields after successful change', function () {
    Livewire::actingAs($this->parent)
        ->test(Profile::class)
        ->set('currentPassword', 'oldpassword')
        ->set('newPassword', 'newpassword123')
        ->set('newPasswordConfirmation', 'newpassword123')
        ->call('changePassword')
        ->assertSet('currentPassword', '')
        ->assertSet('newPassword', '')
        ->assertSet('newPasswordConfirmation', '');
});
