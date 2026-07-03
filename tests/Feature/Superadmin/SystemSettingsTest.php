<?php

use App\Livewire\Superadmin\SystemSettings;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\Setting;
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

it('renders system settings page', function () {
    $this->actingAs($this->superAdmin)
        ->get(route('superadmin.system-settings'))
        ->assertOk();
});

it('loads existing settings on mount', function () {
    Setting::set('academy_name', 'Test Academy');
    Setting::set('currency', 'USD');

    Livewire::actingAs($this->superAdmin)
        ->test(SystemSettings::class)
        ->assertSet('academyName', 'Test Academy')
        ->assertSet('currency', 'USD');
});

it('can save settings', function () {
    Livewire::actingAs($this->superAdmin)
        ->test(SystemSettings::class)
        ->set('academyName', 'Lil Hoopsters')
        ->set('academyEmail', 'info@lil.com')
        ->set('currency', 'IDR')
        ->set('timezone', 'Asia/Jakarta')
        ->call('save');

    expect(Setting::get('academy_name'))->toBe('Lil Hoopsters');
    expect(Setting::get('academy_email'))->toBe('info@lil.com');
    expect(Setting::get('currency'))->toBe('IDR');
});

it('validates required academy name', function () {
    Livewire::actingAs($this->superAdmin)
        ->test(SystemSettings::class)
        ->set('academyName', '')
        ->call('save')
        ->assertHasErrors(['academyName']);
});

it('validates email format', function () {
    Livewire::actingAs($this->superAdmin)
        ->test(SystemSettings::class)
        ->set('academyName', 'Academy')
        ->set('academyEmail', 'not-an-email')
        ->call('save')
        ->assertHasErrors(['academyEmail']);
});

it('records an audit log when settings are saved', function () {
    Livewire::actingAs($this->superAdmin)
        ->test(SystemSettings::class)
        ->set('academyName', 'Updated Academy Name')
        ->set('currency', 'IDR')
        ->set('timezone', 'Asia/Jakarta')
        ->call('save');

    $log = AuditLog::where('action', 'system_settings.updated')->first();
    expect($log)->not->toBeNull();
    expect($log->subject_type)->toBeNull();
    expect($log->meta['academy_name'])->toBe('Updated Academy Name');
});
