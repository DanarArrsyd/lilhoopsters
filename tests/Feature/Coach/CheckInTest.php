<?php

use App\Livewire\Coach\CheckIn;
use App\Models\Coach;
use App\Models\CoachAttendance;
use App\Models\Location;
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

    $this->coachUser = User::factory()->withRole('coach')->approved()->create();
    $this->coach     = Coach::factory()->create(['user_id' => $this->coachUser->id]);
    $this->location  = Location::factory()->create(['is_active' => true]);
});

it('renders the check-in page', function () {
    $this->actingAs($this->coachUser)
        ->get(route('coach.checkin'))
        ->assertOk();
});

it('can check in to a location', function () {
    Livewire::actingAs($this->coachUser)
        ->test(CheckIn::class)
        ->set('locationId', $this->location->id)
        ->call('checkIn');

    expect(CoachAttendance::where('coach_id', $this->coach->id)->count())->toBe(1);
    expect($this->coach->isCheckedIn())->toBeTrue();
});

it('cannot check in twice', function () {
    CoachAttendance::create([
        'coach_id'      => $this->coach->id,
        'location_id'   => $this->location->id,
        'checked_in_at' => now(),
        'expires_at'    => now()->addHours(8),
    ]);

    Livewire::actingAs($this->coachUser)
        ->test(CheckIn::class)
        ->set('locationId', $this->location->id)
        ->call('checkIn')
        ->assertHasErrors(['locationId']);

    expect(CoachAttendance::where('coach_id', $this->coach->id)->count())->toBe(1);
});

it('can check out', function () {
    $checkin = CoachAttendance::create([
        'coach_id'      => $this->coach->id,
        'location_id'   => $this->location->id,
        'checked_in_at' => now(),
        'expires_at'    => now()->addHours(8),
    ]);

    Livewire::actingAs($this->coachUser)
        ->test(CheckIn::class)
        ->call('checkOut');

    expect($checkin->fresh()->checked_out_at)->not->toBeNull();
    expect($this->coach->fresh()->isCheckedIn())->toBeFalse();
});

it('requires location to check in', function () {
    Livewire::actingAs($this->coachUser)
        ->test(CheckIn::class)
        ->set('locationId', '')
        ->call('checkIn')
        ->assertHasErrors(['locationId']);
});
