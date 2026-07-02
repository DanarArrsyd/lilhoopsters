<?php

use App\Livewire\Portal\EnrollPlayer;
use App\Models\Child;
use App\Models\Coach;
use App\Models\Enrollment;
use App\Models\Location;
use App\Models\Package;
use App\Models\Program;
use App\Models\Role;
use App\Models\Schedule;
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

    $this->parent   = User::factory()->withRole('parent')->approved()->create();
    $this->location = Location::factory()->create();
    $this->program  = Program::factory()->create();
    $this->package  = Package::factory()->registration()->create(['location_id' => $this->location->id]);
});

it('renders enroll page for parent', function () {
    $this->actingAs($this->parent)
        ->get(route('parent.enroll'))
        ->assertOk();
});

it('shows only unregistered and active children in step 1', function () {
    Child::factory()->create(['user_id' => $this->parent->id, 'name' => 'Unregistered Kid', 'status' => 'unregistered']);
    Child::factory()->active()->create(['user_id' => $this->parent->id, 'name' => 'Active Kid']);
    Child::factory()->create(['user_id' => $this->parent->id, 'name' => 'Pending Kid', 'status' => 'pending']);

    Livewire::actingAs($this->parent)
        ->test(EnrollPlayer::class)
        ->assertSee('Unregistered Kid')
        ->assertSee('Active Kid')
        ->assertDontSee('Pending Kid');
});

it('advances to step 2 after selecting unregistered child', function () {
    $child = Child::factory()->create(['user_id' => $this->parent->id, 'status' => 'unregistered']);

    Livewire::actingAs($this->parent)
        ->test(EnrollPlayer::class)
        ->call('selectChild', $child->id)
        ->assertSet('step', 2)
        ->assertSet('enrollmentType', 'registration');
});

it('advances to step 2 after selecting active child', function () {
    $child = Child::factory()->active()->create(['user_id' => $this->parent->id]);

    Livewire::actingAs($this->parent)
        ->test(EnrollPlayer::class)
        ->call('selectChild', $child->id)
        ->assertSet('step', 2)
        ->assertSet('enrollmentType', 'program');
});

it('cannot select another parents child', function () {
    $other = User::factory()->withRole('parent')->approved()->create();
    $child = Child::factory()->create(['user_id' => $other->id, 'status' => 'unregistered']);

    Livewire::actingAs($this->parent)
        ->test(EnrollPlayer::class)
        ->call('selectChild', $child->id);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

it('can submit registration enrollment', function () {
    $child = Child::factory()->create(['user_id' => $this->parent->id, 'status' => 'unregistered']);

    Livewire::actingAs($this->parent)
        ->test(EnrollPlayer::class)
        ->call('selectChild', $child->id)
        ->set('selectedLocationId', $this->location->id)
        ->set('selectedPackageId', $this->package->id)
        ->set('jerseyName', 'BUDI')
        ->set('jerseyNumber', '10')
        ->call('submit');

    $this->assertDatabaseHas('enrollments', [
        'child_id'   => $child->id,
        'type'       => 'registration',
        'package_id' => $this->package->id,
        'status'     => 'pending',
    ]);

    $this->assertDatabaseHas('transactions', [
        'user_id'    => $this->parent->id,
        'child_id'   => $child->id,
        'package_id' => $this->package->id,
        'amount'     => $this->package->price,
        'status'     => 'pending',
    ]);

    expect($child->fresh()->status)->toBe('pending');
    expect($child->fresh()->jersey_name)->toBe('BUDI');
    expect($child->fresh()->jersey_number)->toBe('10');
});

it('requires package to be selected before submitting', function () {
    $child = Child::factory()->create(['user_id' => $this->parent->id, 'status' => 'unregistered']);

    Livewire::actingAs($this->parent)
        ->test(EnrollPlayer::class)
        ->call('selectChild', $child->id)
        ->set('selectedLocationId', $this->location->id)
        ->call('submit')
        ->assertHasErrors(['selectedPackageId']);
});

it('can submit program enrollment after selecting schedule', function () {
    $coachUser  = User::factory()->withRole('coach')->approved()->create();
    $coach      = Coach::factory()->create(['user_id' => $coachUser->id]);
    $schedule   = Schedule::factory()->create([
        'location_id' => $this->location->id,
        'program_id'  => $this->program->id,
        'coach_id'    => $coach->id,
        'is_active'   => true,
    ]);
    $programPkg = Package::factory()->regular()->create(['location_id' => $this->location->id]);
    $child      = Child::factory()->active()->create(['user_id' => $this->parent->id]);

    Livewire::actingAs($this->parent)
        ->test(EnrollPlayer::class)
        ->call('selectChild', $child->id)
        ->call('selectSchedule', $schedule->id)
        ->set('selectedPackageId', $programPkg->id)
        ->call('submit');

    $this->assertDatabaseHas('enrollments', [
        'child_id'    => $child->id,
        'type'        => 'program',
        'schedule_id' => $schedule->id,
        'package_id'  => $programPkg->id,
        'status'      => 'pending',
    ]);
});

it('redirects to home after successful submission', function () {
    $child = Child::factory()->create(['user_id' => $this->parent->id, 'status' => 'unregistered']);

    Livewire::actingAs($this->parent)
        ->test(EnrollPlayer::class)
        ->call('selectChild', $child->id)
        ->set('selectedLocationId', $this->location->id)
        ->set('selectedPackageId', $this->package->id)
        ->call('submit')
        ->assertRedirect(route('parent.home'));
});

it('can go back from step 2 to step 1', function () {
    $child = Child::factory()->create(['user_id' => $this->parent->id, 'status' => 'unregistered']);

    Livewire::actingAs($this->parent)
        ->test(EnrollPlayer::class)
        ->call('selectChild', $child->id)
        ->assertSet('step', 2)
        ->call('back')
        ->assertSet('step', 1);
});

it('transaction is linked to enrollment', function () {
    $child = Child::factory()->create(['user_id' => $this->parent->id, 'status' => 'unregistered']);

    Livewire::actingAs($this->parent)
        ->test(EnrollPlayer::class)
        ->call('selectChild', $child->id)
        ->set('selectedLocationId', $this->location->id)
        ->set('selectedPackageId', $this->package->id)
        ->call('submit');

    $enrollment  = Enrollment::where('child_id', $child->id)->first();
    $transaction = Transaction::where('child_id', $child->id)->first();

    expect($enrollment->transaction_id)->toBe($transaction->id);
    expect($transaction->enrollment_id)->toBe($enrollment->id);
});
