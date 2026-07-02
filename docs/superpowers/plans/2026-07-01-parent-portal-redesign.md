# Parent Portal Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the parent portal's 11-page navigation with a single consolidated `parent.home` page per child, while reusing existing wizard components (leave request, makeup class, private session) and the existing visual system unchanged.

**Architecture:** A new `App\Livewire\Portal\Home` component owns child selection state and assembles read-only data (next session, payment summary, attendance count, active event) by calling a new stateless helper, `App\Support\ChildSchedulePlanner`, which extracts the per-child schedule-validity algorithm already used in `App\Livewire\Portal\Dashboard`. Section markup lives in small Blade partials (`resources/views/components/portal/*.blade.php`) that receive plain data, not Livewire components — avoiding extra network round trips on one page. The three existing wizards (`LeaveRequests`, `PrivateSessions`, `MakeUpClasses`) are embedded via `@livewire(...)` inside modals triggered from a Quick Actions section, unmodified.

**Tech Stack:** Laravel 11, Livewire 3, Alpine.js (`x-collapse`/`x-show` for inline expand and modals), Tailwind v4, Pest for tests.

## Global Constraints

- Spec: `docs/superpowers/specs/2026-07-01-parent-portal-redesign-design.md`
- Reuse existing query/business logic — do not duplicate `Dashboard`'s session-validity algorithm inline in `Home`; extract it to `ChildSchedulePlanner` (Task 1) and have both consume it where practical.
- Visual system is locked already: navy `#1A2F5E` / off-white `#F4F7FC`, Public Sans + IBM Plex Mono fonts (`resources/css/app.css`, `resources/views/components/app.blade.php` — already shipped, do not touch). Use existing `<x-card>`, `<x-badge>`, `<x-btn>` components.
- All new UI copy in English (matches existing i18n convention — add new `lang/en/messages.php` / `lang/id/messages.php` keys under a `portal.home` namespace for every new string; do not hardcode user-facing text).
- Direct rollout, no feature flag — old nav routes are removed in the same change that ships Home (Task 7).
- `php artisan test` must pass (full suite) before any task is considered done.

---

### Task 1: ChildSchedulePlanner helper

**Files:**
- Create: `app/Support/ChildSchedulePlanner.php`
- Test: `tests/Unit/Support/ChildSchedulePlannerTest.php`

**Interfaces:**
- Produces: `ChildSchedulePlanner::approvedEnrollments(Child $child): Collection<Enrollment>`, `ChildSchedulePlanner::sessionsOn(Collection $enrollments, Carbon $date): Collection<Enrollment>`, `ChildSchedulePlanner::nextSession(Child $child): ?array` (keys: `program`, `location`, `coach`, `date` (Carbon), `start` (string `H:i:s`), `end` (string `H:i:s`)), `ChildSchedulePlanner::weekSessions(Child $child): Collection` keyed by lowercase day name (`monday`...`sunday`), each value a `Collection` of arrays with keys `program`, `location`, `start`, `end`.

- [ ] **Step 1: Write the failing unit test**

```php
<?php

use App\Models\Child;
use App\Models\Enrollment;
use App\Models\Location;
use App\Models\Package;
use App\Models\Program;
use App\Models\Schedule;
use App\Models\User;
use App\Support\ChildSchedulePlanner;
use Carbon\Carbon;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-07-06')); // a Monday

    $this->parentUser = User::factory()->withRole('parent')->approved()->create();
    $this->child = Child::factory()->create(['parent_id' => $this->parentUser->id, 'status' => 'active']);

    $location = Location::factory()->create();
    $program  = Program::factory()->create();
    $this->schedule = Schedule::factory()->create([
        'location_id' => $location->id,
        'program_id'  => $program->id,
        'day_of_week' => 'monday',
        'start_time'  => '16:00:00',
        'end_time'    => '17:00:00',
        'is_active'   => true,
    ]);
});

afterEach(fn () => Carbon::setTestNow());

it('returns the next valid session for a child with an active enrollment', function () {
    Enrollment::factory()->create([
        'child_id'    => $this->child->id,
        'schedule_id' => $this->schedule->id,
        'type'        => 'program',
        'status'      => 'approved',
        'started_at'  => '2026-06-01',
        'expires_at'  => '2026-12-31',
    ]);

    $next = ChildSchedulePlanner::nextSession($this->child->fresh());

    expect($next)->not->toBeNull()
        ->and($next['program'])->toBe($this->schedule->program->name)
        ->and($next['date']->toDateString())->toBe('2026-07-06');
});

it('returns null when the child has no approved program enrollment', function () {
    expect(ChildSchedulePlanner::nextSession($this->child->fresh()))->toBeNull();
});

it('excludes sessions before the enrollment start date', function () {
    Enrollment::factory()->create([
        'child_id'    => $this->child->id,
        'schedule_id' => $this->schedule->id,
        'type'        => 'program',
        'status'      => 'approved',
        'started_at'  => '2026-07-13', // next Monday
        'expires_at'  => '2026-12-31',
    ]);

    $next = ChildSchedulePlanner::nextSession($this->child->fresh());

    expect($next['date']->toDateString())->toBe('2026-07-13');
});

it('builds a week map of sessions keyed by day name', function () {
    Enrollment::factory()->create([
        'child_id'    => $this->child->id,
        'schedule_id' => $this->schedule->id,
        'type'        => 'program',
        'status'      => 'approved',
        'started_at'  => '2026-06-01',
        'expires_at'  => '2026-12-31',
    ]);

    $week = ChildSchedulePlanner::weekSessions($this->child->fresh());

    expect($week->has('monday'))->toBeTrue()
        ->and($week->get('monday'))->toHaveCount(1);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Support/ChildSchedulePlannerTest.php`
Expected: FAIL — `Class "App\Support\ChildSchedulePlanner" not found`

