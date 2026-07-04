# Analytics: LTV + Attendance Insight Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a per-child lifetime-value (LTV) detail modal to `admin/players`, and a new "Attendance" tab to Owner Insights showing 30-day attendance rate by program and by location.

**Architecture:** Two independent, additive features on two existing Livewire pages. `Players.php` gains modal state + a query scoped to one child's paid transactions. `Owner.php` gains one more private data-builder method (`attendanceData()`) following the exact pattern of its existing five, wired into `render()` and displayed as a 5th Alpine-driven tab reusing the page's existing KPI-card and fill-bar markup.

**Tech Stack:** Laravel 11, Livewire 3, Alpine.js (already used for the existing Owner Insights tabs), Pest.

## Global Constraints

- LTV total = sum of `Transaction.amount` where `status = 'paid'` and `child_id` matches, per spec section 1.
- LTV transaction history table shows ALL transaction statuses (not just paid) — pending/cancelled attempts are useful admin context, per spec section 1.
- Modal overlay pattern: `fixed inset-0` backdrop + `relative bg-surface rounded-2xl border border-line shadow-xl`, `max-w-2xl` (matches `attendances.blade.php:207`), per spec section 1.
- Attendance rate = `present / (present + no_show + sick + permit)` as a percentage; `make_up` is excluded from both numerator and denominator, per spec section 2.
- Attendance window = trailing 30 days, fixed, no user-adjustable range — matches the rest of Owner Insights' trend calculations, per spec section 2 and "Out of scope".
- Attendance tab traffic-light bar thresholds: green ≥80%, amber 50–79%, red <50% (stricter than the existing capacity-fill thresholds, since showing up is the expected norm) — per spec section 2 point 2.
- Attendance tab reuses the page's existing KPI-card grid and fill-bar list markup exactly (`owner.blade.php:277-333`) — no new visual pattern introduced, per spec section 2's closing paragraph.
- No date-range picker, no drill-down navigation, no changes to `Reports.php` — per spec "Out of scope".

---

### Task 1: LTV modal on Players page

**Files:**
- Modify: `app/Livewire/Admin/Players.php`
- Modify: `resources/views/livewire/admin/players.blade.php`
- Test: `tests/Feature/Admin/PlayersTest.php`

**Interfaces:**
- Produces: `openLtv(int $childId): void`, `closeLtv(): void`, `public bool $showLtv`, `public ?int $ltvChildId` on `Players` — used only by this task's own view; no other task depends on them.

- [ ] **Step 1: Write the failing tests**

Add to `tests/Feature/Admin/PlayersTest.php` (append at end of file; add `use App\Models\Package;` and `use App\Models\Transaction;` to the existing `use` block at the top if not already present — check first with `grep -n "^use " tests/Feature/Admin/PlayersTest.php`):

```php
it('opens the ltv modal and shows total lifetime spend', function () {
    $child = Child::factory()->create(['name' => 'LTV Test Child']);
    $package = Package::factory()->regular()->create();

    Transaction::factory()->create([
        'child_id'   => $child->id,
        'package_id' => $package->id,
        'amount'     => 500000,
        'status'     => 'paid',
    ]);
    Transaction::factory()->create([
        'child_id'   => $child->id,
        'package_id' => $package->id,
        'amount'     => 300000,
        'status'     => 'paid',
    ]);
    Transaction::factory()->create([
        'child_id'   => $child->id,
        'package_id' => $package->id,
        'amount'     => 200000,
        'status'     => 'pending',
    ]);

    Livewire::actingAs($this->admin)
        ->test(Players::class)
        ->call('openLtv', $child->id)
        ->assertSet('showLtv', true)
        ->assertSet('ltvChildId', $child->id)
        ->assertSee('Rp 800.000') // 500,000 + 300,000 paid only, pending excluded
        ->assertSee($package->name);
});

it('shows empty state when a child has no transactions', function () {
    $child = Child::factory()->create(['name' => 'No Transactions Child']);

    Livewire::actingAs($this->admin)
        ->test(Players::class)
        ->call('openLtv', $child->id)
        ->assertSee('No transactions yet');
});

it('closes the ltv modal', function () {
    $child = Child::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(Players::class)
        ->call('openLtv', $child->id)
        ->assertSet('showLtv', true)
        ->call('closeLtv')
        ->assertSet('showLtv', false)
        ->assertSet('ltvChildId', null);
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter "opens the ltv modal|shows empty state when a child has no transactions|closes the ltv modal"`
Expected: FAIL — `Call to undefined method App\Livewire\Admin\Players::openLtv()`.

