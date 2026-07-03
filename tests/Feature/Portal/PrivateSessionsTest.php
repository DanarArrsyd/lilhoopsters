<?php

use App\Livewire\Portal\PrivateSessions;
use App\Models\Child;
use App\Models\Coach;
use App\Models\Enrollment;
use App\Models\Location;
use App\Models\Package;
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
    $this->child    = Child::factory()->active()->create(['user_id' => $this->parent->id]);
    $this->location = Location::factory()->create(['is_active' => true]);

    // A coach-agnostic private slot template (coach_id = null).
    $this->template = Schedule::factory()->create([
        'type'        => 'private',
        'coach_id'    => null,
        'location_id' => $this->location->id,
        'program_id'  => null,
        'day_of_week' => 'monday',
        'start_time'  => '16:00',
        'end_time'    => '17:00',
        'max_capacity'=> 1,
        'is_active'   => true,
    ]);

    $this->package = Package::factory()->create([
        'type'        => 'private',
        'location_id' => $this->location->id,
        'is_active'   => true,
        'session_count' => 1,
    ]);

    // Two active coaches, neither tied to any private schedule.
    $this->coachA = Coach::factory()->create();
    $this->coachB = Coach::factory()->create();
});

it('offers every active coach regardless of existing schedules', function () {
    Livewire::actingAs($this->parent)
        ->test(PrivateSessions::class)
        ->call('selectChild', $this->child->id)
        ->call('selectLocation', $this->location->id)
        ->assertSee($this->coachA->user->name)
        ->assertSee($this->coachB->user->name);
});

it('materialises a concrete coach-bound schedule when booking', function () {
    Livewire::actingAs($this->parent)
        ->test(PrivateSessions::class)
        ->call('selectChild', $this->child->id)
        ->call('selectLocation', $this->location->id)
        ->call('selectCoach', $this->coachA->id)
        ->call('selectDay', 'monday')
        ->call('selectSchedule', $this->template->id)
        ->set('selectedPackageId', $this->package->id)
        ->call('confirmDetails')
        ->call('submit');

    // A concrete schedule was created for the chosen coach from the template.
    $concrete = Schedule::where('type', 'private')
        ->where('coach_id', $this->coachA->id)
        ->where('location_id', $this->location->id)
        ->where('day_of_week', 'monday')
        ->first();

    expect($concrete)->not->toBeNull();
    expect($concrete->id)->not->toBe($this->template->id);

    // Enrollment + pending transaction point at the concrete schedule.
    expect(Enrollment::where('schedule_id', $concrete->id)->where('child_id', $this->child->id)->exists())->toBeTrue();
    expect(Transaction::where('user_id', $this->parent->id)->where('status', 'pending')->exists())->toBeTrue();
});

it('reuses the same concrete schedule for the same coach and slot', function () {
    $book = function () {
        return Livewire::actingAs($this->parent)
            ->test(PrivateSessions::class)
            ->call('selectChild', $this->child->id)
            ->call('selectLocation', $this->location->id)
            ->call('selectCoach', $this->coachA->id)
            ->call('selectDay', 'monday')
            ->call('selectSchedule', $this->template->id)
            ->set('selectedPackageId', $this->package->id)
            ->call('confirmDetails')
            ->call('submit');
    };

    $book();

    expect(Schedule::where('type', 'private')->where('coach_id', $this->coachA->id)->count())->toBe(1);
});

it('only lists locations that have private slot templates', function () {
    $emptyLocation = Location::factory()->create(['is_active' => true, 'name' => 'No Private Here']);

    Livewire::actingAs($this->parent)
        ->test(PrivateSessions::class)
        ->call('selectChild', $this->child->id)
        ->assertSee($this->location->name)
        ->assertDontSee('No Private Here');
});
