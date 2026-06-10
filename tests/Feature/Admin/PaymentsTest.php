<?php

use App\Livewire\Admin\Payments;
use App\Models\Location;
use App\Models\Package;
use App\Models\Role;
use App\Models\Transaction;
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

    $this->admin    = User::factory()->withRole('admin')->approved()->create();
    $this->location = Location::factory()->create();
    $this->package  = Package::factory()->registration()->create(['location_id' => $this->location->id]);
});

it('renders payments page for admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.payments'))
        ->assertOk();
});

it('non-admin cannot access payments page', function () {
    $parent = User::factory()->withRole('parent')->approved()->create();

    $this->actingAs($parent)
        ->get(route('admin.payments'))
        ->assertForbidden();
});

it('shows transactions with transaction code', function () {
    $trx = Transaction::factory()->create(['package_id' => $this->package->id]);

    Livewire::actingAs($this->admin)
        ->test(Payments::class)
        ->assertSee($trx->transaction_code);
});

it('can filter payments by status', function () {
    $trxA = Transaction::factory()->create(['package_id' => $this->package->id, 'status' => 'pending']);
    $trxB = Transaction::factory()->paid()->create(['package_id' => $this->package->id]);

    Livewire::actingAs($this->admin)
        ->test(Payments::class)
        ->set('filterStatus', 'pending')
        ->assertSee($trxA->transaction_code)
        ->assertDontSee($trxB->transaction_code);
});

it('can search by transaction code', function () {
    $trxA = Transaction::factory()->create(['package_id' => $this->package->id]);
    $trxB = Transaction::factory()->create(['package_id' => $this->package->id]);

    Livewire::actingAs($this->admin)
        ->test(Payments::class)
        ->set('search', $trxA->transaction_code)
        ->assertSee($trxA->transaction_code)
        ->assertDontSee($trxB->transaction_code);
});

it('can verify a payment', function () {
    $trx = Transaction::factory()->create(['package_id' => $this->package->id, 'status' => 'pending']);

    Livewire::actingAs($this->admin)
        ->test(Payments::class)
        ->call('verify', $trx->id);

    expect($trx->fresh()->status)->toBe('paid');
    expect($trx->fresh()->verified_by)->toBe($this->admin->id);
    expect($trx->fresh()->paid_at)->not->toBeNull();
});

it('can reject a payment', function () {
    $trx = Transaction::factory()->create(['package_id' => $this->package->id, 'status' => 'pending']);

    Livewire::actingAs($this->admin)
        ->test(Payments::class)
        ->call('reject', $trx->id)
        ->call('confirmReject');

    expect($trx->fresh()->status)->toBe('rejected');
});

it('can add admin notes when rejecting', function () {
    $trx = Transaction::factory()->create(['package_id' => $this->package->id, 'status' => 'pending']);

    Livewire::actingAs($this->admin)
        ->test(Payments::class)
        ->set('rejectingId', $trx->id)
        ->set('adminNote', 'Bukti transfer tidak valid')
        ->call('confirmReject');

    expect($trx->fresh()->status)->toBe('rejected');
    expect($trx->fresh()->admin_notes)->toBe('Bukti transfer tidak valid');
});
