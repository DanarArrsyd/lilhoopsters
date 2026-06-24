<?php
// tests/Feature/Admin/EventRegistrationTest.php

use App\Livewire\Admin\Events;
use App\Models\Child;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Role;
use App\Models\Transaction;
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
});

function makeEvent(array $attrs = []): Event
{
    return Event::create(array_merge([
        'name'            => 'Summer Champ',
        'start_date'      => today()->addWeek(),
        'end_date'        => today()->addWeeks(3),
        'is_active'       => true,
        'is_registerable' => true,
    ], $attrs));
}

it('confirms a free registration with no transaction', function () {
    $event = makeEvent(['price' => null]);

    $reg = EventService::register($event, $this->child);

    expect($reg->status)->toBe('confirmed');
    expect($reg->transaction_id)->toBeNull();
    expect(Transaction::count())->toBe(0);
});

it('creates a pending transaction for a paid registration', function () {
    $event = makeEvent(['price' => 150000]);

    $reg = EventService::register($event, $this->child);

    expect($reg->status)->toBe('pending');
    $txn = Transaction::first();
    expect($txn)->not->toBeNull();
    expect($txn->amount)->toBe(150000);
    expect($txn->status)->toBe('pending');
    expect($txn->user_id)->toBe($this->parent->id);
    expect($reg->fresh()->transaction_id)->toBe($txn->id);
});

it('blocks a duplicate registration', function () {
    $event = makeEvent();
    EventService::register($event, $this->child);

    expect(fn() => EventService::register($event, $this->child))
        ->toThrow(RuntimeException::class);
});

it('enforces capacity', function () {
    $event  = makeEvent(['capacity' => 1]);
    $child2 = Child::factory()->active()->create(['user_id' => $this->parent->id]);

    EventService::register($event, $this->child); // fills the single spot

    expect(fn() => EventService::register($event, $child2))
        ->toThrow(RuntimeException::class);
});

it('rejects registration for a non-registerable event', function () {
    $event = makeEvent(['is_registerable' => false]);

    expect(fn() => EventService::register($event, $this->child))
        ->toThrow(RuntimeException::class);
});

it('admin can add a participant from the events page', function () {
    $event = makeEvent(['price' => 100000]);

    Livewire::actingAs($this->admin)->test(Events::class)
        ->call('openParticipants', $event->id)
        ->set('addChildId', $this->child->id)
        ->call('addParticipant');

    expect(EventRegistration::where('event_id', $event->id)->where('child_id', $this->child->id)->exists())->toBeTrue();
});

it('cancelling a registration frees a spot', function () {
    $event = makeEvent(['capacity' => 1]);
    $reg   = EventService::register($event, $this->child);

    expect($event->isFull())->toBeTrue();

    Livewire::actingAs($this->admin)->test(Events::class)
        ->call('openParticipants', $event->id)
        ->call('cancelRegistration', $reg->id);

    expect($reg->fresh()->status)->toBe('cancelled');
    expect($event->fresh()->isFull())->toBeFalse();
});
