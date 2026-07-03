<?php

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\Transaction;
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

it('records an audit log entry with the authenticated actor snapshot', function () {
    $admin = User::factory()->withRole('admin')->approved()->create(['name' => 'Eka Danar']);
    $this->actingAs($admin);

    $transaction = Transaction::factory()->create();

    $log = AuditLog::record('payment.verified', $transaction, 'Verified payment TRX-TEST', ['old_status' => 'pending', 'new_status' => 'paid']);

    expect($log->actor_id)->toBe($admin->id);
    expect($log->actor_name)->toBe('Eka Danar');
    expect($log->actor_role)->toBe('admin');
    expect($log->action)->toBe('payment.verified');
    expect($log->subject_type)->toBe(Transaction::class);
    expect($log->subject_id)->toBe($transaction->id);
    expect($log->description)->toBe('Verified payment TRX-TEST');
    expect($log->meta)->toBe(['old_status' => 'pending', 'new_status' => 'paid']);
});

it('falls back to a system actor when there is no authenticated user', function () {
    $log = AuditLog::record('system_settings.updated', null, 'Updated system settings');

    expect($log->actor_id)->toBeNull();
    expect($log->actor_name)->toBe('System');
    expect($log->actor_role)->toBe('system');
    expect($log->subject_type)->toBeNull();
    expect($log->subject_id)->toBeNull();
    expect($log->meta)->toBeNull();
});
