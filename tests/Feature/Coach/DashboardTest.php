<?php

use App\Livewire\Coach\Dashboard;
use App\Models\Coach;
use App\Models\Role;
use App\Models\Schedule;
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

    $this->coachRole = Role::where('name', 'coach')->first();
    $this->coachUser = User::factory()->withRole('coach')->approved()->create();
    $this->coach     = Coach::factory()->create(['user_id' => $this->coachUser->id]);
});

it('renders the coach dashboard page', function () {
    $this->actingAs($this->coachUser)
        ->get(route('coach.dashboard'))
        ->assertOk();
});

it('non-coach cannot access coach dashboard', function () {
    $parent = User::factory()->withRole('parent')->approved()->create();

    $this->actingAs($parent)
        ->get(route('coach.dashboard'))
        ->assertForbidden();
});

it('shows zero stats when coach has no schedules', function () {
    $component = Livewire::actingAs($this->coachUser)->test(Dashboard::class);

    $component->assertSet('stats.total_schedules', 0)
              ->assertSet('stats.today_schedules', 0)
              ->assertSet('stats.active_students', 0);
});

it('counts total active schedules', function () {
    Schedule::factory()->count(3)->create(['coach_id' => $this->coach->id, 'is_active' => true]);
    Schedule::factory()->create(['coach_id' => $this->coach->id, 'is_active' => false]);

    $component = Livewire::actingAs($this->coachUser)->test(Dashboard::class);

    $component->assertSet('stats.total_schedules', 3);
});

it('counts today schedules correctly', function () {
    $today     = strtolower(now()->format('l'));
    $tomorrow  = strtolower(now()->addDay()->format('l'));

    Schedule::factory()->create(['coach_id' => $this->coach->id, 'day_of_week' => $today, 'is_active' => true]);
    Schedule::factory()->create(['coach_id' => $this->coach->id, 'day_of_week' => $today, 'is_active' => true]);
    Schedule::factory()->create(['coach_id' => $this->coach->id, 'day_of_week' => $tomorrow, 'is_active' => true]);

    $component = Livewire::actingAs($this->coachUser)->test(Dashboard::class);

    $component->assertSet('stats.today_schedules', 2);
});

it('shows today schedules in the view', function () {
    $today = strtolower(now()->format('l'));

    $schedule = Schedule::factory()->create([
        'coach_id'    => $this->coach->id,
        'day_of_week' => $today,
        'is_active'   => true,
        'start_time'  => '09:00:00',
        'end_time'    => '10:00:00',
    ]);

    Livewire::actingAs($this->coachUser)
        ->test(Dashboard::class)
        ->assertSee($schedule->program->name);
});

it('shows no sessions message when no schedules today', function () {
    $notToday = strtolower(now()->addDay()->format('l'));
    Schedule::factory()->create(['coach_id' => $this->coach->id, 'day_of_week' => $notToday, 'is_active' => true]);

    Livewire::actingAs($this->coachUser)
        ->test(Dashboard::class)
        ->assertSee('No sessions today.');
});

it('shows a maps button for a schedule location that has a maps url', function () {
    $location = \App\Models\Location::factory()->create(['maps_url' => 'https://maps.google.com/?q=coach-test']);
    $schedule = \App\Models\Schedule::factory()->create([
        'coach_id'    => $this->coach->id,
        'location_id' => $location->id,
        'day_of_week' => strtolower(now()->format('l')),
    ]);

    Livewire::actingAs($this->coachUser)
        ->test(\App\Livewire\Coach\Dashboard::class)
        ->assertSee('https://maps.google.com/?q=coach-test', false);
});
