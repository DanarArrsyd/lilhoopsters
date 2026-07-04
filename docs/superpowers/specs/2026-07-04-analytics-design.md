# Analytics: LTV + Attendance Insight — Design

**Date:** 2026-07-04
**Pages:** `admin/players` (`app/Livewire/Admin/Players.php`), `admin/owner` (`app/Livewire/Admin/Owner.php`, "Owner Insights")

## Context

`Owner.php`/`owner.blade.php` and `Reports.php`/`reports.blade.php` already cover revenue, funnel, retention, churn, coach utilization, and class-fill analytics extensively. Owner Insights already has: a color-coded health strip + 30-day trend deltas + a money-ranked action center at the top (always visible), and the remaining raw-data sections are already split into 4 tabs (Members, Finance, Coaches, Pipeline) via Alpine.js (`resources/views/livewire/admin/owner.blade.php:110`) — so the "long unnavigable page" problem this session set out to fix is already solved. Two things are genuinely missing:

1. **LTV / payment history per child** — no detail view exists at all today; `admin/players` is list-only.
2. **Attendance rate by program & location** — no page shows this anywhere.

This spec covers both, plus a 5th Owner Insights tab to hold the new attendance data, built to match the existing visual language exactly (KPI card grid + traffic-light fill bars, per `owner.blade.php:277-333`) so it reads as native to the page rather than bolted on.

## 1. LTV per child

**Where:** `admin/players`, admin-only. New "View" action per row, opens a modal (same overlay pattern as `attendances.blade.php:207` — `fixed inset-0` backdrop + `relative bg-surface rounded-2xl border border-line shadow-xl`, sized `max-w-2xl` for this modal since it holds a table).

**Trigger:** new column or icon-button in the existing `players.blade.php` table (`@foreach` loop at line 40) — `wire:click="openLtv({{ $player->id }})"`.

**Component (`app/Livewire/Admin/Players.php`):**

```php
public bool $showLtv = false;
public ?int $ltvChildId = null;

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
```

`render()` additionally passes, only when `$this->showLtv`:

```php
'ltvChild'        => $this->ltvChildId ? Child::with('parent')->find($this->ltvChildId) : null,
'ltvTotal'        => $this->ltvChildId ? Transaction::where('child_id', $this->ltvChildId)->where('status', 'paid')->sum('amount') : 0,
'ltvTransactions' => $this->ltvChildId
    ? Transaction::where('child_id', $this->ltvChildId)->with('package')->latest('created_at')->get()
    : collect(),
```

**Modal content:**
- Header: child name + parent name.
- Total lifetime spend, large (same `text-xl font-extrabold text-navy` KPI-number treatment used elsewhere), computed from paid transactions only (`status = 'paid'`).
- Full transaction history table below it: Tanggal, Paket, Jumlah, Status (all statuses shown, not just paid — a parent's pending/cancelled attempts are useful context for an admin). Status uses the existing `<x-badge :status="...">` component already used on `attendances.blade.php:93` and elsewhere.
- Empty state: "No transactions yet" if the child has none.

## 2. Attendance analytics — new "Attendance" tab in Owner Insights

**Where:** `admin/owner`, as a 5th tab alongside the existing Members/Finance/Coaches/Pipeline (`owner.blade.php:110-149` — add one more `<button @click="tab='attendance'">` following the exact same markup/icon-button pattern as the other four).

**Metric:** `present / (present + no_show + sick + permit)` as a percentage, over the trailing 30 days (matching the window already used for `leaderboardData()` in `Owner.php:486` and the trend deltas in `insightsData()`, for consistency with the rest of the page). `make_up` attendances are excluded from both numerator and denominator (a make-up class is a reschedule of an absence already counted, not a fresh attendance event).

**New private method in `Owner.php`, `attendanceData(): array`:**

```php
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
        'overall'      => $overall,
        'present'      => $rows->where('status', 'present')->count(),
        'total'        => $rows->count(),
        'by_program'   => $byProgram,
        'by_location'  => $byLocation,
    ];
}
```

Wired into `render()` alongside the other data-builder calls, passed to the view as `attendance`.

**Layout (mirrors the Members tab's Class Utilization section exactly, `owner.blade.php:271-333`):**

1. KPI card row (`grid grid-cols-2 lg:grid-cols-3 gap-3`, same card markup as capacity's 3 cards): "Overall Attendance Rate" (big %, sub-text "`{present}/{total} sessions`"), "Present" (count), "Absent/Sick/Permit" (total minus present).
2. "By Program" breakdown: same fill-bar list pattern as the capacity schedule list (`owner.blade.php:308-330`) — one row per program, a horizontal bar showing the rate, right-aligned `{rate}%` and `({total} sessions)`. Traffic-light bar color: green (`bg-[#15803D]`) at ≥80%, amber (`bg-[#B45309]`) at 50–79%, red (`bg-[#B91C1C]`) below 50% — thresholds chosen to match attendance-specific expectations (unlike class-fill, where ≥50% is already "good"; showing up is expected to be the norm, so the bar is stricter).
3. "By Location" breakdown: identical list treatment, one row per location.
4. Empty state ("No attendance records in the last 30 days") if `total === 0`, matching the style of existing empty states on the page (e.g. `owner.blade.php:306`).

This tab intentionally reuses the page's existing KPI-card and fill-bar components rather than introducing a new visual pattern — the design goal (easy to read and act on for admins/owners) is served by consistency with the tabs already on the page, not by novelty.

## Out of scope

- No date-range picker for the Attendance tab — fixed 30-day trailing window, matching the rest of Owner Insights' trend calculations (no section on this page has a user-adjustable range; `Reports.php` is the page with date-range filtering, and stays that way).
- No drill-down from a program/location row into individual attendance records — that already exists on `admin/attendances` (with its own filters, per the just-shipped date-range + export feature).
- Owner Insights tabify: already implemented (`owner.blade.php:110`), no work needed.
- No changes to `Reports.php`/`reports.blade.php` — confirmed already well-structured (tabs, KPI deltas, breakdowns) for this iteration.