- [ ] **Step 3: Add modal state and query to `Players.php`**

Replace the full contents of `app/Livewire/Admin/Players.php` with:

```php
<?php

namespace App\Livewire\Admin;

use App\Models\Child;
use App\Models\Transaction;
use Livewire\Component;
use Livewire\WithPagination;

class Players extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $filterStatus = '';

    public bool $showLtv     = false;
    public ?int $ltvChildId  = null;

    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }

    public function openLtv(int $childId): void
    {
        $this->ltvChildId = $childId;
        $this->showLtv    = true;
    }

    public function closeLtv(): void
    {
        $this->showLtv    = false;
        $this->ltvChildId = null;
    }

    public function render()
    {
        return view('livewire.admin.players', [
            'players' => Child::with('parent')
                ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
                ->when($this->search, fn($q) => $q
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhereHas('parent', fn($u) => $u->where('name', 'like', "%{$this->search}%")))
                ->orderByRaw("FIELD(status,'pending','active','unregistered','inactive')")
                ->orderBy('name')
                ->paginate(15),
            'ltvChild'        => $this->ltvChildId ? Child::with('parent')->find($this->ltvChildId) : null,
            'ltvTotal'        => $this->ltvChildId ? Transaction::where('child_id', $this->ltvChildId)->where('status', 'paid')->sum('amount') : 0,
            'ltvTransactions' => $this->ltvChildId
                ? Transaction::where('child_id', $this->ltvChildId)->with('package')->latest('created_at')->get()
                : collect(),
        ]);
    }
}
```

- [ ] **Step 4: Add the "View" action and modal to the blade view**

In `resources/views/livewire/admin/players.blade.php`, add a "View" button after the status badge cell. Replace:

```blade
                            <td class="py-3 px-4">
                                <x-badge :status="$player->status">{{ __('messages.status.'.$player->status) }}</x-badge>
                            </td>
                        </tr>
```

with:

```blade
                            <td class="py-3 px-4">
                                <x-badge :status="$player->status">{{ __('messages.status.'.$player->status) }}</x-badge>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <x-btn variant="secondary" size="sm" wire:click="openLtv({{ $player->id }})">
                                    View
                                </x-btn>
                            </td>
                        </tr>
```

Add the matching header cell. Replace:

```blade
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.players.col_status') }}</th>
                    </tr>
```

with:

```blade
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.players.col_status') }}</th>
                        <th class="py-3 px-4"></th>
                    </tr>
```

Update the empty-state `colspan` from `6` to `7`. Replace:

```blade
                            <td colspan="6" class="py-2">
```

with:

```blade
                            <td colspan="7" class="py-2">
```

Add the modal markup at the end of the file, just before the final closing `</div>` (the one that closes `<div class="max-w-6xl mx-auto">`). Replace:

```blade
    </div>{{-- /max-w-6xl --}}
</div>
```

with:

