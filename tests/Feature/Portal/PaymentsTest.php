<?php

use App\Livewire\Portal\Payments;
use App\Models\Child;
use App\Models\Location;
use App\Models\Package;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::insert([
        ['name' => 'super_admin', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'admin',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'coach',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'parent',      'created_at' => now(), 'updated_at' => now()],
    ]);

    $this->parent   = User::factory()->withRole('parent')->approved()->create();
    $this->location = Location::factory()->create();
    $this->package  = Package::factory()->registration()->create(['location_id' => $this->location->id]);
    $this->child    = Child::factory()->create(['user_id' => $this->parent->id]);
});


it('shows only own transactions', function () {
    $trx = Transaction::factory()->create([
        'user_id'    => $this->parent->id,
        'child_id'   => $this->child->id,
        'package_id' => $this->package->id,
    ]);

    $other    = User::factory()->withRole('parent')->approved()->create();
    $otherTrx = Transaction::factory()->create([
        'user_id'    => $other->id,
        'package_id' => $this->package->id,
    ]);

    Livewire::actingAs($this->parent)
        ->test(Payments::class)
        ->assertSee($trx->transaction_code)
        ->assertDontSee($otherTrx->transaction_code);
});

it('can filter by status', function () {
    $pending = Transaction::factory()->create([
        'user_id'    => $this->parent->id,
        'package_id' => $this->package->id,
        'status'     => 'pending',
    ]);
    $paid = Transaction::factory()->paid()->create([
        'user_id'    => $this->parent->id,
        'package_id' => $this->package->id,
    ]);

    Livewire::actingAs($this->parent)
        ->test(Payments::class)
        ->set('filterStatus', 'pending')
        ->assertSee($pending->transaction_code)
        ->assertDontSee($paid->transaction_code);
});

it('can upload payment proof', function () {
    Storage::fake('public');

    $trx = Transaction::factory()->create([
        'user_id'    => $this->parent->id,
        'child_id'   => $this->child->id,
        'package_id' => $this->package->id,
        'status'     => 'pending',
    ]);

    $file = UploadedFile::fake()->image('proof.jpg');

    Livewire::actingAs($this->parent)
        ->test(Payments::class)
        ->call('openUpload', $trx->id)
        ->set('proofFile', $file)
        ->set('agreedToTnc', true)
        ->call('uploadProof');

    $trx->refresh();
    expect($trx->payment_proof)->not->toBeNull();

    Storage::disk('public')->assertExists($trx->payment_proof);
});

it('requires a file when uploading proof', function () {
    $trx = Transaction::factory()->create([
        'user_id'    => $this->parent->id,
        'package_id' => $this->package->id,
        'status'     => 'pending',
    ]);

    Livewire::actingAs($this->parent)
        ->test(Payments::class)
        ->call('openUpload', $trx->id)
        ->call('uploadProof')
        ->assertHasErrors(['proofFile']);
});

it('cannot upload proof for another parents transaction', function () {
    $other = User::factory()->withRole('parent')->approved()->create();
    $trx   = Transaction::factory()->create([
        'user_id'    => $other->id,
        'package_id' => $this->package->id,
        'status'     => 'pending',
    ]);

    Livewire::actingAs($this->parent)
        ->test(Payments::class)
        ->call('openUpload', $trx->id);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

it('can cancel upload modal', function () {
    $trx = Transaction::factory()->create([
        'user_id'    => $this->parent->id,
        'package_id' => $this->package->id,
    ]);

    Livewire::actingAs($this->parent)
        ->test(Payments::class)
        ->call('openUpload', $trx->id)
        ->assertSet('uploadingId', $trx->id)
        ->call('cancelUpload')
        ->assertSet('uploadingId', null);
});

it('lets the owner download a receipt for a paid transaction', function () {
    $trx = Transaction::factory()->paid()->create([
        'user_id'    => $this->parent->id,
        'child_id'   => $this->child->id,
        'package_id' => $this->package->id,
    ]);

    $res = $this->actingAs($this->parent)
        ->get(route('parent.payments.receipt', $trx));

    $res->assertOk();
    expect($res->headers->get('content-type'))->toContain('application/pdf');
    expect($res->headers->get('content-disposition'))->toContain("Receipt-{$trx->transaction_code}.pdf");
});

it('blocks receipt download for a non-paid transaction', function () {
    $trx = Transaction::factory()->create([
        'user_id'    => $this->parent->id,
        'child_id'   => $this->child->id,
        'package_id' => $this->package->id,
        'status'     => 'pending',
    ]);

    $this->actingAs($this->parent)
        ->get(route('parent.payments.receipt', $trx))
        ->assertNotFound();
});

it('blocks receipt download for another parents transaction', function () {
    $other    = User::factory()->withRole('parent')->approved()->create();
    $otherTrx = Transaction::factory()->paid()->create([
        'user_id'    => $other->id,
        'package_id' => $this->package->id,
    ]);

    $this->actingAs($this->parent)
        ->get(route('parent.payments.receipt', $otherTrx))
        ->assertForbidden();
});
