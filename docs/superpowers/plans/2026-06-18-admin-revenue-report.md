# Admin Revenue Report Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add an `/admin/reports` page where admin staff can analyse paid package revenue with time-range filters, KPI cards, an SVG bar chart, type/location breakdowns, top packages table, and a payment funnel strip.

**Architecture:** Single Livewire 3 component (`App\Livewire\Admin\Reports`) that runs two DB queries inside `render()` — one for paid transactions (revenue) and one for the all-status funnel — then derives all display data in PHP collections. Charts are server-side inline SVG with Alpine.js hover tooltips (no new JS libraries). Follows the exact same patterns as the existing `Packages` and `Payments` components.

**Tech Stack:** Laravel 11, Livewire 3, Alpine.js, Tailwind CSS v4 (custom `navy/off/line/ink/muted` CSS vars), Pest tests.

---

## File Map

| Action | File |
|---|---|
| Create | `app/Livewire/Admin/Reports.php` |
| Create | `resources/views/livewire/admin/reports.blade.php` |
| Create | `resources/views/admin/reports.blade.php` |
| Modify | `routes/web.php` — add `admin.reports` route |
| Modify | `resources/views/components/admin-nav.blade.php` — sidebar link |
| Create | `tests/Feature/Admin/ReportsTest.php` |

---

## Task 1: Route + stub page (smoke test first)

**Files:**
- Modify: `routes/web.php`
- Create: `resources/views/admin/reports.blade.php`
- Create: `tests/Feature/Admin/ReportsTest.php` (partial — route test only)

- [ ] **Step 1: Write the failing route test**

```php
<?php
// tests/Feature/Admin/ReportsTest.php

use App\Models\Location;
use App\Models\Package;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
```

- [ ] **Step 2: Run tests to see them fail**

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/basketballv2
php artisan test tests/Feature/Admin/ReportsTest.php
```
Expected: FAIL — route `admin.reports` not found.

- [ ] **Step 3: Add the route to `routes/web.php`**

Inside the existing `admin.` route group (after the `payments` line), add:

```php
Route::get('/reports', fn() => view('admin.reports'))->name('reports');
```

- [ ] **Step 4: Create the stub admin view `resources/views/admin/reports.blade.php`**

```blade
<x-admin title="Reports">
    <x-slot name="navigation">
        <x-admin-nav />
    </x-slot>

    <livewire:admin.reports />
</x-admin>
```

- [ ] **Step 5: Create a minimal Livewire component `app/Livewire/Admin/Reports.php`**

```php
<?php

namespace App\Livewire\Admin;

use Livewire\Component;

class Reports extends Component
{
    public function render()
    {
        return view('livewire.admin.reports');
    }
}
```

- [ ] **Step 6: Create a minimal Livewire view `resources/views/livewire/admin/reports.blade.php`**

```blade
<div>
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Reports</h2>
        <p class="text-sm text-muted">Revenue & payment analytics.</p>
    </div>
</div>
```

- [ ] **Step 7: Run tests — expect them to pass**

```bash
php artisan test tests/Feature/Admin/ReportsTest.php
```
Expected: 2 tests PASS.

- [ ] **Step 8: Commit**

```bash
git add routes/web.php \
        resources/views/admin/reports.blade.php \
        app/Livewire/Admin/Reports.php \
        resources/views/livewire/admin/reports.blade.php \
        tests/Feature/Admin/ReportsTest.php
git commit -m "feat(reports): stub route, view, and Livewire component"
```

---

## Task 2: Sidebar nav link

**Files:**
- Modify: `resources/views/components/admin-nav.blade.php`
- Modify: `tests/Feature/Admin/ReportsTest.php` (add nav test)

- [ ] **Step 1: Add nav visibility test**

Append to `tests/Feature/Admin/ReportsTest.php`:

```php
it('shows reports link in sidebar', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.reports'));

    $response->assertSee(route('admin.reports'));
});
```

- [ ] **Step 2: Run to confirm it currently passes** (the route exists, the link doesn't yet — assertSee on href may still pass if rendered by admin-nav; run to find out)

```bash
php artisan test tests/Feature/Admin/ReportsTest.php --filter="shows reports link"
```

- [ ] **Step 3: Add the sidebar link in `resources/views/components/admin-nav.blade.php`**

Inside the **Finance** `<x-sidebar-section>`, directly after the Payments `<x-sidebar-link>` block:

```blade
<x-sidebar-link href="{{ route('admin.reports') }}" :active="request()->routeIs('admin.reports')">
    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
    </svg>
    Reports
