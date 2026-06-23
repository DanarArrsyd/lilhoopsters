<?php
// tests/Feature/Admin/TransactionExpiryTest.php

use App\Models\Enrollment;
use App\Models\Location;
use App\Models\Package;
use App\Models\Role;
use App\Models\Transaction;
use App\Services\TransactionExpiryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::insert([
        ['name' => 'super_admin', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'admin',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'coach',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'parent',      'created_at' => now(), 'updated_at' => now()],
    ]);

    $this->location = Location::factory()->create();
    $this->package  = Package::factory()->regular()->create(['location_id' => $this->location->id]);
});

it('expires a pending transaction older than the threshold', function () {
    $t = Transaction::factory()->create([
        'package_id' => $this->package->id,
        'created_at' => now()->subDays(10),
    ]);

    TransactionExpiryService::run(7);

    $t->refresh();
    expect($t->status)->toBe('expired');
    expect($t->expired_at)->not->toBeNull();
});

it('leaves recent pending transactions untouched', function () {
    $t = Transaction::factory()->create([
        'package_id' => $this->package->id,
        'created_at' => now()->subDays(2),
    ]);

    TransactionExpiryService::run(7);

    expect($t->fresh()->status)->toBe('pending');
});

it('does not touch paid transactions', function () {
    $t = Transaction::factory()->paid()->create([
        'package_id' => $this->package->id,
        'paid_at'    => now(),
        'created_at' => now()->subDays(30),
    ]);

    TransactionExpiryService::run(7);

    expect($t->fresh()->status)->toBe('paid');
});

it('cascades expiry to the pending enrollment', function () {
    $t = Transaction::factory()->create([
        'package_id' => $this->package->id,
        'created_at' => now()->subDays(10),
    ]);
    $enrollment = Enrollment::factory()->program()->create([
        'package_id'     => $this->package->id,
        'transaction_id' => $t->id,
        'status'         => 'pending',
    ]);

    $stats = TransactionExpiryService::run(7);

    expect($enrollment->fresh()->status)->toBe('expired');
    expect($stats['transactions'])->toBe(1);
    expect($stats['enrollments'])->toBe(1);
});

it('runs via the artisan command with a custom window', function () {
    Transaction::factory()->create([
        'package_id' => $this->package->id,
        'created_at' => now()->subDays(4),
    ]);

    // Default 7 days → nothing; --days=3 → expires it.
    $this->artisan('transactions:expire', ['--days' => 3])->assertSuccessful();

    expect(Transaction::where('status', 'expired')->count())->toBe(1);
});