```blade
    @if ($showLtv)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-navy/40" wire:click="closeLtv"></div>
        <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-2xl max-h-[85vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-line">
                <div>
                    <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">{{ $ltvChild?->name }}</h3>
                    <p class="text-xs text-muted">{{ $ltvChild?->parent?->name }}</p>
                </div>
                <button wire:click="closeLtv" class="text-muted hover:text-navy p-1 leading-none">&#x2715;</button>
            </div>

            <div class="px-6 py-4 border-b border-line">
                <p class="text-[11px] font-semibold text-muted uppercase tracking-wide leading-tight">Total Lifetime Spend</p>
                <p class="text-xl font-extrabold text-navy leading-none tracking-tight mt-1.5">Rp {{ number_format($ltvTotal, 0, ',', '.') }}</p>
            </div>

            <div class="p-6">
                <p class="text-[10px] font-bold uppercase tracking-widest text-faint mb-3">Transaction History</p>
                @if ($ltvTransactions->isEmpty())
                    <p class="text-center text-sm text-muted py-6">No transactions yet</p>
                @else
                    <div class="divide-y divide-line">
                        @foreach ($ltvTransactions as $t)
                            <div class="py-3 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-ink truncate">{{ $t->package?->name ?? '—' }}</p>
                                    <p class="text-[11px] text-faint">{{ $t->created_at->format('d M Y') }}</p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-sm font-bold text-navy">Rp {{ number_format($t->amount, 0, ',', '.') }}</p>
                                    <x-badge :status="$t->status">{{ ucfirst($t->status) }}</x-badge>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    </div>{{-- /max-w-6xl --}}
</div>
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test tests/Feature/Admin/PlayersTest.php`
Expected: PASS, all tests including the 3 new ones.

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/Admin/Players.php resources/views/livewire/admin/players.blade.php tests/Feature/Admin/PlayersTest.php
git commit -m "feat: add per-child LTV modal to admin players page"
```

---

### Task 2: Attendance tab in Owner Insights

**Files:**
- Modify: `app/Livewire/Admin/Owner.php`
- Modify: `resources/views/livewire/admin/owner.blade.php`
- Test: `tests/Feature/Admin/OwnerTest.php`

**Interfaces:**
- Consumes: nothing from Task 1 (fully independent).
- Produces: `attendanceData(): array` (private method on `Owner`) returning `['overall' => ?float, 'present' => int, 'total' => int, 'by_program' => Collection<string, ['rate' => ?float, 'total' => int]>, 'by_location' => Collection<string, ['rate' => ?float, 'total' => int]>]`. Not consumed by any other task in this plan.

- [ ] **Step 1: Write the failing tests**

Add to `tests/Feature/Admin/OwnerTest.php` (append at end of file; the file already imports `Schedule`, `Location`, `Package`, `Child` — additionally add `use App\Models\Attendance;` and `use App\Models\Program;` to the top `use` block if not already present — check with `grep -n "^use " tests/Feature/Admin/OwnerTest.php`):

```php
it('shows the attendance tab with overall rate and breakdowns', function () {
    $program  = Program::factory()->create(['name' => 'Junior League']);
    $location = Location::factory()->create(['name' => 'Cikarang Court']);
    $schedule = Schedule::factory()->create([
        'program_id'  => $program->id,
        'location_id' => $location->id,
    ]);

    Attendance::factory()->present()->create(['schedule_id' => $schedule->id, 'attended_at' => now()]);
    Attendance::factory()->present()->create(['schedule_id' => $schedule->id, 'attended_at' => now()]);
    Attendance::factory()->present()->create(['schedule_id' => $schedule->id, 'attended_at' => now()]);
    Attendance::factory()->absent()->create(['schedule_id' => $schedule->id, 'attended_at' => now()]);

    Livewire::actingAs($this->admin)
        ->test(Owner::class)
        ->assertSee('Junior League')
        ->assertSee('Cikarang Court')
        ->assertSee('75%'); // 3 present / 4 total
});

it('excludes make-up attendances from the attendance rate', function () {
    $schedule = Schedule::factory()->create();

    Attendance::factory()->present()->create(['schedule_id' => $schedule->id, 'attended_at' => now()]);
    Attendance::factory()->create(['schedule_id' => $schedule->id, 'attended_at' => now(), 'status' => 'make_up']);

    Livewire::actingAs($this->admin)
        ->test(Owner::class)
        ->assertSee('100%'); // 1 present / 1 total, make_up excluded from both sides
});

