<?php

use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Backup\Events\BackupHasFailed;
use Spatie\Backup\Events\CleanupHasFailed;

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

it('notifies super admins when a backup fails', function () {
    event(new BackupHasFailed(new \Exception('disk full')));

    $note = Notification::where('user_id', $this->superAdmin->id)
        ->where('type', 'backup_failed')
        ->first();

    expect($note)->not->toBeNull();
    expect($note->body)->toContain('disk full');
});

it('notifies super admins when cleanup fails', function () {
    event(new CleanupHasFailed(new \Exception('permission denied')));

    $note = Notification::where('user_id', $this->superAdmin->id)
        ->where('type', 'backup_cleanup_failed')
        ->first();

    expect($note)->not->toBeNull();
    expect($note->body)->toContain('permission denied');
});

it('does not notify admin or coach roles, only super_admin', function () {
    $admin = User::factory()->withRole('admin')->approved()->create();

    event(new BackupHasFailed(new \Exception('disk full')));

    expect(Notification::where('user_id', $admin->id)->exists())->toBeFalse();
});
