<?php
// tests/Feature/Admin/EventAttendanceTest.php

use App\Livewire\Admin\Events;
use App\Models\Child;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\Role;
use App\Models\User;
use App\Services\EventService;
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

    $this->admin  = User::factory()->withRole('admin')->approved()->create();
    $this->parent = User::factory()->withRole('parent')->approved()->create();
    $this->child  = Child::factory()->active()->create(['user_id' => $this->parent->id]);

    $this->event = Event::create([
        'name'            => 'Summer Champ',
        'start_date'      => today(),
        'end_date'        => today()->addDays(7),
        'is_active'       => true,
        'is_registerable' => true,
    ]);
});

it('marks a confirmed participant present', function () {
    EventService::register($this->event, $this->child); // free → confirmed

    Livewire::actingAs($this->admin)->test(Events::class)
        ->call('openParticipants', $this->event->id)
        ->set('attendanceDate', today()->toDateString())
        ->call('markAttendance', $this->child->id, 'present');

    $att = EventAttendance::where('event_id', $this->event->id)
        ->where('child_id', $this->child->id)
        ->whereDate('attendance_date', today())
        ->first();

    expect($att)->not->toBeNull();
    expect($att->status)->toBe('present');
});

it('toggles present to absent without duplicating', function () {
    EventService::register($this->event, $this->child);

    Livewire::actingAs($this->admin)->test(Events::class)
        ->call('openParticipants', $this->event->id)
        ->set('attendanceDate', today()->toDateString())
        ->call('markAttendance', $this->child->id, 'present')
        ->call('markAttendance', $this->child->id, 'absent');

    expect(EventAttendance::where('event_id', $this->event->id)->where('child_id', $this->child->id)->count())->toBe(1);
    expect(EventAttendance::first()->status)->toBe('absent');
});

it('does not mark a non-confirmed participant', function () {
    $paid  = Event::create([
        'name' => 'Paid', 'start_date' => today(), 'end_date' => today()->addDays(7),
        'is_active' => true, 'is_registerable' => true, 'price' => 100000,
    ]);
    EventService::register($paid, $this->child); // paid → pending

    Livewire::actingAs($this->admin)->test(Events::class)
        ->call('openParticipants', $paid->id)
        ->set('attendanceDate', today()->toDateString())
        ->call('markAttendance', $this->child->id, 'present');

    expect(EventAttendance::count())->toBe(0);
});

it('rejects an attendance date outside the event period', function () {
    EventService::register($this->event, $this->child);

    Livewire::actingAs($this->admin)->test(Events::class)
        ->call('openParticipants', $this->event->id)
        ->set('attendanceDate', today()->addDays(30)->toDateString())
        ->call('markAttendance', $this->child->id, 'present');

    expect(EventAttendance::count())->toBe(0);
});

it('keeps attendance separate per date', function () {
    EventService::register($this->event, $this->child);

    Livewire::actingAs($this->admin)->test(Events::class)
        ->call('openParticipants', $this->event->id)
        ->set('attendanceDate', today()->toDateString())
        ->call('markAttendance', $this->child->id, 'present')
        ->set('attendanceDate', today()->addDay()->toDateString())
        ->call('markAttendance', $this->child->id, 'absent');

    expect(EventAttendance::where('child_id', $this->child->id)->count())->toBe(2);
});