it('shows an empty state when there is no attendance in the last 30 days', function () {
    Livewire::actingAs($this->admin)
        ->test(Owner::class)
        ->assertSee('No attendance records in the last 30 days');
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter "shows the attendance tab with overall rate|excludes make-up attendances|shows an empty state when there is no attendance"`
Expected: FAIL — `assertSee('75%')` etc. fail because the "Attendance" tab and its content don't exist yet.

- [ ] **Step 3: Add `attendanceData()` to `Owner.php`**

Add `use App\Models\Attendance;` to the top of `app/Livewire/Admin/Owner.php` (it is not currently imported — confirm with `grep -n "^use " app/Livewire/Admin/Owner.php`).

Add this method after `leadData()` (i.e. after the closing `}` of the method ending at line 278, before the `// ─── Insights` comment block):

```php
    // ─── H. Attendance rate by program & location ────────────────────

    private function attendanceData(): array
    {
        $since = now()->subDays(30)->toDateString();

        $rows = Attendance::whereIn('status', ['present', 'no_show', 'sick', 'permit'])
            ->where('attended_at', '>=', $since)
            ->with(['schedule.program', 'schedule.location'])
            ->get();

        $rate = fn($items) => $items->count() > 0
            ? round($items->where('status', 'present')->count() / $items->count() * 100, 1)
            : null;

        $overall = $rate($rows);

        $byProgram = $rows->groupBy(fn($a) => $a->schedule?->program?->name ?? 'Private / Unassigned')
            ->map(fn($items) => ['rate' => $rate($items), 'total' => $items->count()])
            ->sortByDesc('total');

        $byLocation = $rows->groupBy(fn($a) => $a->schedule?->location?->name ?? 'Unknown')
            ->map(fn($items) => ['rate' => $rate($items), 'total' => $items->count()])
            ->sortByDesc('total');

        return [
            'overall'     => $overall,
            'present'     => $rows->where('status', 'present')->count(),
            'total'       => $rows->count(),
            'by_program'  => $byProgram,
            'by_location' => $byLocation,
        ];
    }
```

Wire it into `render()`. Replace:

```php
    public function render()
    {
        $renewal  = $this->renewalData();
        $ar       = $this->arData();
        $capacity = $this->capacityData();
        $leads    = $this->leadData();

        return view('livewire.admin.owner', [
            'renewal'     => $renewal,
            'ar'          => $ar,
            'payroll'     => $this->payrollData(),
            'capacity'    => $capacity,
            'leads'       => $leads,
            'events'      => $this->eventsData(),
            'insights'    => $this->insightsData($renewal, $ar, $capacity, $leads),
            'leaderboard' => $this->leaderboardData(),
        ]);
    }
```

with:

```php
    public function render()
    {
        $renewal  = $this->renewalData();
        $ar       = $this->arData();
        $capacity = $this->capacityData();
        $leads    = $this->leadData();

        return view('livewire.admin.owner', [
            'renewal'     => $renewal,
            'ar'          => $ar,
            'payroll'     => $this->payrollData(),
            'capacity'    => $capacity,
            'leads'       => $leads,
            'events'      => $this->eventsData(),
            'insights'    => $this->insightsData($renewal, $ar, $capacity, $leads),
            'leaderboard' => $this->leaderboardData(),
            'attendance'  => $this->attendanceData(),
        ]);
    }
```

- [ ] **Step 4: Add the "Attendance" tab button**

In `resources/views/livewire/admin/owner.blade.php`, add a 5th tab button after the "Pipeline" button. Replace:

```blade
            <button @click="tab='pipeline'"
                    :class="tab === 'pipeline' ? 'bg-navy text-off shadow-sm' : 'text-muted hover:text-ink hover:bg-off'"
                    class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-150 text-sm font-bold">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
                Pipeline
            </button>
        </div>
```

with:

```blade
            <button @click="tab='pipeline'"
                    :class="tab === 'pipeline' ? 'bg-navy text-off shadow-sm' : 'text-muted hover:text-ink hover:bg-off'"
                    class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-150 text-sm font-bold">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
                Pipeline
            </button>

            <button @click="tab='attendance'"
                    :class="tab === 'attendance' ? 'bg-navy text-off shadow-sm' : 'text-muted hover:text-ink hover:bg-off'"
                    class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-150 text-sm font-bold">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Attendance
            </button>
        </div>
```

- [ ] **Step 5: Add the "Attendance" tab content panel**

In `resources/views/livewire/admin/owner.blade.php`, find the end of the Pipeline tab's `<div x-show="tab === 'pipeline'" ...>...</div>` block — it is the last tab panel, immediately followed by the closing `</div>` of the outer `x-data="{ tab: 'members' }"` wrapper. Locate that closing sequence (the Events section closes at line 721 per the file's current structure, i.e. `</section>` followed by the pipeline panel's closing `</div>`, followed by the outer wrapper's closing `</div>`). Insert the new tab panel between the Pipeline panel's closing `</div>` and the outer wrapper's closing `</div>`:

