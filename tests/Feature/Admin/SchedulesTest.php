<?php

use App\Livewire\Admin\Schedules;
use App\Models\Coach;
use App\Models\Location;
use App\Models\Program;
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

    $this->admin    = User::factory()->withRole('admin')->approved()->create();
    $this->location = Location::factory()->create(['name' => 'Pakubuwono']);
    $this->program  = Program::factory()->create(['name' => 'Junior']);
});

it('renders schedules page for admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.schedules'))
        ->assertOk();
});

it('non-admin cannot access schedules page', function () {
    $parent = User::factory()->withRole('parent')->approved()->create();

    $this->actingAs($parent)
        ->get(route('admin.schedules'))
        ->assertForbidden();
});

it('shows existing schedules', function () {
    Schedule::factory()->create([
        'location_id' => $this->location->id,
        'program_id'  => $this->program->id,
        'day_of_week' => 'saturday',
        'start_time'  => '09:00:00',
    ]);

    Livewire::actingAs($this->admin)
        ->test(Schedules::class)
        ->assertSee('Pakubuwono')
        ->assertSee('Junior');
});

it('can filter schedules by location', function () {
    $other        = Location::factory()->create(['name' => 'Kemang']);
    $progA        = Program::factory()->create(['name' => 'Junior A']);
    $progB        = Program::factory()->create(['name' => 'Rookie B']);

    Schedule::factory()->create(['location_id' => $this->location->id, 'program_id' => $progA->id]);
    Schedule::factory()->create(['location_id' => $other->id,          'program_id' => $progB->id]);

    Livewire::actingAs($this->admin)
        ->test(Schedules::class)
        ->set('filterLocation', $this->location->id)
        ->assertSee('Junior A')
        ->assertDontSee('Rookie B');
});

it('can create a schedule', function () {
    Livewire::actingAs($this->admin)
        ->test(Schedules::class)
        ->call('openCreate')
        ->set('location_id', $this->location->id)
        ->set('program_id', $this->program->id)
        ->set('day_of_week', 'saturday')
        ->set('start_time', '09:00')
        ->set('end_time', '10:00')
        ->set('max_capacity', 20)
        ->call('save')
        ->assertHasNoErrors();

    expect(Schedule::where([
        'location_id' => $this->location->id,
        'day_of_week' => 'saturday',
    ])->exists())->toBeTrue();
});

it('validates required fields on create', function () {
    Livewire::actingAs($this->admin)
        ->test(Schedules::class)
        ->call('openCreate')
        ->call('save')
        // start_time/end_time are composed from hour/minute/period dropdowns
        // (which have defaults), so they can't be empty — only these can.
        ->assertHasErrors(['location_id', 'program_id', 'day_of_week']);
});

it('can update a schedule', function () {
    $schedule = Schedule::factory()->create([
        'location_id' => $this->location->id,
        'program_id'  => $this->program->id,
        'max_capacity'=> 20,
    ]);

    Livewire::actingAs($this->admin)
        ->test(Schedules::class)
        ->call('openEdit', $schedule->id)
        ->set('max_capacity', 25)
        ->call('save')
        ->assertHasNoErrors();

    expect($schedule->fresh()->max_capacity)->toBe(25);
});

it('can toggle schedule active status', function () {
    $schedule = Schedule::factory()->create([
        'location_id' => $this->location->id,
        'program_id'  => $this->program->id,
        'is_active'   => true,
    ]);

    Livewire::actingAs($this->admin)
        ->test(Schedules::class)
        ->call('toggleActive', $schedule->id);

    expect($schedule->fresh()->is_active)->toBeFalse();
});

it('can delete a schedule', function () {
    $schedule = Schedule::factory()->create([
        'location_id' => $this->location->id,
        'program_id'  => $this->program->id,
    ]);

    Livewire::actingAs($this->admin)
        ->test(Schedules::class)
        ->call('confirmDelete', $schedule->id);

    expect(Schedule::find($schedule->id))->toBeNull();
});