- [ ] **Step 3: Write the implementation**

```php
<?php

namespace App\Support;

use App\Models\Child;
use App\Models\Enrollment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ChildSchedulePlanner
{
    public static function approvedEnrollments(Child $child): Collection
    {
        return Enrollment::where('child_id', $child->id)
            ->where('status', 'approved')
            ->where('type', 'program')
            ->whereNotNull('schedule_id')
            ->with(['schedule.program', 'schedule.location', 'schedule.coach.user'])
            ->get();
    }

    public static function isSessionValidOn(Enrollment $enrollment, Carbon $date): bool
    {
        $day = $date->copy()->startOfDay();
        $weekday = strtolower($day->format('l'));

        if ($enrollment->schedule->day_of_week !== $weekday) {
            return false;
        }

        if ($enrollment->started_at && $day->lt($enrollment->started_at->copy()->startOfDay())) {
            return false;
        }

        if ($enrollment->total_sessions && $enrollment->started_at) {
            $firstSession = $enrollment->started_at->copy()->startOfDay();
            while (strtolower($firstSession->format('l')) !== $weekday) {
                $firstSession->addDay();
            }
            if ($day->lt($firstSession)) {
                return false;
            }
            $sessionNumber = intdiv($firstSession->diffInDays($day), 7) + 1;
            if ($sessionNumber > $enrollment->total_sessions) {
                return false;
            }
        } elseif ($enrollment->expires_at && $day->gt($enrollment->expires_at->copy()->startOfDay())) {
            return false;
        }

        return true;
    }

    public static function sessionsOn(Collection $enrollments, Carbon $date): Collection
    {
        return $enrollments->filter(
            fn (Enrollment $e) => self::isSessionValidOn($e, $date)
        )->values();
    }

    public static function nextSession(Child $child): ?array
    {
        $enrollments = self::approvedEnrollments($child);

        if ($enrollments->isEmpty()) {
            return null;
        }

        for ($i = 0; $i < 14; $i++) {
            $date = now()->copy()->addDays($i);
            $sessions = self::sessionsOn($enrollments, $date);

            if ($sessions->isNotEmpty()) {
                $enrollment = $sessions->first();

                return [
                    'program'  => $enrollment->schedule->program->name,
                    'location' => $enrollment->schedule->location->name,
                    'coach'    => $enrollment->schedule->coach?->user?->name,
                    'date'     => $date,
                    'start'    => $enrollment->schedule->start_time,
                    'end'      => $enrollment->schedule->end_time,
                ];
            }
        }

        return null;
    }

    public static function weekSessions(Child $child): Collection
    {
        $enrollments = self::approvedEnrollments($child);
        $weekStart   = now()->startOfWeek(Carbon::MONDAY);
        $days        = collect(range(0, 6))->map(fn (int $i) => $weekStart->copy()->addDays($i));

        return $days
            ->mapWithKeys(function (Carbon $date) use ($enrollments) {
                $sessions = self::sessionsOn($enrollments, $date)->map(fn (Enrollment $e) => [
                    'program'  => $e->schedule->program->name,
                    'location' => $e->schedule->location->name,
                    'start'    => $e->schedule->start_time,
                    'end'      => $e->schedule->end_time,
                ]);

                return [strtolower($date->format('l')) => $sessions];
            })
            ->filter(fn (Collection $sessions) => $sessions->isNotEmpty());
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/Support/ChildSchedulePlannerTest.php`
Expected: PASS — 4 tests

- [ ] **Step 5: Commit**

```bash
git add app/Support/ChildSchedulePlanner.php tests/Unit/Support/ChildSchedulePlannerTest.php
git commit -m "feat(portal): add ChildSchedulePlanner helper for per-child session lookup"
```

---

### Task 2: Home Livewire component, route, and empty-state view

**Files:**
- Create: `app/Livewire/Portal/Home.php`
- Create: `resources/views/livewire/portal/home.blade.php`
- Modify: `routes/web.php:96` (replace the `parent.dashboard` route)
- Test: `tests/Feature/Portal/HomeTest.php`

**Interfaces:**
- Consumes: `ChildSchedulePlanner::nextSession`, `ChildSchedulePlanner::weekSessions` (Task 1)
- Produces: route `parent.home`, Livewire component `App\Livewire\Portal\Home` with public method `switchChild(int $childId): void`

- [ ] **Step 1: Write the failing feature test**

```php
<?php

use App\Livewire\Portal\Home;
use App\Models\Child;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->parent = User::factory()->withRole('parent')->approved()->create();
});

it('renders the home page', function () {
    $this->actingAs($this->parent)->get(route('parent.home'))->assertOk();
});

it('shows an empty state when the parent has no children', function () {
    Livewire::actingAs($this->parent)
        ->test(Home::class)
        ->assertSee('Add your first player');
});

it('defaults to the first child and can switch children', function () {
    $first  = Child::factory()->create(['parent_id' => $this->parent->id, 'status' => 'active', 'name' => 'Aisyah']);
    $second = Child::factory()->create(['parent_id' => $this->parent->id, 'status' => 'active', 'name' => 'Bayu']);

    Livewire::actingAs($this->parent)
        ->test(Home::class)
        ->assertSet('activeChildId', $first->id)
        ->call('switchChild', $second->id)
        ->assertSet('activeChildId', $second->id);
});

it('refuses to switch to a child that does not belong to the parent', function () {
    $other = User::factory()->withRole('parent')->approved()->create();
    $notMine = Child::factory()->create(['parent_id' => $other->id, 'status' => 'active']);

    Livewire::actingAs($this->parent)
        ->test(Home::class)
        ->call('switchChild', $notMine->id)
        ->assertStatus(404);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Portal/HomeTest.php`
Expected: FAIL — route `parent.home` not defined

