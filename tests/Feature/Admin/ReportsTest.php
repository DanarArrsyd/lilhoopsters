<?php
// tests/Feature/Admin/ReportsTest.php

use App\Livewire\Admin\Reports;
use App\Models\Location;
use App\Models\Package;
use App\Models\Role;
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

    $this->admin    = User::factory()->withRole('admin')->approved()->create();
    $this->location = Location::factory()->create();
    $this->package  = Package::factory()->regular()->create(['location_id' => $this->location->id]);
});

it('renders reports page for admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.reports'))
        ->assertOk();
});

it('non-admin cannot access reports page', function () {
    $parent = User::factory()->withRole('parent')->approved()->create();

    $this->actingAs($parent)
        ->get(route('admin.reports'))
        ->assertForbidden();
});

it('shows reports link in sidebar', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.reports'));

    $response->assertSee(route('admin.reports'));
});

it('counts only paid transactions in total revenue', function () {
    Transaction::factory()->paid()->create([
        'package_id' => $this->package->id,
        'amount'     => 500000,
        'paid_at'    => now(),
    ]);
    Transaction::factory()->create([          // pending — must be excluded
        'package_id' => $this->package->id,
        'amount'     => 300000,
    ]);

    $component = Livewire::actingAs($this->admin)->test(Reports::class);

    expect($component->viewData('kpis')['total_revenue'])->toBe(500000);
    expect($component->viewData('kpis')['paid_count'])->toBe(1);
});

it('filters revenue by location', function () {
    $other = Location::factory()->create();
    $pkgB  = Package::factory()->create(['location_id' => $other->id]);

    Transaction::factory()->paid()->create(['package_id' => $this->package->id, 'amount' => 200000, 'paid_at' => now()]);
    Transaction::factory()->paid()->create(['package_id' => $pkgB->id,          'amount' => 400000, 'paid_at' => now()]);

    $component = Livewire::actingAs($this->admin)
        ->test(Reports::class)
        ->set('filterLocation', $this->location->id);

    expect($component->viewData('kpis')['total_revenue'])->toBe(200000);
});

it('swaps reversed date range', function () {
    Transaction::factory()->paid()->create([
        'package_id' => $this->package->id,
        'amount'     => 100000,
        'paid_at'    => now(),
    ]);

    // dateFrom after dateTo — should swap and still find the transaction
    $component = Livewire::actingAs($this->admin)
        ->test(Reports::class)
        ->set('dateFrom', now()->addDays(5)->toDateString())
        ->set('dateTo',   now()->subDays(5)->toDateString());

    expect($component->viewData('kpis')['paid_count'])->toBe(1);
});

it('shows correct conversion rate', function () {
    // 1 paid, 1 pending — conversion = 50 %
    Transaction::factory()->paid()->create(['package_id' => $this->package->id, 'paid_at' => now()]);
    Transaction::factory()->create(['package_id' => $this->package->id]);

    $component = Livewire::actingAs($this->admin)->test(Reports::class);

    expect($component->viewData('kpis')['conversion_rate'])->toBe(50.0);
});

it('groups revenue by package type', function () {
    $regPkg = Package::factory()->registration()->create(['location_id' => $this->location->id]);
    Transaction::factory()->paid()->create(['package_id' => $this->package->id, 'amount' => 300000, 'paid_at' => now()]); // regular
    Transaction::factory()->paid()->create(['package_id' => $regPkg->id,        'amount' => 100000, 'paid_at' => now()]); // registration

    $component = Livewire::actingAs($this->admin)->test(Reports::class);
    $byType    = $component->viewData('byType');

    expect($byType->has('regular'))->toBeTrue();
    expect($byType->has('registration'))->toBeTrue();
    expect($byType['regular']['revenue'])->toBe(300000);
    expect($byType['registration']['revenue'])->toBe(100000);
});

it('excludes out-of-range transactions from revenue', function () {
    Transaction::factory()->paid()->create([
        'package_id' => $this->package->id,
        'amount'     => 500000,
        'paid_at'    => now()->subYear(),   // last year — outside default "this month" range
    ]);

    $component = Livewire::actingAs($this->admin)->test(Reports::class);

    expect($component->viewData('kpis')['total_revenue'])->toBe(0);
});

it('funnel counts all statuses by created_at', function () {
    Transaction::factory()->paid()->create(['package_id' => $this->package->id, 'paid_at' => now()]);
    Transaction::factory()->create(['package_id' => $this->package->id, 'status' => 'pending']);
    Transaction::factory()->create(['package_id' => $this->package->id, 'status' => 'rejected']);

    $component = Livewire::actingAs($this->admin)->test(Reports::class);
    $funnel    = $component->viewData('funnel');

    expect($funnel['paid'])->toBe(1);
    expect($funnel['pending'])->toBe(1);
    expect($funnel['rejected'])->toBe(1);
});

it('preset buttons update date range', function () {
    $component = Livewire::actingAs($this->admin)->test(Reports::class);

    $component->call('setPreset', '30d');
    expect($component->get('preset'))->toBe('30d');
    expect($component->get('dateFrom'))->toBe(now()->subDays(29)->toDateString());

    $component->call('setPreset', 'year');
    expect($component->get('preset'))->toBe('year');
    expect($component->get('dateFrom'))->toBe(now()->startOfYear()->toDateString());
});

it('computes positive revenue delta vs previous period', function () {
    // Current window 2026-06-10..2026-06-19 (10 days).
    // Previous window (equal length, immediately before) = 2026-05-31..2026-06-09.
    Transaction::factory()->paid()->create([
        'package_id' => $this->package->id,
        'amount'     => 300000,
        'paid_at'    => '2026-06-15 10:00:00',   // current
    ]);
    Transaction::factory()->paid()->create([
        'package_id' => $this->package->id,
        'amount'     => 100000,
        'paid_at'    => '2026-06-05 10:00:00',   // previous (baseline)
    ]);

    $component = Livewire::actingAs($this->admin)->test(Reports::class)
        ->set('dateFrom', '2026-06-10')
        ->set('dateTo', '2026-06-19');

    $deltas = $component->viewData('deltas');

    expect($deltas['total_revenue']['dir'])->toBe('up');
    expect($deltas['total_revenue']['pct'])->toBe(200.0);
});

it('returns null delta when both periods are empty', function () {
    // No transactions at all — current and previous period both zero.
    $component = Livewire::actingAs($this->admin)->test(Reports::class);

    expect($component->viewData('deltas')['total_revenue'])->toBeNull();
});

it('exports paid transactions as csv', function () {
    Transaction::factory()->paid()->create([
        'package_id' => $this->package->id,
        'amount'     => 250000,
        'paid_at'    => now(),
    ]);

    $response = Livewire::actingAs($this->admin)->test(Reports::class)
        ->call('export');

    $response->assertFileDownloaded();
});