```blade
        {{-- ══════════════════════════════════════════════
             TAB: ATTENDANCE — 30-day attendance rate by program & location
        ══════════════════════════════════════════════ --}}
        <div x-show="tab === 'attendance'"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="mt-5 space-y-5">

            <section>
                <div class="flex items-center gap-2 mb-3">
                    <h2 class="text-sm font-extrabold text-navy uppercase tracking-wide">Attendance Rate</h2>
                    <span class="text-[11px] text-faint">Last 30 days</span>
                </div>

                @if ($attendance['total'] === 0)
                    <div class="bg-surface border border-line rounded-xl">
                        <p class="px-5 py-8 text-center text-sm text-muted">No attendance records in the last 30 days</p>
                    </div>
                @else
                    <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 mb-3">
                        <div class="bg-surface border border-line rounded-xl px-4 pt-4 pb-3 flex flex-col gap-3">
                            <p class="text-[11px] font-semibold text-muted uppercase tracking-wide leading-tight">Overall Attendance Rate</p>
                            <div>
                                <p class="text-xl font-extrabold text-navy leading-none tracking-tight">{{ $attendance['overall'] }}%</p>
                                <p class="text-[11px] text-muted mt-1.5">{{ $attendance['present'] }}/{{ $attendance['total'] }} sessions</p>
                            </div>
                            <div class="h-0.5 w-10 bg-navy rounded-full"></div>
                        </div>
                        <div class="bg-surface border border-line rounded-xl px-4 pt-4 pb-3 flex flex-col gap-3">
                            <p class="text-[11px] font-semibold text-muted uppercase tracking-wide leading-tight">Present</p>
                            <div>
                                <p class="text-xl font-extrabold text-navy leading-none tracking-tight">{{ $attendance['present'] }}</p>
                                <p class="text-[11px] text-muted mt-1.5">of {{ $attendance['total'] }} tracked</p>
                            </div>
                            <div class="h-0.5 w-10 bg-[#15803D] rounded-full"></div>
                        </div>
                        <div class="bg-surface border border-line rounded-xl px-4 pt-4 pb-3 flex flex-col gap-3">
                            <p class="text-[11px] font-semibold text-muted uppercase tracking-wide leading-tight">Absent / Sick / Permit</p>
                            <div>
                                <p class="text-xl font-extrabold text-navy leading-none tracking-tight">{{ $attendance['total'] - $attendance['present'] }}</p>
                                <p class="text-[11px] text-muted mt-1.5">of {{ $attendance['total'] }} tracked</p>
                            </div>
                            <div class="h-0.5 w-10 bg-[#B45309] rounded-full"></div>
                        </div>
                    </div>

                    <p class="text-[10px] font-bold uppercase tracking-widest text-faint mb-2 mt-4">By Program</p>
                    <div class="bg-surface border border-line rounded-xl overflow-hidden mb-3">
                        <div class="divide-y divide-line">
                            @foreach ($attendance['by_program'] as $label => $row)
                                @php
                                    $rate = $row['rate'] ?? 0;
                                    $barColor = $rate >= 80 ? 'bg-[#15803D]' : ($rate >= 50 ? 'bg-[#B45309]' : 'bg-[#B91C1C]');
                                @endphp
                                <div class="px-5 py-3 flex items-center gap-4">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold text-ink truncate">{{ $label }}</p>
                                    </div>
                                    <div class="w-40 shrink-0 hidden sm:block">
                                        <div class="h-2 bg-off rounded-full overflow-hidden">
                                            <div class="h-full {{ $barColor }} rounded-full" style="width: {{ min($rate, 100) }}%"></div>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0 w-24">
                                        <p class="text-sm font-bold text-navy">{{ $row['rate'] }}%</p>
                                        <p class="text-[11px] text-muted">({{ $row['total'] }} sessions)</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <p class="text-[10px] font-bold uppercase tracking-widest text-faint mb-2">By Location</p>
                    <div class="bg-surface border border-line rounded-xl overflow-hidden">
                        <div class="divide-y divide-line">
                            @foreach ($attendance['by_location'] as $label => $row)
                                @php
                                    $rate = $row['rate'] ?? 0;
                                    $barColor = $rate >= 80 ? 'bg-[#15803D]' : ($rate >= 50 ? 'bg-[#B45309]' : 'bg-[#B91C1C]');
                                @endphp
                                <div class="px-5 py-3 flex items-center gap-4">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold text-ink truncate">{{ $label }}</p>
                                    </div>
                                    <div class="w-40 shrink-0 hidden sm:block">
                                        <div class="h-2 bg-off rounded-full overflow-hidden">
                                            <div class="h-full {{ $barColor }} rounded-full" style="width: {{ min($rate, 100) }}%"></div>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0 w-24">
                                        <p class="text-sm font-bold text-navy">{{ $row['rate'] }}%</p>
                                        <p class="text-[11px] text-muted">({{ $row['total'] }} sessions)</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>

        </div>{{-- /attendance tab --}}
```

