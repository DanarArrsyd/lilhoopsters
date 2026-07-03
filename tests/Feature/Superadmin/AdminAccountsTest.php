<?php

use App\Livewire\Superadmin\AdminAccounts;
use App\Models\AuditLog;
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

it('renders admin accounts page', function () {
    $this->actingAs($this->superAdmin)
        ->get(route('superadmin.admins'))
        ->assertOk();
});

it('non-superadmin cannot access admin accounts page', function () {
    $admin = User::factory()->withRole('admin')->approved()->create();
    $this->actingAs($admin)
        ->get(route('superadmin.admins'))
        ->assertForbidden();
});

it('shows empty state when no admins', function () {
    Livewire::actingAs($this->superAdmin)
        ->test(AdminAccounts::class)
        ->assertSee('No admin accounts found');
});

it('can create a new admin account', function () {
    Livewire::actingAs($this->superAdmin)
        ->test(AdminAccounts::class)
        ->call('openForm')
        ->set('name', 'New Admin')
        ->set('email', 'newadmin@test.com')
        ->set('password', 'password123')
        ->set('passwordConfirmation', 'password123')
        ->call('create');

    $admin = User::whereHas('role', fn($q) => $q->where('name', 'admin'))
        ->where('email', 'newadmin@test.com')
        ->first();

    expect($admin)->not->toBeNull();
    expect($admin->is_active)->toBe(true);
    expect($admin->registration_status)->toBe('approved');
});

it('validates required fields on create', function () {
    Livewire::actingAs($this->superAdmin)
        ->test(AdminAccounts::class)
        ->call('openForm')
        ->call('create')
        ->assertHasErrors(['name', 'email', 'password']);
});

it('validates unique email', function () {
    $existing = User::factory()->withRole('admin')->approved()->create(['email' => 'taken@test.com']);

    Livewire::actingAs($this->superAdmin)
        ->test(AdminAccounts::class)
        ->call('openForm')
        ->set('name', 'Another Admin')
        ->set('email', 'taken@test.com')
        ->set('password', 'password123')
        ->set('passwordConfirmation', 'password123')
        ->call('create')
        ->assertHasErrors(['email']);
});

it('can toggle admin active status', function () {
    $admin = User::factory()->withRole('admin')->approved()->create(['is_active' => true]);

    Livewire::actingAs($this->superAdmin)
        ->test(AdminAccounts::class)
        ->call('toggleActive', $admin->id);

    expect($admin->fresh()->is_active)->toBe(false);

    Livewire::actingAs($this->superAdmin)
        ->test(AdminAccounts::class)
        ->call('toggleActive', $admin->id);

    expect($admin->fresh()->is_active)->toBe(true);
});

it('lists existing admins', function () {
    User::factory()->withRole('admin')->approved()->create(['name' => 'Visible Admin']);

    Livewire::actingAs($this->superAdmin)
        ->test(AdminAccounts::class)
        ->assertSee('Visible Admin');
});

it('records an audit log when an admin account is created', function () {
    Livewire::actingAs($this->superAdmin)
        ->test(AdminAccounts::class)
        ->set('name', 'New Admin')
        ->set('email', 'newadmin@example.com')
        ->set('password', 'password123')
        ->set('passwordConfirmation', 'password123')
        ->call('create');

    $log = AuditLog::where('action', 'admin_account.created')->first();
    expect($log)->not->toBeNull();
    expect($log->description)->toContain('New Admin');
});

it('records an audit log when an admin account is deactivated via toggle', function () {
    $admin = User::factory()->withRole('admin')->approved()->create(['is_active' => true]);

    Livewire::actingAs($this->superAdmin)
        ->test(AdminAccounts::class)
        ->call('toggleActive', $admin->id);

    $log = AuditLog::where('action', 'admin_account.deactivated')->first();
    expect($log)->not->toBeNull();
    expect($log->subject_id)->toBe($admin->id);
});

it('records an audit log when an admin account is reactivated via toggle', function () {
    $admin = User::factory()->withRole('admin')->approved()->create(['is_active' => false]);

    Livewire::actingAs($this->superAdmin)
        ->test(AdminAccounts::class)
        ->call('toggleActive', $admin->id);

    $log = AuditLog::where('action', 'admin_account.activated')->first();
    expect($log)->not->toBeNull();
    expect($log->subject_id)->toBe($admin->id);
});

it('records an audit log when an admin account is deactivated via the confirm dialog', function () {
    $admin = User::factory()->withRole('admin')->approved()->create(['is_active' => true]);

    Livewire::actingAs($this->superAdmin)
        ->test(AdminAccounts::class)
        ->call('confirmDeactivate', $admin->id)
        ->call('deactivate');

    expect(AuditLog::where('action', 'admin_account.deactivated')->where('subject_id', $admin->id)->exists())->toBeTrue();
});
