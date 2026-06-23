<?php
// tests/Feature/Admin/LeadTest.php

use App\Livewire\Admin\Leads;
use App\Livewire\Admin\Owner;
use App\Models\Lead;
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

    $this->admin = User::factory()->withRole('admin')->approved()->create();
});

it('renders leads page for admin', function () {
    $this->actingAs($this->admin)->get(route('admin.leads'))->assertOk();
});

it('non-admin cannot access leads', function () {
    $parent = User::factory()->withRole('parent')->approved()->create();
    $this->actingAs($parent)->get(route('admin.leads'))->assertForbidden();
});

it('shows leads link in sidebar', function () {
    $this->actingAs($this->admin)->get(route('admin.leads'))
        ->assertSee(route('admin.leads'));
});

it('creates a lead and stamps the creator', function () {
    Livewire::actingAs($this->admin)->test(Leads::class)
        ->call('openCreate')
        ->set('parent_name', 'Budi Santoso')
        ->set('child_name', 'Arka')
        ->set('source', 'instagram')
        ->call('save')
        ->assertHasNoErrors();

    $lead = Lead::first();
    expect($lead->parent_name)->toBe('Budi Santoso');
    expect($lead->status)->toBe('new');
    expect($lead->created_by)->toBe($this->admin->id);
});

it('requires a parent name', function () {
    Livewire::actingAs($this->admin)->test(Leads::class)
        ->call('openCreate')
        ->set('parent_name', '')
        ->call('save')
        ->assertHasErrors(['parent_name' => 'required']);
});

it('advances lead status from the row control', function () {
    $lead = Lead::factory()->create(['status' => 'new']);

    Livewire::actingAs($this->admin)->test(Leads::class)
        ->call('setStatus', $lead->id, 'trial_scheduled');

    expect($lead->fresh()->status)->toBe('trial_scheduled');
});

it('rejects an invalid status value', function () {
    $lead = Lead::factory()->create(['status' => 'new']);

    Livewire::actingAs($this->admin)->test(Leads::class)
        ->call('setStatus', $lead->id, 'bogus');

    expect($lead->fresh()->status)->toBe('new');
});

it('filters leads by status', function () {
    Lead::factory()->create(['status' => 'new', 'parent_name' => 'Ada New']);
    Lead::factory()->create(['status' => 'converted', 'parent_name' => 'Zed Converted']);

    Livewire::actingAs($this->admin)->test(Leads::class)
        ->set('statusFilter', 'converted')
        ->assertSee('Zed Converted')
        ->assertDontSee('Ada New');
});

it('computes lead funnel conversion rate on owner insights', function () {
    Lead::factory()->count(3)->create(['status' => 'new']);
    Lead::factory()->count(3)->create(['status' => 'converted']);
    Lead::factory()->count(1)->create(['status' => 'lost']);

    $funnel = Livewire::actingAs($this->admin)->test(Owner::class)->viewData('leads');

    expect($funnel['total'])->toBe(7);
    expect($funnel['open'])->toBe(3);
    expect($funnel['converted'])->toBe(3);
    // 3 converted ÷ (3 converted + 1 lost) = 75%
    expect($funnel['conversion'])->toBe(75.0);
});
