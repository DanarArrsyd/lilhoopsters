<?php

use App\Livewire\Coach\Schedules;
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

    $this->coachUser = User::factory()->withRole('coach')->approved()->create();
    $this->coach     = Coach::factory()->create(['user_id' => $this->coachUser->id]);
});

it('renders the schedules page', function () {
    $this->actingAs($this->coachUser)
        ->get(route('coach.schedules'))
        ->assertOk();
});

it('shows empty state when no schedules assigned', function () {
    Livewire::actingAs($this->coachUser)
        ->test(Schedules::class)
        ->assertSee('No schedules assigned yet');
});

it('shows schedules assigned to the coach', function () {
    $schedule = Schedule::factory()->create([
        'coach_id'    => $this->coach->id,
        'day_of_week' => 'monday',
        'is_active'   => true,
    ]);

    Livewire::actingAs($this->coachUser)
        ->test(Schedules::class)
        ->assertSee($schedule->program->name)
        ->assertSee($schedule->location->name);
});

it('does not show schedules from other coaches', function () {
    $otherCoachUser = User::factory()->withRole('coach')->approved()->create();
    $otherCoach     = Coach::factory()->create(['user_id' => $otherCoachUser->id]);
    $schedule       = Schedule::factory()->create(['coach_id' => $otherCoach->id]);

    Livewire::actingAs($this->coachUser)
        ->test(Schedules::class)
        ->assertDontSee($schedule->program->name);
});

it('groups schedules by day', function () {
    Schedule::factory()->create(['coach_id' => $this->coach->id, 'day_of_week' => 'monday']);
    Schedule::factory()->create(['coach_id' => $this->coach->id, 'day_of_week' => 'wednesday']);

    Livewire::actingAs($this->coachUser)
        ->test(Schedules::class)
        ->assertSee(__('messages.coach.days.monday'))
        ->assertSee(__('messages.coach.days.wednesday'));
});
