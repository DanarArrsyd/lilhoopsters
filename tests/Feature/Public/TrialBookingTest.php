<?php
// tests/Feature/Public/TrialBookingTest.php

use App\Livewire\Public\TrialBooking;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('is publicly accessible without logging in', function () {
    $this->get(route('trial'))->assertOk();
});

it('creates a web lead from a trial request', function () {
    Livewire::test(TrialBooking::class)
        ->set('parent_name', 'Budi Santoso')
        ->set('whatsapp', '08123456789')
        ->set('child_name', 'Arka')
        ->set('child_age', 7)
        ->call('submit')
        ->assertSet('submitted', true);

    $lead = Lead::first();
    expect($lead)->not->toBeNull();
    expect($lead->parent_name)->toBe('Budi Santoso');
    expect($lead->source)->toBe('web');
    expect($lead->status)->toBe('new');
    expect($lead->notes)->toContain('Child age: 7');
});

it('silently drops a bot submission that fills the honeypot', function () {
    Livewire::test(TrialBooking::class)
        ->set('parent_name', 'Bot')
        ->set('whatsapp', '08123456789')
        ->set('child_name', 'Bot Child')
        ->set('website', 'http://spam.example')   // honeypot
        ->call('submit')
        ->assertSet('submitted', true);

    expect(Lead::count())->toBe(0);
});

it('validates required fields', function () {
    Livewire::test(TrialBooking::class)
        ->call('submit')
        ->assertHasErrors(['parent_name', 'whatsapp', 'child_name']);

    expect(Lead::count())->toBe(0);
});

it('throttles excessive submissions from one IP', function () {
    $component = Livewire::test(TrialBooking::class);

    for ($i = 0; $i < 6; $i++) {
        $component
            ->set('parent_name', "Parent {$i}")
            ->set('whatsapp', '08123456789')
            ->set('child_name', "Child {$i}")
            ->set('website', '')
            ->call('submit');
    }

    // 5 allowed, the 6th blocked.
    expect(Lead::count())->toBe(5);
});