- [ ] **Step 3: Add the route**

In `routes/web.php`, find the parent route group (around line 96) and replace:

```php
Route::get('/dashboard',  fn() => view('parent.dashboard'))->name('dashboard');
```

with:

```php
Route::get('/dashboard',  fn() => redirect()->route('parent.home'))->name('dashboard');
Route::get('/home',       \App\Livewire\Portal\Home::class)->name('home');
```

- [ ] **Step 4: Write the Livewire component**

```php
<?php

namespace App\Livewire\Portal;

use App\Models\Child;
use App\Models\Event;
use App\Support\ChildSchedulePlanner;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Home extends Component
{
    public ?int $activeChildId = null;

    public function mount(): void
    {
        $storedId = session('portal_active_child_id');
        $children = Auth::user()->children()->orderBy('name')->get();

        $this->activeChildId = ($storedId && $children->contains('id', $storedId))
            ? $storedId
            : $children->first()?->id;
    }

    public function switchChild(int $childId): void
    {
        Auth::user()->children()->findOrFail($childId);

        $this->activeChildId = $childId;
        session(['portal_active_child_id' => $childId]);
    }

    public function getChildrenProperty()
    {
        return Auth::user()->children()->orderBy('name')->get();
    }

    public function getActiveChildProperty(): ?Child
    {
        if (! $this->activeChildId) {
            return null;
        }

        return Auth::user()->children()
            ->with(['enrollments.package', 'enrollments.schedule.location'])
            ->find($this->activeChildId);
    }

    public function render()
    {
        $child = $this->activeChild;
        $sectionFailed = false;

        [$nextSession, $weekSessions] = $this->safely(function () use ($child) {
            return $child
                ? [ChildSchedulePlanner::nextSession($child), ChildSchedulePlanner::weekSessions($child)]
                : [null, collect()];
        }, [null, collect()], $sectionFailed);

        [$transactions, $pendingAmount] = $this->safely(function () use ($child) {
            return $child
                ? [
                    Auth::user()->transactions()->where('child_id', $child->id)->with('package')->latest()->take(5)->get(),
                    Auth::user()->transactions()->where('child_id', $child->id)->where('status', 'pending')->sum('amount'),
                ]
                : [collect(), 0];
        }, [collect(), 0], $sectionFailed);

        $attendanceCounts = $this->safely(function () use ($child) {
            return $child
                ? $child->attendances()
                    ->whereMonth('attended_at', now()->month)
                    ->whereYear('attended_at', now()->year)
                    ->selectRaw('status, count(*) as total')
                    ->groupBy('status')
                    ->pluck('total', 'status')
                : collect();
        }, collect(), $sectionFailed);

        $activeEvent = $this->safely(function () use ($child) {
            if (! $child) {
                return null;
            }
            $locationIds = $child->enrollments->pluck('schedule.location_id')->filter()->values();

            return Event::where('is_active', true)
                ->where('is_registerable', true)
                ->whereDate('end_date', '>=', today())
                ->where(fn ($q) => $q->whereNull('location_id')->orWhereIn('location_id', $locationIds))
                ->first();
        }, null, $sectionFailed);

        return view('livewire.portal.home', [
            'children'         => $this->children,
            'child'            => $child,
            'nextSession'      => $nextSession,
            'weekSessions'     => $weekSessions,
            'transactions'     => $transactions,
            'pendingAmount'    => $pendingAmount,
            'attendanceCounts' => $attendanceCounts,
            'activeEvent'      => $activeEvent,
            'sectionFailed'    => $sectionFailed,
        ]);
    }

    /**
     * Run a data-loading closure; on failure, log it, flag $sectionFailed by
     * reference, and fall back to a safe default so one bad query can't take
     * down the whole page.
     */
    private function safely(callable $fn, mixed $default, bool &$sectionFailed): mixed
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            report($e);
            $sectionFailed = true;

            return $default;
        }
    }
}
```

- [ ] **Step 5: Write the view skeleton (empty state + placeholder slots for later tasks)**

```blade
<div>
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">{{ __('messages.portal.home.title') }}</h2>
        <p class="text-sm text-muted">{{ __('messages.portal.home.subtitle') }}</p>
    </div>

    @if ($children->isEmpty())
        <x-empty-state
            :title="__('messages.portal.home.empty_title')"
            :description="__('messages.portal.home.empty_desc')">
            <x-slot name="action">
                <x-btn href="{{ route('parent.enroll') }}" variant="add">{{ __('messages.portal.home.add_player') }}</x-btn>
            </x-slot>
        </x-empty-state>
    @else
        @if ($sectionFailed ?? false)
            <div class="mb-4 px-4 py-3 rounded-xl bg-[#B91C1C]/10 text-[#B91C1C] text-sm flex items-center justify-between">
                <span>{{ __('messages.portal.home.section_error') }}</span>
                <button wire:click="$refresh" class="font-semibold underline shrink-0 ml-3">{{ __('messages.portal.home.retry') }}</button>
            </div>
        @endif
        {{-- Sections added in Tasks 3-6 --}}
        <div id="portal-home-sections"></div>
    @endif
</div>
```

