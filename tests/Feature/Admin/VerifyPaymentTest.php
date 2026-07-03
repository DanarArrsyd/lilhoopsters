<?php

use App\Livewire\Admin\VerifyPayment;
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

    $this->admin   = User::factory()->withRole('admin')->approved()->create();
    $this->parent  = User::factory()->withRole('parent')->approved()->create();
    $this->package = Package::factory()->create();
});

it('renders the verify payment page for admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.verify-payment'))
        ->assertOk()
        ->assertSeeLivewire(VerifyPayment::class);
});

it('non-admin cannot access verify payment', function () {
    $this->actingAs($this->parent)
        ->get(route('admin.verify-payment'))
        ->assertForbidden();
});

it('verifies a valid paid transaction by code', function () {
    $trx = Transaction::factory()->paid()->create([
        'user_id'    => $this->parent->id,
        'package_id' => $this->package->id,
    ]);

    Livewire::actingAs($this->admin)
        ->test(VerifyPayment::class)
        ->set('manualCode', $trx->transaction_code)
        ->call('verifyManual')
        ->assertSet('resultStatus', 'valid')
        ->assertSee($this->parent->name);
});

it('flags a non-paid transaction as invalid', function () {
    $trx = Transaction::factory()->create([
        'user_id'    => $this->parent->id,
        'package_id' => $this->package->id,
        'status'     => 'pending',
    ]);

    Livewire::actingAs($this->admin)
        ->test(VerifyPayment::class)
        ->set('manualCode', $trx->transaction_code)
        ->call('verifyManual')
        ->assertSet('resultStatus', 'unpaid');
});

it('reports not found for an unknown code', function () {
    Livewire::actingAs($this->admin)
        ->test(VerifyPayment::class)
        ->set('manualCode', 'TRX-NOPE1234')
        ->call('verifyManual')
        ->assertSet('resultStatus', 'not_found');
});

it('strips a URL wrapper and still resolves the code', function () {
    $trx = Transaction::factory()->paid()->create([
        'user_id'    => $this->parent->id,
        'package_id' => $this->package->id,
    ]);

    Livewire::actingAs($this->admin)
        ->test(VerifyPayment::class)
        ->call('processQr', 'https://example.test/verify/' . $trx->transaction_code)
        ->assertSet('resultStatus', 'valid');
});
