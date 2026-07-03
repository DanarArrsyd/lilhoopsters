<?php

use App\Livewire\Admin\AuditLog;
use App\Models\AuditLog as AuditLogModel;
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

    $this->admin      = User::factory()->withRole('admin')->approved()->create();
    $this->superAdmin = User::factory()->withRole('super_admin')->approved()->create();
});

it('renders the audit log page for admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.audit-log'))
        ->assertOk();
});

it('renders the audit log page for super_admin', function () {
    $this->actingAs($this->superAdmin)
        ->get(route('admin.audit-log'))
        ->assertOk();
});

it('coach and parent cannot access the audit log page', function () {
    $coach  = User::factory()->withRole('coach')->approved()->create();
    $parent = User::factory()->withRole('parent')->approved()->create();

    $this->actingAs($coach)->get(route('admin.audit-log'))->assertForbidden();
    $this->actingAs($parent)->get(route('admin.audit-log'))->assertForbidden();
});

it('lists audit log entries newest first', function () {
    AuditLogModel::record('payment.verified', null, 'First entry');
    AuditLogModel::record('payment.rejected', null, 'Second entry');

    Livewire::actingAs($this->admin)
        ->test(AuditLog::class)
        ->assertSeeInOrder(['Second entry', 'First entry']);
});

it('filters by action type', function () {
    AuditLogModel::record('payment.verified', null, 'A verified payment');
    AuditLogModel::record('enrollment.approved', null, 'An approved enrollment');

    Livewire::actingAs($this->admin)
        ->test(AuditLog::class)
        ->set('filterAction', 'payment.verified')
        ->assertSee('A verified payment')
        ->assertDontSee('An approved enrollment');
});

it('filters by search text over the description', function () {
    AuditLogModel::record('payment.verified', null, 'Verified payment for Widia');
    AuditLogModel::record('payment.verified', null, 'Verified payment for Kai');

    Livewire::actingAs($this->admin)
        ->test(AuditLog::class)
        ->set('search', 'Widia')
        ->assertSee('Verified payment for Widia')
        ->assertDontSee('Verified payment for Kai');
});