Add two more lang keys under `portal.home` in both files (introduced by this task's error-handling change):

```php
// en
'section_error' => "Some information couldn't load. The rest of the page still works.",
'retry'         => 'Retry',
```

```php
// id
'section_error' => 'Sebagian informasi gagal dimuat. Bagian lain tetap bisa dipakai.',
'retry'         => 'Coba lagi',
```

Add the new keys to `lang/en/messages.php` and `lang/id/messages.php` under a new `portal.home` array:

```php
// lang/en/messages.php — inside the 'portal' top-level array
'home' => [
    'title'       => 'Home',
    'subtitle'    => 'Everything about your child, in one place.',
    'empty_title' => 'No players yet',
    'empty_desc'  => 'Add your first player to see their schedule and progress here.',
    'add_player'  => 'Add player',
],
```

```php
// lang/id/messages.php — inside the 'portal' top-level array
'home' => [
    'title'       => 'Beranda',
    'subtitle'    => 'Semua tentang anak Anda, dalam satu halaman.',
    'empty_title' => 'Belum ada pemain',
    'empty_desc'  => 'Tambahkan pemain pertama untuk melihat jadwal dan progresnya di sini.',
    'add_player'  => 'Tambah pemain',
],
```

The empty-state test asserts the English string `'Add your first player'` — adjust the test to assert `__('messages.portal.home.empty_desc')` content instead if the exact wording above differs; the test step shown uses a fragment (`Add your first player`) that matches `empty_desc` — keep wording consistent between the test and the lang file.

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test tests/Feature/Portal/HomeTest.php`
Expected: PASS — 4 tests

- [ ] **Step 7: Commit**

```bash
git add app/Livewire/Portal/Home.php resources/views/livewire/portal/home.blade.php routes/web.php lang/en/messages.php lang/id/messages.php tests/Feature/Portal/HomeTest.php
git commit -m "feat(portal): add Home component with child switching and empty state"
```

---

### Task 3: Header, child switcher, and next-session card section

**Files:**
- Create: `resources/views/components/portal/child-switcher.blade.php`
- Create: `resources/views/components/portal/schedule-card.blade.php`
- Modify: `resources/views/livewire/portal/home.blade.php`
- Modify: `tests/Feature/Portal/HomeTest.php`

**Interfaces:**
- Consumes: `$children` (Collection<Child>), `$activeChildId` (int), `$nextSession` (array|null), `$weekSessions` (Collection) from `Home::render()` (Task 2)
- Produces: Blade components `<x-portal.child-switcher>` and `<x-portal.schedule-card>`

- [ ] **Step 1: Write the failing test**

```php
it('shows the next session for the active child', function () {
    $child = Child::factory()->create(['parent_id' => $this->parent->id, 'status' => 'active']);
    $location = \App\Models\Location::factory()->create(['name' => 'GOR Senayan']);
    $program  = \App\Models\Program::factory()->create(['name' => 'MVP']);
    $schedule = \App\Models\Schedule::factory()->create([
        'location_id' => $location->id,
        'program_id'  => $program->id,
        'day_of_week' => strtolower(now()->format('l')),
        'is_active'   => true,
    ]);
    \App\Models\Enrollment::factory()->create([
        'child_id' => $child->id, 'schedule_id' => $schedule->id,
        'type' => 'program', 'status' => 'approved',
        'started_at' => now()->subMonth(), 'expires_at' => now()->addMonth(),
    ]);

    Livewire::actingAs($this->parent)
        ->test(Home::class)
        ->assertSee('MVP')
        ->assertSee('GOR Senayan');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Portal/HomeTest.php --filter="shows the next session"`
Expected: FAIL — assertion fails, nothing rendered yet for sections

- [ ] **Step 3: Write the child switcher component**

```blade
@props(['children', 'activeChildId'])

@if ($children->count() > 1)
    <div class="flex gap-2 overflow-x-auto pb-1 mb-5">
        @foreach ($children as $c)
            <button wire:click="switchChild({{ $c->id }})"
                    @class([
                        'shrink-0 text-sm font-semibold px-4 py-1.5 rounded-full border transition-colors',
                        'bg-navy text-off border-navy' => $c->id === $activeChildId,
                        'bg-surface text-ink border-line hover:border-navy/40' => $c->id !== $activeChildId,
                    ])>
                {{ $c->name }}
            </button>
        @endforeach
    </div>
@endif
```

- [ ] **Step 4: Write the schedule card component**

```blade
@props(['nextSession', 'weekSessions'])

<x-card class="mb-4">
    <p class="text-xs font-bold uppercase tracking-wide text-muted mb-2">{{ __('messages.portal.home.next_session') }}</p>

    @if ($nextSession)
        <div class="flex items-start justify-between"
             x-data="{ open: false }">
            <div>
                <p class="text-base font-semibold text-ink">{{ $nextSession['program'] }}</p>
                <p class="text-sm text-muted">
                    {{ $nextSession['coach'] ?? __('messages.portal.home.no_coach') }} · {{ $nextSession['location'] }}
                </p>
                <p class="text-xs text-faint mt-1">{{ $nextSession['date']->translatedFormat('l, d M') }}</p>
            </div>
            <span class="font-mono text-sm text-navy font-medium shrink-0">
                {{ \Illuminate\Support\Carbon::parse($nextSession['start'])->format('H:i') }}
            </span>
        </div>

        @if ($weekSessions->isNotEmpty())
            <div x-data="{ open: false }" class="mt-3 pt-3 border-t border-line">
                <button @click="open = !open" class="text-xs font-semibold text-navy">
                    <span x-show="!open">{{ __('messages.portal.home.view_week') }}</span>
                    <span x-show="open" x-cloak>{{ __('messages.portal.home.hide_week') }}</span>
                </button>
                <div x-show="open" x-collapse x-cloak class="mt-3 space-y-2">
                    @foreach ($weekSessions as $day => $sessions)
                        @foreach ($sessions as $session)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-ink capitalize">{{ ucfirst($day) }} — {{ $session['program'] }}</span>
                                <span class="font-mono text-muted">{{ \Illuminate\Support\Carbon::parse($session['start'])->format('H:i') }}</span>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        @endif
    @else
        <p class="text-sm text-muted">{{ __('messages.portal.home.no_session') }}</p>
    @endif
</x-card>
```

- [ ] **Step 5: Wire both into the Home view**

Replace the placeholder div in `resources/views/livewire/portal/home.blade.php`:

```blade
<div id="portal-home-sections"></div>
```

with:

```blade
<x-portal.child-switcher :children="$children" :active-child-id="$activeChildId" />
<x-portal.schedule-card :next-session="$nextSession" :week-sessions="$weekSessions" />
```

Add the new lang keys used above to both `lang/en/messages.php` and `lang/id/messages.php` under `portal.home`:

```php
// en
'next_session' => 'Next session',
'no_coach'     => 'Coach TBA',
'no_session'   => 'No upcoming sessions scheduled.',
'view_week'    => 'View this week',
'hide_week'    => 'Hide this week',
```

```php
// id
'next_session' => 'Sesi berikutnya',
'no_coach'     => 'Pelatih belum ditentukan',
'no_session'   => 'Belum ada sesi terjadwal.',
'view_week'    => 'Lihat minggu ini',
'hide_week'    => 'Sembunyikan minggu ini',
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test tests/Feature/Portal/HomeTest.php`
Expected: PASS — all tests including the new one

- [ ] **Step 7: Commit**

```bash
git add resources/views/components/portal/child-switcher.blade.php resources/views/components/portal/schedule-card.blade.php resources/views/livewire/portal/home.blade.php lang/en/messages.php lang/id/messages.php tests/Feature/Portal/HomeTest.php
git commit -m "feat(portal): add child switcher and next-session card to home page"
```

---

### Task 4: Payment status section

**Files:**
- Create: `resources/views/components/portal/payment-card.blade.php`
- Modify: `resources/views/livewire/portal/home.blade.php`
- Modify: `tests/Feature/Portal/HomeTest.php`

**Interfaces:**
- Consumes: `$transactions` (Collection<Transaction>), `$pendingAmount` (int) from `Home::render()` (Task 2)
- Produces: `<x-portal.payment-card>`

- [ ] **Step 1: Write the failing test**

```php
it('shows payment status for the active child', function () {
    $child = Child::factory()->create(['parent_id' => $this->parent->id, 'status' => 'active']);
    \App\Models\Transaction::factory()->create([
        'user_id' => $this->parent->id, 'child_id' => $child->id,
        'status' => 'pending', 'amount' => 450000,
    ]);

    Livewire::actingAs($this->parent)
        ->test(Home::class)
        ->assertSee('450,000', escape: false);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Portal/HomeTest.php --filter="shows payment status"`
Expected: FAIL — nothing renders payment info yet

- [ ] **Step 3: Write the payment card component**

```blade
@props(['transactions', 'pendingAmount'])

<x-card class="mb-4" x-data="{ open: false }">
    <div class="flex items-center justify-between">
        <p class="text-xs font-bold uppercase tracking-wide text-muted">{{ __('messages.portal.home.payments') }}</p>
        @if ($pendingAmount > 0)
            <x-badge status="pending">{{ __('messages.portal.home.pending') }}</x-badge>
        @else
            <x-badge status="paid">{{ __('messages.portal.home.up_to_date') }}</x-badge>
        @endif
    </div>

    @if ($pendingAmount > 0)
        <p class="font-mono text-lg text-ink font-medium mt-2">
            Rp{{ number_format($pendingAmount, 0, ',', '.') }}
        </p>
        <p class="text-xs text-muted">{{ __('messages.portal.home.pending_hint') }}</p>
    @endif

    @if ($transactions->isNotEmpty())
        <button @click="open = !open" class="text-xs font-semibold text-navy mt-3 pt-3 border-t border-line block">
            <span x-show="!open">{{ __('messages.portal.home.view_history') }}</span>
            <span x-show="open" x-cloak>{{ __('messages.portal.home.hide_history') }}</span>
        </button>
        <div x-show="open" x-collapse x-cloak class="mt-3 border-t border-line">
            @foreach ($transactions as $trx)
                <div class="flex items-center justify-between py-2 border-b border-line last:border-b-0 text-sm">
                    <span class="text-ink">{{ $trx->package?->name ?? __('messages.portal.home.transaction') }}</span>
                    <span class="flex items-center gap-2">
                        <span class="font-mono text-muted">Rp{{ number_format($trx->amount, 0, ',', '.') }}</span>
                        <x-badge :status="$trx->status">{{ __('messages.status.'.$trx->status) }}</x-badge>
                    </span>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-xs text-faint mt-3 pt-3 border-t border-line">{{ __('messages.portal.home.no_history') }}</p>
    @endif
</x-card>
```

Add one more lang key under `portal.home` in both files (alongside the others added in this task):

```php
// en
'no_history' => 'No payment history yet.',
```

```php
// id
'no_history' => 'Belum ada riwayat pembayaran.',
```

- [ ] **Step 4: Wire into Home view**

In `resources/views/livewire/portal/home.blade.php`, add after `<x-portal.schedule-card ...>`:

```blade
<x-portal.payment-card :transactions="$transactions" :pending-amount="$pendingAmount" />
```

Add lang keys to both files under `portal.home`:

```php
// en
'payments'      => 'Payments',
'pending'       => 'Pending',
'up_to_date'    => 'Up to date',
'pending_hint'  => 'Upload proof of payment on the Payments page.',
'view_history'  => 'View payment history',
'hide_history'  => 'Hide payment history',
'transaction'   => 'Payment',
```

```php
// id
'payments'      => 'Pembayaran',
'pending'       => 'Menunggu',
'up_to_date'    => 'Lunas',
'pending_hint'  => 'Unggah bukti pembayaran di halaman Pembayaran.',
'view_history'  => 'Lihat riwayat pembayaran',
'hide_history'  => 'Sembunyikan riwayat pembayaran',
'transaction'   => 'Pembayaran',
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test tests/Feature/Portal/HomeTest.php`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add resources/views/components/portal/payment-card.blade.php resources/views/livewire/portal/home.blade.php lang/en/messages.php lang/id/messages.php tests/Feature/Portal/HomeTest.php
git commit -m "feat(portal): add payment status card to home page"
```

---

### Task 5: Attendance strip and quick actions (embedded wizards)

**Files:**
- Create: `resources/views/components/portal/attendance-strip.blade.php`
- Create: `resources/views/components/portal/quick-actions.blade.php`
- Modify: `resources/views/livewire/portal/home.blade.php`
- Modify: `tests/Feature/Portal/HomeTest.php`

**Interfaces:**
- Consumes: `$attendanceCounts` (Collection keyed by status), `$child` (Child|null) from `Home::render()` (Task 2)
- Produces: `<x-portal.attendance-strip>`, `<x-portal.quick-actions>`. Embeds existing `App\Livewire\Portal\LeaveRequests`, `App\Livewire\Portal\PrivateSessions`, `App\Livewire\Portal\MakeUpClasses` unmodified via `@livewire`.

- [ ] **Step 1: Write the failing test**

```php
it('shows quick action buttons that open the existing wizards', function () {
    Child::factory()->create(['parent_id' => $this->parent->id, 'status' => 'active']);

    Livewire::actingAs($this->parent)
        ->test(Home::class)
        ->assertSeeLivewire(\App\Livewire\Portal\LeaveRequests::class)
        ->assertSeeLivewire(\App\Livewire\Portal\PrivateSessions::class)
        ->assertSeeLivewire(\App\Livewire\Portal\MakeUpClasses::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Portal/HomeTest.php --filter="quick action buttons"`
Expected: FAIL — wizards not embedded yet

- [ ] **Step 3: Write the attendance strip component**

```blade
@props(['attendanceCounts'])

@php
    $present = $attendanceCounts->get('present', 0);
    $absent  = $attendanceCounts->get('no_show', 0);
@endphp

<x-card class="mb-4">
    <p class="text-xs font-bold uppercase tracking-wide text-muted mb-2">{{ __('messages.portal.home.attendance_this_month') }}</p>
    <div class="flex gap-6">
        <div>
            <p class="font-mono text-2xl text-[#15803D] font-medium">{{ $present }}</p>
            <p class="text-xs text-muted">{{ __('messages.portal.home.present') }}</p>
        </div>
        <div>
            <p class="font-mono text-2xl text-[#B91C1C] font-medium">{{ $absent }}</p>
            <p class="text-xs text-muted">{{ __('messages.portal.home.absent') }}</p>
        </div>
    </div>
</x-card>
```

- [ ] **Step 4: Write the quick actions component**

```blade
<x-card class="mb-4">
    <p class="text-xs font-bold uppercase tracking-wide text-muted mb-3">{{ __('messages.portal.home.quick_actions') }}</p>

    <div class="grid grid-cols-3 gap-2">
        <button @click="$dispatch('open-modal', 'leave-request')"
                class="flex flex-col items-center gap-1.5 text-center p-3 rounded-xl border border-line hover:border-navy/40 transition-colors">
            <span class="text-sm font-semibold text-ink">{{ __('messages.portal.home.leave_request') }}</span>
        </button>
        <button @click="$dispatch('open-modal', 'makeup-class')"
                class="flex flex-col items-center gap-1.5 text-center p-3 rounded-xl border border-line hover:border-navy/40 transition-colors">
            <span class="text-sm font-semibold text-ink">{{ __('messages.portal.home.makeup_class') }}</span>
        </button>
        <button @click="$dispatch('open-modal', 'private-session')"
                class="flex flex-col items-center gap-1.5 text-center p-3 rounded-xl border border-line hover:border-navy/40 transition-colors">
            <span class="text-sm font-semibold text-ink">{{ __('messages.portal.home.private_session') }}</span>
        </button>
    </div>

    <div x-data="{ activeModal: null }"
         @open-modal.window="activeModal = $event.detail"
         @keydown.escape.window="activeModal = null">

        <div x-show="activeModal === 'leave-request'" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-navy/40" @click="activeModal = null"></div>
            <div class="relative bg-surface rounded-2xl border border-line w-full max-w-lg max-h-[90vh] overflow-y-auto">
                @livewire('portal.leave-requests')
            </div>
        </div>

        <div x-show="activeModal === 'makeup-class'" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-navy/40" @click="activeModal = null"></div>
            <div class="relative bg-surface rounded-2xl border border-line w-full max-w-lg max-h-[90vh] overflow-y-auto">
                @livewire('portal.make-up-classes')
            </div>
        </div>

        <div x-show="activeModal === 'private-session'" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-navy/40" @click="activeModal = null"></div>
            <div class="relative bg-surface rounded-2xl border border-line w-full max-w-lg max-h-[90vh] overflow-y-auto">
                @livewire('portal.private-sessions')
            </div>
        </div>
    </div>
</x-card>
```

Note: confirm the Livewire kebab-case component aliases (`portal.leave-requests`, `portal.make-up-classes`, `portal.private-sessions`) resolve correctly by running `php artisan livewire:list` — Livewire 3 auto-discovers names from the class path (`App\Livewire\Portal\LeaveRequests` → `portal.leave-requests`, `App\Livewire\Portal\MakeUpClasses` → `portal.make-up-classes`, `App\Livewire\Portal\PrivateSessions` → `portal.private-sessions`). If any alias differs from what's listed, use the exact name from that command's output instead.

- [ ] **Step 5: Wire into Home view**

In `resources/views/livewire/portal/home.blade.php`, add after the payment card:

```blade
<x-portal.attendance-strip :attendance-counts="$attendanceCounts" />
<x-portal.quick-actions />
```

Add lang keys under `portal.home`:

```php
// en
'attendance_this_month' => 'Attendance this month',
'present'                => 'Present',
'absent'                 => 'Absent',
'quick_actions'          => 'Quick actions',
'leave_request'          => 'Request leave',
'makeup_class'           => 'Makeup class',
'private_session'        => 'Private session',
```

```php
// id
'attendance_this_month' => 'Kehadiran bulan ini',
'present'                => 'Hadir',
'absent'                 => 'Tidak hadir',
'quick_actions'          => 'Aksi cepat',
'leave_request'          => 'Ajukan izin',
'makeup_class'           => 'Kelas pengganti',
'private_session'        => 'Sesi privat',
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test tests/Feature/Portal/HomeTest.php`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add resources/views/components/portal/attendance-strip.blade.php resources/views/components/portal/quick-actions.blade.php resources/views/livewire/portal/home.blade.php lang/en/messages.php lang/id/messages.php tests/Feature/Portal/HomeTest.php
git commit -m "feat(portal): add attendance strip and quick-action wizard modals to home page"
```

---

### Task 6: Active event banner

**Files:**
- Create: `resources/views/components/portal/event-banner.blade.php`
- Modify: `resources/views/livewire/portal/home.blade.php`
- Modify: `tests/Feature/Portal/HomeTest.php`

**Interfaces:**
- Consumes: `$activeEvent` (Event|null) from `Home::render()` (Task 2)
- Produces: `<x-portal.event-banner>`

- [ ] **Step 1: Write the failing test**

```php
it('shows a banner when an event is open for registration', function () {
    $child = Child::factory()->create(['parent_id' => $this->parent->id, 'status' => 'active']);
    \App\Models\Event::factory()->create([
        'name' => 'Summer Camp 2026',
        'is_active' => true, 'is_registerable' => true,
        'location_id' => null,
        'start_date' => now()->addDays(5), 'end_date' => now()->addDays(10),
    ]);

    Livewire::actingAs($this->parent)
        ->test(Home::class)
        ->assertSee('Summer Camp 2026');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Portal/HomeTest.php --filter="open for registration"`
Expected: FAIL — banner not rendered yet

- [ ] **Step 3: Write the event banner component**

```blade
@props(['activeEvent'])

@if ($activeEvent)
    <a href="{{ route('parent.events') }}"
       class="block mb-4 px-4 py-3 rounded-xl bg-navy text-off flex items-center justify-between">
        <div>
            <p class="text-sm font-semibold">{{ $activeEvent->name }}</p>
            <p class="text-xs opacity-80">{{ __('messages.portal.home.event_open') }}</p>
        </div>
        <span class="text-xs font-semibold underline">{{ __('messages.portal.home.event_cta') }}</span>
    </a>
@endif
```

- [ ] **Step 4: Wire into Home view**

In `resources/views/livewire/portal/home.blade.php`, add as the first item right after the child switcher (it's a time-sensitive callout, belongs near the top):

```blade
<x-portal.child-switcher :children="$children" :active-child-id="$activeChildId" />
<x-portal.event-banner :active-event="$activeEvent" />
<x-portal.schedule-card :next-session="$nextSession" :week-sessions="$weekSessions" />
```

Add lang keys under `portal.home`:

```php
// en
'event_open' => 'Registration is open',
'event_cta'  => 'View details',
```

```php
// id
'event_open' => 'Pendaftaran dibuka',
'event_cta'  => 'Lihat detail',
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test tests/Feature/Portal/HomeTest.php`
Expected: PASS — all tests in the file

- [ ] **Step 6: Commit**

```bash
git add resources/views/components/portal/event-banner.blade.php resources/views/livewire/portal/home.blade.php lang/en/messages.php lang/id/messages.php tests/Feature/Portal/HomeTest.php
git commit -m "feat(portal): add active event banner to home page"
```

---

### Task 7: Trim navigation to 3 items and remove old nav routes

**Files:**
- Modify: `resources/views/components/parent-nav.blade.php`
- Modify: `routes/web.php`
- Modify: `tests/Feature/Portal/HomeTest.php`

**Interfaces:**
- Consumes: `Home` component (Task 2), existing `parent.news` and `parent.profile` routes (unchanged)

- [ ] **Step 1: Write the failing test**

```php
it('redirects the old dashboard route to home', function () {
    $this->actingAs($this->parent)->get(route('parent.dashboard'))
        ->assertRedirect(route('parent.home'));
});

it('returns 404 for routes removed from navigation', function () {
    $this->actingAs($this->parent)->get('/parent/payments')->assertNotFound();
    $this->actingAs($this->parent)->get('/parent/attendance')->assertNotFound();
    $this->actingAs($this->parent)->get('/parent/leaves')->assertNotFound();
    $this->actingAs($this->parent)->get('/parent/makeup')->assertNotFound();
    $this->actingAs($this->parent)->get('/parent/report-cards')->assertNotFound();
    $this->actingAs($this->parent)->get('/parent/players')->assertNotFound();
    $this->actingAs($this->parent)->get('/parent/events')->assertNotFound();
    $this->actingAs($this->parent)->get('/parent/private')->assertNotFound();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Portal/HomeTest.php --filter="removed from navigation"`
Expected: FAIL — old routes still exist (200, not 404)

- [ ] **Step 3: Remove the old routes**

In `routes/web.php`, inside the parent route group (the `profile.complete` middleware sub-group), delete these lines entirely:

```php
Route::get('/players',    fn() => view('parent.players'))->name('players');
Route::get('/events',     fn() => view('parent.events'))->name('events');
Route::get('/payments',   fn() => view('parent.payments'))->name('payments');
Route::get('/leaves',     fn() => view('parent.leaves'))->name('leaves');
Route::get('/attendance',    fn() => view('parent.attendance'))->name('attendance');
Route::get('/makeup',        fn() => view('parent.makeup'))->name('makeup');
Route::get('/report-cards',    fn() => view('parent.report-cards'))->name('report-cards');
Route::get('/private',         \App\Livewire\Portal\PrivateSessions::class)->name('private');
```

Keep `enroll`, `news`, `profile`, and the new `home`/`dashboard` redirect. The group should now read:

```php
Route::middleware('profile.complete')->group(function () {
    Route::get('/dashboard',  fn() => redirect()->route('parent.home'))->name('dashboard');
    Route::get('/home',       \App\Livewire\Portal\Home::class)->name('home');
    Route::get('/enroll',     \App\Livewire\Portal\EnrollPlayer::class)->name('enroll');
    Route::get('/news',       fn() => view('parent.news'))->name('news');
    Route::get('/profile',    fn() => view('parent.profile'))->name('profile');
});
```

`parent.events` (the route, not the in-page banner) is also removed since the event banner links to it in Task 6 — change that link target. In `resources/views/components/portal/event-banner.blade.php`, replace:

```blade
<a href="{{ route('parent.events') }}"
```

with a link that just dispatches the same kind of inline expand pattern used elsewhere, since there's no longer a dedicated events page. Simplest correct fix: make the banner non-interactive (no link), since event registration for the active child can be added as a future quick action if needed — that's out of this spec's scope. Replace the `<a>` wrapper with a `<div>`:

```blade
@props(['activeEvent'])

@if ($activeEvent)
    <div class="block mb-4 px-4 py-3 rounded-xl bg-navy text-off">
        <p class="text-sm font-semibold">{{ $activeEvent->name }}</p>
        <p class="text-xs opacity-80">{{ __('messages.portal.home.event_open') }}</p>
    </div>
@endif
```

And remove the now-unused `'event_cta'` key from both lang files (delete that one line from each).

- [ ] **Step 4: Rewrite the nav to 3 items**

Replace the entire contents of `resources/views/components/parent-nav.blade.php`:

```blade
@props(['activeRoute' => null])

@php
    $isActive = fn(string $route) => $activeRoute ? $activeRoute === $route : request()->routeIs($route);
@endphp

<x-sidebar-section :label="__('messages.section.overview')">
    <x-sidebar-link href="{{ route('parent.home') }}" :active="$isActive('parent.home') || $isActive('parent.dashboard')">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
        {{ __('messages.nav.dashboard') }}
    </x-sidebar-link>
    <x-sidebar-link href="{{ route('parent.news') }}" :active="$isActive('parent.news')">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m0 0h2a2 2 0 012 2v9a2 2 0 11-4 0V5a2 2 0 00-2-2H5"/>
        </svg>
        {{ __('messages.nav.news') }}
    </x-sidebar-link>
</x-sidebar-section>

<x-sidebar-section :label="__('messages.section.account')">
    <x-sidebar-link href="{{ route('parent.profile') }}" :active="$isActive('parent.profile')">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
        {{ __('messages.nav.profile') }}
    </x-sidebar-link>
</x-sidebar-section>
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test tests/Feature/Portal/HomeTest.php`
Expected: PASS — all tests in the file

- [ ] **Step 6: Commit**

```bash
git add resources/views/components/parent-nav.blade.php resources/views/components/portal/event-banner.blade.php routes/web.php lang/en/messages.php lang/id/messages.php tests/Feature/Portal/HomeTest.php
git commit -m "feat(portal): trim parent nav to Home/News/Profile, remove deprecated routes"
```

---

### Task 8: Clean up superseded tests and run full verification

**Files:**
- Modify: `tests/Feature/Portal/AttendanceTest.php` (or equivalent — verify exact filename with `ls tests/Feature/Portal/`)
- Modify: `tests/Feature/Portal/PaymentsTest.php`
- Modify: `tests/Feature/Portal/EventsTest.php`
- Remove tests asserting against now-deleted routes only; keep tests that exercise the underlying Livewire components directly (`Livewire::test(AttendanceHistory::class)` style assertions, which still work since those classes are kept, just not routed)

**Interfaces:**
- None — this task only adjusts test coverage to match the routes removed in Task 7

- [ ] **Step 1: List the existing portal tests to find route-coupled assertions**

Run: `grep -rln "route('parent\." tests/Feature/Portal/`

For every match found, open the file and check: does the test call `->get(route('parent.payments'))` (or `.attendance`, `.leaves`, `.makeup`, `.report-cards`, `.players`, `.events`, `.private`) directly? Those specific assertions will now fail with 404 since the routes are gone (Task 7) — delete only those specific test cases (not the whole file; tests using `Livewire::test(SomeComponent::class)` instead of `->get(route(...))` are unaffected and must stay).

- [ ] **Step 2: Run the full portal test suite to find what's actually broken**

Run: `php artisan test tests/Feature/Portal/`
Expected: some failures pointing at the route-coupled assertions found in Step 1 — fix each by deleting the specific failing assertion (the underlying behavior is already covered by `HomeTest.php` from Tasks 2–7, or by the component's own direct `Livewire::test()` coverage which is untouched)

- [ ] **Step 3: Run the entire test suite**

Run: `php artisan test`
Expected: PASS — 0 failures

- [ ] **Step 4: Build frontend assets**

Run: `npm run build`
Expected: build succeeds with no errors

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/Portal/
git commit -m "test(portal): remove assertions against routes deleted in the home page consolidation"
```

---

## Final check

- [ ] Run `php artisan test` one more time — full suite green
- [ ] Manually visit `/parent/home` as a parent user with at least 2 children, confirm: child switcher works, next session shows correct data, payment card expands, quick action modals open and the embedded wizards function (submit a leave request end-to-end)
- [ ] Confirm `/parent/dashboard` redirects to `/parent/home`
- [ ] Confirm old URLs (`/parent/payments`, etc.) return 404, not a blank page or error