</x-sidebar-link>
```

- [ ] **Step 4: Run all reports tests**

```bash
php artisan test tests/Feature/Admin/ReportsTest.php
```
Expected: 3 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/views/components/admin-nav.blade.php \
        tests/Feature/Admin/ReportsTest.php
git commit -m "feat(reports): add sidebar nav link under Finance section"
```

---

## Task 3: Livewire component — filter state + data queries

**Files:**
- Modify: `app/Livewire/Admin/Reports.php`
- Modify: `tests/Feature/Admin/ReportsTest.php` (add filter + KPI tests)

- [ ] **Step 1: Write failing tests for KPI computation**

Append to `tests/Feature/Admin/ReportsTest.php`:

```php
use App\Livewire\Admin\Reports;
use App\Models\Transaction;
use Livewire\Livewire;

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
```

- [ ] **Step 2: Run to confirm they fail**

```bash
php artisan test tests/Feature/Admin/ReportsTest.php
```
Expected: new tests FAIL — `kpis` view data not yet passed.

- [ ] **Step 3: Rewrite `app/Livewire/Admin/Reports.php` with full state + queries**

```php
<?php

namespace App\Livewire\Admin;

use App\Models\Location;
use App\Models\Transaction;
use Carbon\Carbon;
use Livewire\Component;

class Reports extends Component
{
    public string $preset         = 'month';
    public string $dateFrom       = '';
    public string $dateTo         = '';
    public ?int   $filterLocation = null;

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo   = now()->toDateString();
    }

    public function setPreset(string $preset): void
    {
        $this->preset = $preset;
        match ($preset) {
            'month' => [$this->dateFrom, $this->dateTo] = [
                now()->startOfMonth()->toDateString(),
                now()->toDateString(),
            ],
            '30d' => [$this->dateFrom, $this->dateTo] = [
                now()->subDays(29)->toDateString(),
                now()->toDateString(),
            ],
            'year' => [$this->dateFrom, $this->dateTo] = [
                now()->startOfYear()->toDateString(),
                now()->toDateString(),
            ],
            default => null,
        };
    }

    public function updatedDateFrom(): void { $this->preset = 'custom'; }
    public function updatedDateTo(): void   { $this->preset = 'custom'; }

    // ─── helpers ─────────────────────────────────────────────────────

    private function resolvedRange(): array
    {
        $from = Carbon::parse($this->dateFrom)->startOfDay();
        $to   = Carbon::parse($this->dateTo)->endOfDay();

        if ($to->lt($from)) {
            [$from, $to] = [$to->startOfDay(), $from->endOfDay()];
        }

        return [$from, $to];
    }

    // ─── render ──────────────────────────────────────────────────────

    public function render()
    {
        [$from, $to] = $this->resolvedRange();

        // Query A — paid transactions (revenue)
        $paid = Transaction::where('status', 'paid')
            ->whereBetween('paid_at', [$from, $to])
            ->when($this->filterLocation, fn($q) => $q->whereHas(
                'package', fn($p) => $p->where('location_id', $this->filterLocation)
            ))
            ->with(['package.location'])
            ->get();

        // Query B — all-status funnel (by created_at)
        $funnel = Transaction::whereBetween('created_at', [$from, $to])
            ->when($this->filterLocation, fn($q) => $q->whereHas(
                'package', fn($p) => $p->where('location_id', $this->filterLocation)
            ))
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        // KPIs
        $totalRevenue = $paid->sum('amount');
        $paidCount    = $paid->count();
        $funnelTotal  = array_sum($funnel);
        $kpis = [
            'total_revenue'   => $totalRevenue,
            'paid_count'      => $paidCount,
            'avg_transaction' => $paidCount > 0 ? intdiv($totalRevenue, $paidCount) : 0,
            'conversion_rate' => $funnelTotal > 0
                ? round(($funnel['paid'] ?? 0) / $funnelTotal * 100, 1)
                : 0.0,
        ];

        // Revenue over time — daily or monthly buckets
        $rangeDays  = (int) Carbon::parse($this->dateFrom)->diffInDays(Carbon::parse($this->dateTo)) + 1;
        $bucketMode = $rangeDays <= 31 ? 'daily' : 'monthly';
        $chart      = $this->buildTimeChart($paid, $from, $to, $bucketMode);

        // By package type
        $byType = $paid->groupBy(fn($t) => $t->package?->type ?? 'unknown')
            ->map(fn($items) => ['revenue' => $items->sum('amount'), 'count' => $items->count()])
            ->sortByDesc('revenue');

        // By location
        $byLocation = $paid->groupBy(fn($t) => $t->package?->location?->name ?? 'Unknown')
            ->map(fn($items) => ['revenue' => $items->sum('amount'), 'count' => $items->count()])
            ->sortByDesc('revenue');

        // Top 10 packages
        $topPackages = $paid->groupBy('package_id')
            ->map(function ($items) {
                $pkg = $items->first()->package;
                return [
                    'name'     => $pkg?->name ?? '—',
                    'type'     => $pkg?->type ?? '—',
                    'location' => $pkg?->location?->name ?? '—',
                    'units'    => $items->count(),
                    'revenue'  => $items->sum('amount'),
                ];
            })
            ->sortByDesc('revenue')
            ->take(10)
            ->values();

        return view('livewire.admin.reports', compact(
            'kpis', 'chart', 'byType', 'byLocation', 'topPackages', 'funnel',
        ))->with([
            'locations'   => Location::where('is_active', true)->orderBy('name')->get(),
            'bucketMode'  => $bucketMode,
            'funnelTotal' => $funnelTotal,
        ]);
    }

    // Build time-chart data: array of ['label'=>string, 'amount'=>int]
    private function buildTimeChart($paid, Carbon $from, Carbon $to, string $mode): array
    {
        if ($mode === 'daily') {
            // Index paid transactions by date
            $byDate = $paid->groupBy(fn($t) => Carbon::parse($t->paid_at)->toDateString())
                ->map(fn($items) => $items->sum('amount'));

            $buckets = [];
            for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
                $key       = $d->toDateString();
                $buckets[] = ['label' => $d->format('d M'), 'amount' => $byDate->get($key, 0)];
            }
            return $buckets;
        }

        // Monthly
        $byMonth = $paid->groupBy(fn($t) => Carbon::parse($t->paid_at)->format('Y-m'))
            ->map(fn($items) => $items->sum('amount'));

        $buckets = [];
        $cursor  = $from->copy()->startOfMonth();
        $end     = $to->copy()->startOfMonth();
        while ($cursor->lte($end)) {
            $key       = $cursor->format('Y-m');
            $buckets[] = ['label' => $cursor->format('M Y'), 'amount' => $byMonth->get($key, 0)];
            $cursor->addMonthNoOverflow();
        }
        return $buckets;
    }
}
```