- [ ] **Step 6: Run tests to verify they pass**

Run: `php artisan test tests/Feature/Admin/OwnerTest.php`
Expected: PASS, all tests including the 3 new ones.

- [ ] **Step 7: Commit**

```bash
git add app/Livewire/Admin/Owner.php resources/views/livewire/admin/owner.blade.php tests/Feature/Admin/OwnerTest.php
git commit -m "feat: add attendance-rate tab to Owner Insights"
```

---

### Task 3: Full verification

**Files:** none (verification only)

- [ ] **Step 1: Run both affected test files**

Run: `php artisan test tests/Feature/Admin/PlayersTest.php tests/Feature/Admin/OwnerTest.php`
Expected: PASS, all tests (original + Task 1 + Task 2 additions).

- [ ] **Step 2: Run the full test suite**

Run: `php -d memory_limit=512M ./vendor/bin/pest`
Expected: PASS, no regressions (this repo's CLI defaults to a 128M memory_limit that OOMs the full suite; the raised limit is the established workaround for this environment).

- [ ] **Step 3: Manual smoke check**

Visit `admin/players` as an admin user:
- Click "View" on a player row with existing paid transactions, confirm the modal shows total lifetime spend and a transaction history table with status badges.
- Click "View" on a player with no transactions, confirm the "No transactions yet" empty state.
- Close the modal via the backdrop click and via the × button.

Visit `admin/owner` as an admin user:
- Click the new "Attendance" tab, confirm the KPI cards (Overall Attendance Rate, Present, Absent/Sick/Permit) and the "By Program" / "By Location" fill-bar lists render.
- Confirm the tab's visual style (card grid + fill bars) matches the Members tab's Class Utilization section — no visual inconsistency introduced.
