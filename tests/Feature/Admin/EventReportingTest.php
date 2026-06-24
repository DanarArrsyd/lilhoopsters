<?php
// tests/Feature/Admin/EventReportingTest.php

use App\Livewire\Admin\Owner;
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
    $this->child1 = Child::factory()->active()->create(['user_id' => $this->parent->id]);
    $this->child2 = Child::factory()->active()->create(['user_id' => $this->parent->id]);

    $this->event = Event::create([
        'name' => 'Summer Champ', 'start_date' => today(), 'end_date' => today()->addDays(7),
        'is_active' => true, 'is_registerable' => true, 'price' => 100000,
    ]);
});

it('reports event revenue, participation, and attendance to the owner', function () {
    // child1: paid + confirmed
    $reg1 = EventService::register($this->event, $this->child1);
    $reg1->transaction->update(['status' => 'paid', 'paid_at' => now()]);
    $reg1->update(['status' => 'confirmed']);

    // child2: still pending payment
    EventService::register($this->event, $this->child2);

    // attendance: child1 present today
    EventAttendance::create([
        'event_id' => $this->event->id, 'child_id' => $this->child1->id,
        'attendance_date' => today(), 'status' => 'present',
    ]);

    $events = Livewire::actingAs($this->admin)->test(Owner::class)->viewData('events');

    expect($events['total_revenue'])->toBe(100000);
    expect($events['total_pending'])->toBe(100000);
    expect($events['total_people'])->toBe(1);

    $row = $events['rows']->first();
    expect($row['confirmed'])->toBe(1);
    expect($row['pending'])->toBe(1);
    expect($row['attendance'])->toBe(100);
    expect($row['revenue'])->toBe(100000);
});

it('shows no event revenue before any payment is verified', function () {
    EventService::register($this->event, $this->child1); // pending only

    $events = Livewire::actingAs($this->admin)->test(Owner::class)->viewData('events');

    expect($events['total_revenue'])->toBe(0);
    expect($events['rows']->first()['attendance'])->toBeNull();
});