- [ ] **Step 4: Run the tests**

```bash
php artisan test tests/Feature/Admin/ReportsTest.php
```
Expected: all tests PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Livewire/Admin/Reports.php \
        tests/Feature/Admin/ReportsTest.php
git commit -m "feat(reports): Livewire component with filter state, KPI and chart data"
```

---

## Task 4: Blade view — filter bar + KPI cards

**Files:**
- Modify: `resources/views/livewire/admin/reports.blade.php`

- [ ] **Step 1: Replace stub view with filter bar + KPI cards**

```blade
<div>
    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Reports</h2>
        <p class="text-sm text-muted">Revenue & payment analytics.</p>
    </div>

    {{-- Filter bar --}}
    <x-card class="mb-4" padding="p-4">
        <div class="flex flex-wrap gap-2 items-center">
            {{-- Presets --}}
            @foreach (['month' => 'Bulan Ini', '30d' => '30 Hari', 'year' => 'Tahun Ini'] as $key => $label)
                <button wire:click="setPreset('{{ $key }}')"
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg border transition-colors
                               {{ $preset === $key
                                   ? 'bg-navy text-off border-navy'
                                   : 'bg-off text-navy border-line hover:border-navy/40' }}">
                    {{ $label }}
                </button>
            @endforeach

            {{-- Custom date range --}}
            <div class="flex items-center gap-1.5">
                <input type="date" wire:model.live="dateFrom"
                       class="text-xs border border-line rounded-lg px-2 py-1.5 text-ink bg-off
                              focus:outline-none focus:ring-1 focus:ring-navy/30" />
                <span class="text-xs text-muted">–</span>
                <input type="date" wire:model.live="dateTo"
                       class="text-xs border border-line rounded-lg px-2 py-1.5 text-ink bg-off
                              focus:outline-none focus:ring-1 focus:ring-navy/30" />
            </div>

            {{-- Location filter --}}
            <div class="w-full sm:w-48">
                <x-select wire:model.live="filterLocation">
                    <option value="">All Locations</option>
                    @foreach ($locations as $loc)
                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                    @endforeach
                </x-select>
            </div>
        </div>
    </x-card>

    {{-- KPI cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        @php
            $kpiCards = [
                ['label' => 'Total Revenue',      'value' => 'Rp ' . number_format($kpis['total_revenue'], 0, ',', '.'), 'sub' => 'paid transactions'],
                ['label' => 'Transactions Paid',  'value' => number_format($kpis['paid_count']),            'sub' => 'completed'],
                ['label' => 'Avg per Transaction','value' => 'Rp ' . number_format($kpis['avg_transaction'], 0, ',', '.'), 'sub' => 'average value'],
                ['label' => 'Conversion Rate',    'value' => $kpis['conversion_rate'] . '%',                'sub' => 'paid ÷ all initiated'],
            ];
        @endphp

        @foreach ($kpiCards as $card)
            <x-card padding="p-4">
                <p class="text-xs text-muted mb-1">{{ $card['label'] }}</p>
                <p class="text-xl font-extrabold text-navy leading-tight">{{ $card['value'] }}</p>
                <p class="text-xs text-muted mt-0.5">{{ $card['sub'] }}</p>
            </x-card>
        @endforeach
    </div>
</div>
```

- [ ] **Step 2: Rebuild CSS so Tailwind picks up any new classes**

```bash
npm run build
```

- [ ] **Step 3: Verify in browser — open `http://127.0.0.1:8000/admin/reports`**

Confirm: page loads, preset buttons highlight correctly, date inputs respond, location dropdown populates, KPI cards show (all zeros if no data yet).

- [ ] **Step 4: Commit**

```bash
git add resources/views/livewire/admin/reports.blade.php
git commit -m "feat(reports): filter bar and KPI cards"
```

---

## Task 5: Revenue over time — SVG bar chart

**Files:**
- Modify: `resources/views/livewire/admin/reports.blade.php`

- [ ] **Step 1: Append chart section after the KPI cards `</div>` (before the outer `</div>`)**

```blade
    {{-- Revenue over time --}}
    <x-card padding="p-4" class="mb-6">
        <h3 class="text-sm font-bold text-navy uppercase tracking-wide mb-4">Revenue Over Time
            <span class="font-normal normal-case text-muted">({{ $bucketMode === 'daily' ? 'daily' : 'monthly' }})</span>
        </h3>

        @php
            $chartData  = $chart;
            $maxAmount  = collect($chartData)->max('amount') ?: 1;
            $chartH     = 120;   // px — SVG inner height for bars
            $barCount   = count($chartData);
            $svgW       = max($barCount * 28, 300);
            $barW       = max(14, intdiv($svgW, max($barCount, 1)) - 4);
            $labelStep  = max(1, (int) ceil($barCount / 12)); // show at most 12 labels
        @endphp

        @if ($barCount === 0 || $maxAmount === 0)
            <x-empty-state>No paid transactions in this period.</x-empty-state>
        @else
            <div class="overflow-x-auto" x-data="{ tooltip: null, tooltipX: 0, tooltipY: 0 }">
                <svg viewBox="0 0 {{ $svgW }} {{ $chartH + 36 }}"
                     width="100%" preserveAspectRatio="none"
                     style="min-width:{{ min($svgW, 300) }}px; height:{{ $chartH + 36 }}px;"
                     xmlns="http://www.w3.org/2000/svg">

                    {{-- Gridlines (4 lines) --}}
                    @foreach ([0.25, 0.5, 0.75, 1.0] as $frac)
                        @php $y = $chartH - ($frac * $chartH) @endphp
                        <line x1="0" y1="{{ $y }}" x2="{{ $svgW }}" y2="{{ $y }}"
                              stroke="#e5e7eb" stroke-width="1"/>
                        <text x="2" y="{{ $y - 2 }}" font-size="8" fill="#9ca3af">
                            Rp {{ number_format($maxAmount * $frac / 1000, 0, ',', '.') }}k
                        </text>
                    @endforeach

                    {{-- Bars --}}
                    @foreach ($chartData as $i => $bucket)
                        @php
                            $barH    = (int) round($bucket['amount'] / $maxAmount * $chartH);
                            $barX    = $i * ($barW + 4) + 2;
                            $barY    = $chartH - $barH;
                            $labelId = "tip-{$i}";
                        @endphp

                        <g x-on:mouseenter="tooltip='{{ addslashes($bucket['label']) }}: Rp {{ number_format($bucket['amount'], 0, ',', '.') }}'; tooltipX={{ $barX + $barW / 2 }}; tooltipY={{ $barY }}"
                           x-on:mouseleave="tooltip=null"
                           style="cursor:pointer">
                            <rect x="{{ $barX }}" y="{{ $barY }}"
                                  width="{{ $barW }}" height="{{ max($barH, 1) }}"
                                  rx="2" fill="var(--color-navy, #1e3a5f)" opacity="0.85"/>
                        </g>

                        {{-- X-axis label (pruned) --}}
                        @if ($i % $labelStep === 0)
                            <text x="{{ $barX + $barW / 2 }}" y="{{ $chartH + 14 }}"
                                  text-anchor="middle" font-size="8" fill="#6b7280">
                                {{ $bucket['label'] }}
                            </text>
                        @endif
                    @endforeach
                </svg>

                {{-- Tooltip --}}
                <div x-show="tooltip !== null"
                     x-cloak
                     x-bind:style="'left:' + tooltipX + 'px; top:' + (tooltipY - 28) + 'px'"
                     class="absolute pointer-events-none bg-navy text-off text-[10px] font-medium
                            px-2 py-1 rounded shadow z-10 whitespace-nowrap -translate-x-1/2"
                     x-text="tooltip">
                </div>
            </div>
        @endif
    </x-card>
```

> Note: the parent `<div>` in this Livewire view needs `class="relative"` for the tooltip `absolute` positioning to work. Change the outer `<div>` opening tag to:
> ```blade
> <div class="relative">
> ```

- [ ] **Step 2: Rebuild CSS**

```bash
npm run build
```

- [ ] **Step 3: Verify in browser**

Confirm: chart renders, bars show, hovering a bar shows a tooltip with formatted date and amount, gridlines appear, x-axis labels render (pruned when many buckets), empty state shows when no data.

- [ ] **Step 4: Commit**

```bash
git add resources/views/livewire/admin/reports.blade.php
git commit -m "feat(reports): SVG revenue-over-time bar chart with Alpine tooltip"
```

---

## Task 6: Breakdown bars (by type + by location) + top packages table

**Files:**
- Modify: `resources/views/livewire/admin/reports.blade.php`

- [ ] **Step 1: Append breakdown + top-packages section after the chart card (still inside `<div class="relative">`)**

```blade
    {{-- Two-col breakdowns --}}
    @php
        $typeMeta = [
            'registration' => ['label' => 'Registration', 'class' => 'bg-[#1D4ED8]/10 text-[#1D4ED8]', 'bar' => 'bg-[#1D4ED8]'],
            'regular'      => ['label' => 'Regular',      'class' => 'bg-navy/10 text-navy',           'bar' => 'bg-navy'],
            'drop_in'      => ['label' => 'Drop-in',      'class' => 'bg-[#B45309]/10 text-[#B45309]', 'bar' => 'bg-[#B45309]'],
            'private'      => ['label' => 'Private',      'class' => 'bg-[#7C3AED]/10 text-[#7C3AED]', 'bar' => 'bg-[#7C3AED]'],
        ];
        $maxTypeRev     = $byType->max('revenue')     ?: 1;
        $maxLocationRev = $byLocation->max('revenue') ?: 1;
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        {{-- By package type --}}
        <x-card padding="p-4">
            <h3 class="text-sm font-bold text-navy uppercase tracking-wide mb-4">By Package Type</h3>
            @forelse ($byType as $type => $data)
                @php $meta = $typeMeta[$type] ?? ['label' => $type, 'class' => 'bg-line text-ink', 'bar' => 'bg-ink']; @endphp
                <div class="mb-3">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $meta['class'] }}">
                            {{ $meta['label'] }}
                        </span>
                        <div class="text-right">
                            <span class="text-xs font-bold text-ink tabular-nums">
                                Rp {{ number_format($data['revenue'], 0, ',', '.') }}
                            </span>
                            <span class="text-xs text-muted ml-1">({{ $data['count'] }})</span>
                        </div>
                    </div>
                    <div class="h-1.5 bg-line rounded-full overflow-hidden">
                        <div class="{{ $meta['bar'] }} h-full rounded-full"
                             style="width:{{ round($data['revenue'] / $maxTypeRev * 100) }}%">
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-xs text-muted">No data in this period.</p>
            @endforelse
        </x-card>

        {{-- By location --}}
        <x-card padding="p-4">
            <h3 class="text-sm font-bold text-navy uppercase tracking-wide mb-4">By Location</h3>
            @forelse ($byLocation as $locName => $data)
                <div class="mb-3">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-semibold text-ink truncate max-w-[55%]">{{ $locName }}</span>
                        <div class="text-right">
                            <span class="text-xs font-bold text-ink tabular-nums">
                                Rp {{ number_format($data['revenue'], 0, ',', '.') }}
                            </span>
                            <span class="text-xs text-muted ml-1">({{ $data['count'] }})</span>
                        </div>
                    </div>
                    <div class="h-1.5 bg-line rounded-full overflow-hidden">
                        <div class="bg-navy h-full rounded-full"
                             style="width:{{ round($data['revenue'] / $maxLocationRev * 100) }}%">
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-xs text-muted">No data in this period.</p>
            @endforelse
        </x-card>
    </div>

    {{-- Top packages table --}}
    <x-card padding="p-0" class="mb-6 overflow-hidden">
        <div class="px-4 pt-4 pb-3 border-b border-line">
            <h3 class="text-sm font-bold text-navy uppercase tracking-wide">Top Packages</h3>
            <p class="text-xs text-muted">Top 10 by revenue in period.</p>
        </div>
        @if ($topPackages->isEmpty())
            <div class="p-4"><x-empty-state>No paid transactions in this period.</x-empty-state></div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-line bg-faint">
                            <th class="px-4 py-2.5 text-left font-semibold text-muted uppercase tracking-wide">Package</th>
                            <th class="px-4 py-2.5 text-left font-semibold text-muted uppercase tracking-wide">Type</th>
                            <th class="px-4 py-2.5 text-left font-semibold text-muted uppercase tracking-wide">Location</th>
                            <th class="px-4 py-2.5 text-right font-semibold text-muted uppercase tracking-wide">Units Sold</th>
                            <th class="px-4 py-2.5 text-right font-semibold text-muted uppercase tracking-wide">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($topPackages as $pkg)
                            @php $meta = $typeMeta[$pkg['type']] ?? ['label' => $pkg['type'], 'class' => 'bg-line text-ink']; @endphp
                            <tr class="hover:bg-faint transition-colors">
                                <td class="px-4 py-3 font-medium text-ink">{{ $pkg['name'] }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $meta['class'] }}">
                                        {{ $meta['label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-muted">{{ $pkg['location'] }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-ink">{{ $pkg['units'] }}</td>
                                <td class="px-4 py-3 text-right tabular-nums font-bold text-ink">
                                    Rp {{ number_format($pkg['revenue'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>
```

- [ ] **Step 2: Rebuild CSS**

```bash
npm run build
```

- [ ] **Step 3: Verify in browser**

Confirm: two breakdown cards render side by side on desktop (stack on mobile), bars scale relative to highest value, top-packages table shows correctly, empty states work.

- [ ] **Step 4: Commit**

```bash
git add resources/views/livewire/admin/reports.blade.php
git commit -m "feat(reports): breakdown bars by type and location, top packages table"
```

---

## Task 7: Payment funnel strip

**Files:**
- Modify: `resources/views/livewire/admin/reports.blade.php`

- [ ] **Step 1: Append funnel section (last section, before final `</div>`)**

```blade
    {{-- Payment funnel --}}
    <x-card padding="p-4" class="mb-6">
        <h3 class="text-sm font-bold text-navy uppercase tracking-wide mb-4">Payment Funnel
            <span class="font-normal normal-case text-muted">(by initiation date)</span>
        </h3>

        @php
            $funnelMeta = [
                'paid'     => ['label' => 'Paid',     'bg' => 'bg-green-500',  'text' => 'text-green-700',  'light' => 'bg-green-50'],
                'pending'  => ['label' => 'Pending',  'bg' => 'bg-amber-400',  'text' => 'text-amber-700',  'light' => 'bg-amber-50'],
                'rejected' => ['label' => 'Rejected', 'bg' => 'bg-red-500',    'text' => 'text-red-700',    'light' => 'bg-red-50'],
                'expired'  => ['label' => 'Expired',  'bg' => 'bg-slate-400',  'text' => 'text-slate-600',  'light' => 'bg-slate-50'],
            ];
        @endphp

        @if ($funnelTotal === 0)
            <p class="text-xs text-muted">No transactions initiated in this period.</p>
        @else
            <div class="flex h-4 rounded-full overflow-hidden mb-3">
                @foreach ($funnelMeta as $status => $meta)
                    @php $count = $funnel[$status] ?? 0; $pct = $funnelTotal > 0 ? round($count / $funnelTotal * 100) : 0; @endphp
                    @if ($pct > 0)
                        <div class="{{ $meta['bg'] }}" style="width:{{ $pct }}%" title="{{ $meta['label'] }}: {{ $count }}"></div>
                    @endif
                @endforeach
            </div>

            <div class="flex flex-wrap gap-3">
                @foreach ($funnelMeta as $status => $meta)
                    @php $count = $funnel[$status] ?? 0; $pct = $funnelTotal > 0 ? round($count / $funnelTotal * 100, 1) : 0; @endphp
                    <div class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg {{ $meta['light'] }}">
                        <span class="w-2 h-2 rounded-full {{ $meta['bg'] }} shrink-0"></span>
                        <span class="{{ $meta['text'] }} text-xs font-semibold">{{ $meta['label'] }}</span>
                        <span class="{{ $meta['text'] }} text-xs tabular-nums">{{ $count }} ({{ $pct }}%)</span>
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>
```

- [ ] **Step 2: Rebuild CSS**

```bash
npm run build
```

- [ ] **Step 3: Verify in browser**

Confirm: funnel bar renders with proportional coloured segments, legend pills show correct counts and percentages, empty state shows when no transactions in range.

- [ ] **Step 4: Commit**

```bash
git add resources/views/livewire/admin/reports.blade.php
git commit -m "feat(reports): payment funnel strip"
```

---

## Task 8: Full test suite

**Files:**
- Modify: `tests/Feature/Admin/ReportsTest.php`

- [ ] **Step 1: Add breakdown and funnel tests**

Append to `tests/Feature/Admin/ReportsTest.php`:

```php
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
```

- [ ] **Step 2: Run the full test file**

```bash
php artisan test tests/Feature/Admin/ReportsTest.php
```
Expected: all tests PASS.

- [ ] **Step 3: Run full test suite to check for regressions**

```bash
php artisan test
```
Expected: all tests PASS (no regressions).

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/Admin/ReportsTest.php
git commit -m "test(reports): full feature test suite"
```

---

## Self-Review

**Spec coverage check:**

| Spec requirement | Task |
|---|---|
| Route `/admin/reports`, route name `admin.reports` | Task 1 |
| Sidebar link in Finance section | Task 2 |
| Filter: preset buttons (Bulan Ini / 30 Hari / Tahun Ini) | Task 3 + 4 |
| Filter: custom date range, location dropdown | Task 3 + 4 |
| `dateFrom > dateTo` swap | Task 3 |
| KPI: Total Revenue (paid, `paid_at`) | Task 3 |
| KPI: Transactions Paid | Task 3 |
| KPI: Avg per Transaction | Task 3 |
| KPI: Conversion Rate (paid ÷ all created) | Task 3 |
| Revenue over time SVG chart, daily/monthly bucket | Task 3 + 5 |
| Alpine hover tooltip on bars | Task 5 |
| Empty state when no data | Task 4 + 5 + 7 |
| By package type horizontal bars | Task 6 |
| By location horizontal bars | Task 6 |
| Top 10 packages table | Task 6 |
| Payment funnel strip | Task 7 |
| Feature tests | Task 8 |
| No new JS library | ✓ all tasks — SVG + Alpine only |

All spec requirements covered. No placeholders. Method names consistent across all tasks (`setPreset`, `resolvedRange`, `buildTimeChart`, `kpis`, `byType`, `byLocation`, `topPackages`, `funnel`).
