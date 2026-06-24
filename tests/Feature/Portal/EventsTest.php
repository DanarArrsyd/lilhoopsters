<?php
// tests/Feature/Portal/EventsTest.php

use App\Livewire\Admin\Payments;
use App\Livewire\Portal\Events as PortalEvents;
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

function openEvent(array $attrs = []): Event
{
    return Event::create(array_merge([
        'name'            => 'Summer Champ',
        'start_date'      => today()->addWeek(),
        'end_date'        => today()->addWeeks(3),
        'is_active'       => true,
        'is_registerable' => true,
    ], $attrs));
}

it('renders the parent events page', function () {
    $this->actingAs($this->parent)->get(route('parent.events'))->assertOk();
});

it('lets a parent register a child for a free event', function () {
    $event = openEvent(['price' => null]);

    Livewire::actingAs($this->parent)->test(PortalEvents::class)
        ->set("childSelection.{$event->id}", $this->child->id)
        ->call('register', $event->id);

    $reg = EventRegistration::where('event_id', $event->id)->where('child_id', $this->child->id)->first();
    expect($reg->status)->toBe('confirmed');
    expect(Transaction::count())->toBe(0);
});

it('creates a pending payment when a parent registers for a paid event', function () {
    $event = openEvent(['price' => 200000]);

    Livewire::actingAs($this->parent)->test(PortalEvents::class)
        ->set("childSelection.{$event->id}", $this->child->id)
        ->call('register', $event->id);

    $reg = EventRegistration::first();
    expect($reg->status)->toBe('pending');
    expect($reg->transaction->amount)->toBe(200000);
    expect($reg->transaction->user_id)->toBe($this->parent->id);
});

it('confirms the registration when admin verifies the payment', function () {
    $event = openEvent(['price' => 200000]);
    $reg   = EventService::register($event, $this->child);

    Livewire::actingAs($this->admin)->test(Payments::class)->call('verify', $reg->transaction_id);

    expect($reg->fresh()->status)->toBe('confirmed');
    expect(Transaction::find($reg->transaction_id)->status)->toBe('paid');
});

it('cancels the registration when admin rejects the payment', function () {
    $event = openEvent(['price' => 200000]);
    $reg   = EventService::register($event, $this->child);

    Livewire::actingAs($this->admin)->test(Payments::class)
        ->call('reject', $reg->transaction_id)
        ->call('confirmReject');

    expect($reg->fresh()->status)->toBe('cancelled');
});

it('cannot register another parents child', function () {
    $event       = openEvent();
    $otherParent = User::factory()->withRole('parent')->approved()->create();
    $otherChild  = Child::factory()->active()->create(['user_id' => $otherParent->id]);

    expect(fn() => Livewire::actingAs($this->parent)->test(PortalEvents::class)
        ->set("childSelection.{$event->id}", $otherChild->id)
        ->call('register', $event->id)
    )->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});
