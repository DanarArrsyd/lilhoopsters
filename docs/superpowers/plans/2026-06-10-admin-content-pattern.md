# Admin Content Pattern (Dashboard + Players + shared content components) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development. Steps use checkbox (`- [ ]`) syntax.

**Goal:** Establish the reusable content patterns for all internal list/CRUD pages by redesigning the shared content components (`x-card`, `x-badge`, `x-empty-state`, new `x-select`) and applying them to the Admin **Dashboard** and **Players** pages. These two pages become the reference the remaining 12 admin pages copy.

**Architecture:** Presentation-only. Livewire component PHP (properties `search`, `filterStatus`, pagination, `$stats`) is unchanged; only the Blade templates and shared `x-` components change. 244 tests stay green (no test asserts color classes or the empty-state default text — verified).

**Responsive decision:** Admin/coach data tables scroll horizontally inside their card on small screens (`overflow-x-auto`); the page itself never scrolls horizontally. Full card-stacking is reserved for the consumer-facing Parent portal. This keeps the 14 admin/coach list pages maintainable.

**Tech Stack:** Laravel 12, Livewire 3, Tailwind CSS 4. PHP CLI `/opt/homebrew/bin/php`. Verify with `npm run build`.

**Tokens:** navy, off, surface, line, ink, muted, faint + status (#15803D success / #B45309 warning / #B91C1C danger / #1D4ED8 info). Two-tone: neutral chips/icons use navy tints; only true status uses status colors.

---

### Task 1: `x-card` (tokens)

**Files:** Modify: `resources/views/components/card.blade.php` (replace entire file)

- [ ] **Step 1: Replace with:**

```blade
@props(['title' => null, 'padding' => 'p-6'])

<div {{ $attributes->merge(['class' => 'bg-surface rounded-2xl border border-line shadow-sm']) }}>
    @if ($title)
        <div class="flex items-center justify-between px-6 py-4 border-b border-line">
            <h3 class="text-sm font-bold uppercase tracking-wide text-navy">{{ $title }}</h3>
            {{ $action ?? '' }}
        </div>
    @endif
    <div class="{{ $padding }}">
        {{ $slot }}
    </div>
</div>
```

- [ ] **Step 2:** `npm run build`.

---

### Task 2: `x-badge` (two-tone + status tokens)

**Files:** Modify: `resources/views/components/badge.blade.php` (replace entire file)

- [ ] **Step 1: Replace with:**

```blade
@props(['status'])

@php
$classes = match($status) {
    'approved', 'active', 'present', 'paid', 'published', 'auto_approved' => 'bg-[#15803D]/10 text-[#15803D]',
    'pending', 'warning'                                  => 'bg-[#B45309]/10 text-[#B45309]',
    'rejected', 'inactive', 'no_show', 'danger'           => 'bg-[#B91C1C]/10 text-[#B91C1C]',
    'sick', 'info', 'permit'                              => 'bg-[#1D4ED8]/10 text-[#1D4ED8]',
    'make_up'                                             => 'bg-navy/10 text-navy',
    'draft', 'expired', 'unregistered'                    => 'bg-navy/5 text-muted',
    default                                               => 'bg-navy/5 text-muted',
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide $classes"]) }}>
    {{ $slot }}
</span>
```

- [ ] **Step 2:** `npm run build`.

---

### Task 3: `x-empty-state` (tokens + English default)

**Files:** Modify: `resources/views/components/empty-state.blade.php` (replace entire file)

- [ ] **Step 1: Replace with:**

```blade
@props(['title' => 'No data yet', 'description' => null])

<div class="text-center py-16">
    <div class="w-12 h-12 bg-navy/5 rounded-2xl flex items-center justify-center mx-auto mb-4">
        <svg class="w-6 h-6 text-faint" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
        </svg>
    </div>
    <p class="text-sm font-bold text-ink">{{ $title }}</p>
    @if ($description)
        <p class="text-xs text-muted mt-1">{{ $description }}</p>
    @endif
    {{ $slot }}
</div>
```

- [ ] **Step 2:** `npm run build`.

---

### Task 4: New `x-select` component (reusable styled select)

Establishes a consistent select to replace the raw orange-focused `<select>` across all pages. Supports `wire:model`/options via slot.

**Files:** Create: `resources/views/components/select.blade.php`

- [ ] **Step 1: Create with:**

```blade
@props(['label' => null, 'error' => null])

@php
    $fieldId = $attributes->get('id') ?? $attributes->get('name');
@endphp

<div class="space-y-1.5">
    @if ($label)
        <label @if($fieldId) for="{{ $fieldId }}" @endif class="block text-xs font-semibold uppercase tracking-wide text-navy">
            {{ $label }}
        </label>
    @endif

    <select {{ $attributes->merge([
        'id'    => $fieldId,
        'class' => 'block w-full rounded-xl px-3.5 py-3 text-sm bg-surface text-ink border border-line
                    focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy
                    ' . ($error ? 'border-[#DC2626] focus:ring-[#DC2626]/20 focus:border-[#DC2626]' : ''),
    ]) }}>
        {{ $slot }}
    </select>

    @if ($error)
        <p class="text-xs text-[#B91C1C]">{{ $error }}</p>
    @endif
</div>
```

- [ ] **Step 2:** `npm run build`.

---

### Task 5: Admin Dashboard view (`resources/views/livewire/admin/dashboard.blade.php`)

Convert amber/green/blue/orange to two-tone + warning. Keep all `$stats[...]` keys and `route(...)` links exactly. "Action Required" cards turn warning when count > 0, neutral otherwise. "Overview" cards use navy icon tints.

**Files:** Modify: `resources/views/livewire/admin/dashboard.blade.php` (replace entire file)

- [ ] **Step 1: Replace with:**

```blade
<div>
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Dashboard</h2>
        <p class="text-sm text-muted">Overview of academy activity</p>
    </div>

    {{-- Action required --}}
    <h3 class="text-[10px] font-bold uppercase tracking-widest text-faint mb-3">Action Required</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        @php
            $actionCards = [
                ['route' => 'admin.parents',     'label' => 'Pending Registrations', 'count' => $stats['pending_registrations'], 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                ['route' => 'admin.enrollments', 'label' => 'Pending Enrollments',   'count' => $stats['pending_enrollments'],   'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['route' => 'admin.payments',    'label' => 'Pending Payments',      'count' => $stats['pending_payments'],       'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
            ];
        @endphp
        @foreach ($actionCards as $c)
            <a href="{{ route($c['route']) }}" class="block">
                <x-card padding="p-5" class="hover:shadow-md transition-shadow cursor-pointer border-l-4 {{ $c['count'] > 0 ? 'border-l-[#B45309]' : 'border-l-line' }}">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-muted uppercase tracking-wide">{{ $c['label'] }}</p>
                            <p class="text-3xl font-extrabold text-navy mt-1">{{ $c['count'] }}</p>
                        </div>
                        <div class="w-10 h-10 rounded-xl {{ $c['count'] > 0 ? 'bg-[#B45309]/10' : 'bg-navy/5' }} flex items-center justify-center">
                            <svg class="w-5 h-5 {{ $c['count'] > 0 ? 'text-[#B45309]' : 'text-faint' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $c['icon'] }}"/>
                            </svg>
                        </div>
                    </div>
                    @if ($c['count'] > 0)
                        <p class="text-xs text-[#B45309] mt-2 font-semibold">Needs review →</p>
                    @endif
                </x-card>
            </a>
        @endforeach
    </div>

    {{-- Overview --}}
    <h3 class="text-[10px] font-bold uppercase tracking-widest text-faint mb-3">Overview</h3>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        @php
            $overviewCards = [
                ['route' => 'admin.players',   'label' => 'Active Players',   'count' => $stats['active_players'],   'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                ['route' => 'admin.locations', 'label' => 'Active Locations', 'count' => $stats['active_locations'], 'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z'],
                ['route' => 'admin.coaches',   'label' => 'Active Coaches',   'count' => $stats['active_coaches'],   'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
            ];
        @endphp
        @foreach ($overviewCards as $c)
            <a href="{{ route($c['route']) }}" class="block">
                <x-card padding="p-5" class="hover:shadow-md transition-shadow cursor-pointer">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-muted uppercase tracking-wide">{{ $c['label'] }}</p>
                            <p class="text-3xl font-extrabold text-navy mt-1">{{ $c['count'] }}</p>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-navy/8 flex items-center justify-center">
                            <svg class="w-5 h-5 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $c['icon'] }}"/>
                            </svg>
                        </div>
                    </div>
                </x-card>
            </a>
        @endforeach
    </div>
</div>
```

- [ ] **Step 2:** `npm run build`.

---

### Task 6: Admin Players view (`resources/views/livewire/admin/players.blade.php`)

Convert to tokens; replace raw `<select>` with `<x-select>`; navy avatars; responsive table (`overflow-x-auto`). Keep `wire:model.live.debounce.300ms="search"`, `wire:model.live="filterStatus"`, `$players` pagination, `ageInMonths()`, `<x-badge>`.

**Files:** Modify: `resources/views/livewire/admin/players.blade.php` (replace entire file)

- [ ] **Step 1: Replace with:**

```blade
<div>
    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Players</h2>
        <p class="text-sm text-muted">All registered players in the academy</p>
    </div>

    {{-- Filters --}}
    <x-card class="mb-4" padding="p-4">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <x-input wire:model.live.debounce.300ms="search" placeholder="Search by player or parent name..." />
            </div>
            <div class="w-full sm:w-48">
                <x-select wire:model.live="filterStatus">
                    <option value="">All Statuses</option>
                    <option value="unregistered">Unregistered</option>
                    <option value="pending">Pending</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </x-select>
            </div>
        </div>
    </x-card>

    {{-- Table --}}
    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[640px]">
                <thead>
                    <tr class="border-b border-line">
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Player</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Parent</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Age</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Gender</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Jersey</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($players as $player)
                        <tr class="hover:bg-off transition-colors">
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold shrink-0 bg-navy/8 text-navy">
                                        {{ strtoupper(substr($player->name, 0, 1)) }}
                                    </div>
                                    <p class="font-semibold text-ink">{{ $player->name }}</p>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-muted">{{ $player->parent->name }}</td>
                            <td class="py-3 px-4 text-ink">
                                @php $months = $player->ageInMonths(); @endphp
                                @if ($months >= 12)
                                    {{ floor($months / 12) }}yr {{ $months % 12 > 0 ? ($months % 12) . 'mo' : '' }}
                                @else
                                    {{ $months }}mo
                                @endif
                            </td>
                            <td class="py-3 px-4 text-muted">{{ ucfirst($player->gender) }}</td>
                            <td class="py-3 px-4 text-muted text-xs">
                                @if ($player->jersey_name)
                                    <span class="font-semibold text-ink">{{ $player->jersey_name }}</span>
                                    @if ($player->jersey_number)
                                        <span class="ml-1 text-faint">#{{ $player->jersey_number }}</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <x-badge :status="$player->status">{{ ucfirst($player->status) }}</x-badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-2">
                                <x-empty-state title="No players found" description="No players match your search." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($players->hasPages())
            <div class="px-4 py-3 border-t border-line">
                {{ $players->links() }}
            </div>
        @endif
    </x-card>
</div>
```

- [ ] **Step 2:** `npm run build`.

---

### Task 7: Verify & commit

- [ ] **Step 1:** `npm run build` (success).
- [ ] **Step 2:** `/opt/homebrew/bin/php artisan test` — all 244 pass.
- [ ] **Step 3:** Commit:

```bash
git add resources/views/components/card.blade.php resources/views/components/badge.blade.php resources/views/components/empty-state.blade.php resources/views/components/select.blade.php resources/views/livewire/admin/dashboard.blade.php resources/views/livewire/admin/players.blade.php
git commit -m "feat(design): admin content pattern — card/badge/empty-state/select + dashboard + players

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

- [ ] **Step 4 (manual, optional):** as admin, view `/admin/dashboard` and `/admin/players`. Confirm two-tone styling, navy stat numbers, warning border on non-zero action cards, working search/filter, responsive table scroll at 375px, no orange/slate.
