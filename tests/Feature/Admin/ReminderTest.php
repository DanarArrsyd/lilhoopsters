<?php
// tests/Feature/Admin/ReminderTest.php

use App\Livewire\Admin\Owner;
use App\Models\Child;
use App\Models\Enrollment;
use App\Models\Location;
use App\Models\Notification;
use App\Models\Package;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ReminderService;
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
    $this->package  = Package::factory()->regular()->create(['location_id' => $this->location->id]);
});

function expiringEnrollment($package): Enrollment
{
    $child = Child::factory()->create();

    return Enrollment::factory()->program()->approved()->create([
        'child_id'           => $child->id,
        'package_id'         => $package->id,
        'expires_at'         => today()->addDays(5),
        'remaining_sessions' => 2,
    ]);
}

it('command creates renewal reminder for expiring enrollment', function () {
    $e = expiringEnrollment($this->package);

    $this->artisan('reminders:send')->assertSuccessful();

    $note = Notification::where('type', ReminderService::RENEWAL)->first();
    expect($note)->not->toBeNull();
    expect($note->user_id)->toBe($e->child->user_id);
    expect($note->data['enrollment_id'])->toBe($e->id);
});

it('does not remind a payment less than 4 days old (not due soon yet)', function () {
    Transaction::factory()->create([
        'package_id' => $this->package->id,
        'amount'     => 200000,
        'created_at' => today()->subDays(2),
    ]);

    $this->artisan('reminders:send')->assertSuccessful();

    expect(Notification::where('type', ReminderService::PAYMENT)->count())->toBe(0);
});

it('reminds a payment 4-7 days old (within 3 days of the implicit due date)', function () {
    $t = Transaction::factory()->create([
        'package_id' => $this->package->id,
        'amount'     => 200000,
        'created_at' => today()->subDays(5),
    ]);

    $this->artisan('reminders:send')->assertSuccessful();

    $note = Notification::where('type', ReminderService::PAYMENT)->first();
    expect($note)->not->toBeNull();
    expect($note->user_id)->toBe($t->user_id);
    expect($note->data['transaction_id'])->toBe($t->id);
});

it('does not remind a payment older than 7 days (already past the auto-expiry window)', function () {
    Transaction::factory()->create([
        'package_id' => $this->package->id,
        'amount'     => 200000,
        'created_at' => today()->subDays(9),
    ]);

    $this->artisan('reminders:send')->assertSuccessful();

    expect(Notification::where('type', ReminderService::PAYMENT)->count())->toBe(0);
});

it('does not duplicate reminders within cooldown', function () {
    expiringEnrollment($this->package);

    $this->artisan('reminders:send')->assertSuccessful();
    $this->artisan('reminders:send')->assertSuccessful();

    expect(Notification::where('type', ReminderService::RENEWAL)->count())->toBe(1);
});

it('manual renewal reminder bypasses cooldown', function () {
    $e = expiringEnrollment($this->package);

    // Auto run first — creates one.
    $this->artisan('reminders:send')->assertSuccessful();

    // Owner clicks the button — force send creates a second.
    Livewire::actingAs($this->admin)->test(Owner::class)
        ->call('sendRenewalReminder', $e->id)
        ->assertSee('sent');

    expect(Notification::where('type', ReminderService::RENEWAL)->count())->toBe(2);
});

it('manual payment reminder creates a notification', function () {
    $t = Transaction::factory()->create([
        'package_id' => $this->package->id,
        'amount'     => 175000,
    ]);

    Livewire::actingAs($this->admin)->test(Owner::class)
        ->call('sendPaymentReminder', $t->id);

    expect(Notification::where('type', ReminderService::PAYMENT)
        ->where('user_id', $t->user_id)->exists())->toBeTrue();
});
