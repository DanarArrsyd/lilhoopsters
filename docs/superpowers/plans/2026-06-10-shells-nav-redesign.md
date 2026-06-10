# Shells + Navigation Redesign — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development. Steps use checkbox (`- [ ]`) syntax.

**Goal:** Convert the four app layout shells (`admin`, `coach`, `parent-portal`, `superadmin`) and the shared sidebar components to the light two-tone theme (off-white/white sidebar with **navy active nav item**), regroup the admin navigation into the five new sections, and translate coach nav labels to English.

**Architecture:** Presentation-only. Each shell keeps its public contract — the `title`, `navigation`, and `actions` slots plus `{{ $slot }}` — because every page uses `<x-{role} title=...><x-slot name="navigation"><x-{role}-nav/></x-slot>...</x-{role}>`. Route names and `route('logout')` are unchanged. No backend changes; 244 tests stay green.

**Tech Stack:** Laravel 12, Livewire 3, Tailwind CSS 4. PHP CLI `/opt/homebrew/bin/php`. Verify with `npm run build`.

**Tokens:** navy `#0A0F1E`, off `#FAF9F6`, surface `#FFFFFF`, line `#E6E3DC`, ink/muted/faint. Reference: `docs/superpowers/specs/2026-06-10-frontend-redesign-design.md` §2.

**Scope note:** Parent-portal mobile bottom-nav is deferred to the consolidated portal page milestone (it needs route-specific items); this task gives the portal the same drawer pattern as the others. Files merge is NOT in scope — keep the four shells as separate files.

---

### Task 1: `sidebar-link` component (light theme)

**Files:** Modify: `resources/views/components/sidebar-link.blade.php` (replace entire file)

- [ ] **Step 1: Replace with:**

```blade
@props(['href', 'active' => false, 'badge' => null])

@php
$path = ltrim(parse_url($href, PHP_URL_PATH), '/');
$isActive = $active || request()->is($path) || request()->is($path . '/*');
@endphp

<a href="{{ $href }}"
   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors
          {{ $isActive
              ? 'bg-navy text-off font-semibold'
              : 'text-muted hover:bg-off hover:text-navy' }}">
    {{ $slot }}
    @if ($badge)
        <span class="ml-auto bg-[#DC2626] text-off text-[10px] font-bold rounded-full px-1.5 py-0.5 min-w-[18px] text-center">
            {{ $badge }}
        </span>
    @endif
</a>
```

- [ ] **Step 2:** `npm run build` (must succeed).

---

### Task 2: `sidebar-section` component (faint label)

**Files:** Modify: `resources/views/components/sidebar-section.blade.php` (replace entire file)

- [ ] **Step 1: Replace with:**

```blade
@props(['label'])

<div class="mt-5 mb-1 px-3">
    <p class="text-[10px] font-bold uppercase tracking-widest text-faint">{{ $label }}</p>
</div>
{{ $slot }}
```

- [ ] **Step 2:** `npm run build` (must succeed).

---

### Task 3: The four layout shells (light)

All four shells are **identical** except for one line — the panel subtitle under the logo. Apply this exact template to each file, changing ONLY the subtitle text:

- `resources/views/components/admin.blade.php` → subtitle `Admin Panel`, default title `Admin`
- `resources/views/components/coach.blade.php` → subtitle `Coach Panel`, default title `Coach`
- `resources/views/components/parent-portal.blade.php` → subtitle `Parent Portal`, default title `Parent`
- `resources/views/components/superadmin.blade.php` → subtitle `Super Admin`, default title `Super Admin`

**Template** (shown for `admin`; replace the two CAPS placeholders per the list above):

```blade
<x-app>
    <x-slot name="title">{{ $title ?? 'Admin' }}</x-slot>

    <div class="flex min-h-[100dvh]">
        {{-- Sidebar --}}
        <aside id="sidebar"
               class="fixed inset-y-0 left-0 z-50 w-60 bg-surface border-r border-line flex flex-col
                      transform -translate-x-full lg:translate-x-0 transition-transform duration-300">

            {{-- Logo --}}
            <div class="h-16 flex items-center gap-3 px-4 border-b border-line">
                <div class="w-9 h-9 bg-navy rounded-xl flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-off" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="8" fill="none" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </div>
                <div>
                    <p class="text-navy font-extrabold text-sm uppercase tracking-tight leading-tight">BasketManage</p>
                    <p class="text-faint text-[10px] uppercase tracking-wide">Admin Panel</p>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                {{ $navigation ?? '' }}
            </nav>

            {{-- User --}}
            <div class="border-t border-line p-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-navy rounded-full flex items-center justify-center text-off font-bold text-sm shrink-0">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-ink text-sm font-semibold truncate">{{ auth()->user()->name }}</p>
                        <p class="text-muted text-xs truncate">{{ auth()->user()->email }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-muted hover:text-[#B91C1C] transition-colors p-1" title="Sign out">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Overlay mobile --}}
        <div id="sidebar-overlay" class="fixed inset-0 bg-navy/40 z-40 hidden lg:hidden" onclick="closeSidebar()"></div>

        {{-- Main --}}
        <div class="flex-1 flex flex-col min-w-0 lg:ml-60">
            {{-- Topbar --}}
            <header class="h-14 bg-surface border-b border-line flex items-center px-4 gap-4 sticky top-0 z-30">
                <button class="lg:hidden p-2 rounded-lg hover:bg-off text-muted" onclick="openSidebar()">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <div class="flex-1">
                    <h1 class="text-sm font-bold uppercase tracking-wide text-navy">{{ $title ?? 'Dashboard' }}</h1>
                </div>
                {{ $actions ?? '' }}
            </header>

            {{-- Content --}}
            <main class="flex-1 bg-off p-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    @push('scripts')
    <script>
        function openSidebar() {
            document.getElementById('sidebar').classList.remove('-translate-x-full');
            document.getElementById('sidebar-overlay').classList.remove('hidden');
        }
        function closeSidebar() {
            document.getElementById('sidebar').classList.add('-translate-x-full');
            document.getElementById('sidebar-overlay').classList.add('hidden');
        }
    </script>
    @endpush
</x-app>
```

Per-file changes from the template above:
- **admin.blade.php:** `{{ $title ?? 'Admin' }}` (both occurrences), subtitle `Admin Panel`.
- **coach.blade.php:** `{{ $title ?? 'Coach' }}` (both occurrences), subtitle `Coach Panel`.
- **parent-portal.blade.php:** `{{ $title ?? 'Parent' }}` (both occurrences), subtitle `Parent Portal`.
- **superadmin.blade.php:** `{{ $title ?? 'Super Admin' }}` (both occurrences), subtitle `Super Admin`.

(The two `$title` occurrences are the `<x-slot name="title">` line and the `<h1>` topbar line.)

- [ ] **Step 1:** Apply the template to all four files with the per-file substitutions.
- [ ] **Step 2:** `npm run build` (must succeed).

---

### Task 4: Regroup admin navigation

Reorganize `admin-nav.blade.php` into the five new sections, reusing the existing icons. All routes already exist. Replace the entire file.

**Files:** Modify: `resources/views/components/admin-nav.blade.php` (replace entire file)

- [ ] **Step 1: Replace with:**

```blade
<x-sidebar-link href="{{ route('admin.dashboard') }}" :active="request()->routeIs('admin.dashboard')">
    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    Dashboard
</x-sidebar-link>

<x-sidebar-section label="People">
    <x-sidebar-link href="{{ route('admin.parents') }}" :active="request()->routeIs('admin.parents')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        Parents
    </x-sidebar-link>
    <x-sidebar-link href="{{ route('admin.players') }}" :active="request()->routeIs('admin.players')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2" fill="none"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4c0 0-4 3-4 8s4 8 4 8"/>
        </svg>
        Players
    </x-sidebar-link>
    <x-sidebar-link href="{{ route('admin.coaches') }}" :active="request()->routeIs('admin.coaches')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
        Coaches
    </x-sidebar-link>
</x-sidebar-section>

<x-sidebar-section label="Programs">
    <x-sidebar-link href="{{ route('admin.locations') }}" :active="request()->routeIs('admin.locations')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        Locations
    </x-sidebar-link>
    <x-sidebar-link href="{{ route('admin.programs') }}" :active="request()->routeIs('admin.programs')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        Programs
    </x-sidebar-link>
    <x-sidebar-link href="{{ route('admin.packages') }}" :active="request()->routeIs('admin.packages')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
        </svg>
        Packages
    </x-sidebar-link>
    <x-sidebar-link href="{{ route('admin.schedules') }}" :active="request()->routeIs('admin.schedules')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        Schedules
    </x-sidebar-link>
</x-sidebar-section>

<x-sidebar-section label="Operations">
    <x-sidebar-link href="{{ route('admin.attendances') }}" :active="request()->routeIs('admin.attendances')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
        </svg>
        Attendances
    </x-sidebar-link>
    <x-sidebar-link href="{{ route('admin.leave-requests') }}" :active="request()->routeIs('admin.leave-requests')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        Leave Requests
    </x-sidebar-link>
    <x-sidebar-link href="{{ route('admin.makeup-classes') }}" :active="request()->routeIs('admin.makeup-classes')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        Make-Up Classes
    </x-sidebar-link>
    <x-sidebar-link href="{{ route('admin.enrollments') }}" :active="request()->routeIs('admin.enrollments')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Enrollments
    </x-sidebar-link>
</x-sidebar-section>

<x-sidebar-section label="Finance">
    <x-sidebar-link href="{{ route('admin.payments') }}" :active="request()->routeIs('admin.payments')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
        Payments
    </x-sidebar-link>
</x-sidebar-section>

<x-sidebar-section label="Reports">
    <x-sidebar-link href="{{ route('admin.report-cards') }}" :active="request()->routeIs('admin.report-cards')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        Report Cards
    </x-sidebar-link>
</x-sidebar-section>
```

- [ ] **Step 2:** `npm run build` (must succeed).

---

### Task 5: Coach nav — English labels

Translate the Indonesian section/labels. Replace the entire file.

**Files:** Modify: `resources/views/components/coach-nav.blade.php` (replace entire file)

- [ ] **Step 1: Replace with:**

```blade
<x-sidebar-link href="{{ route('coach.dashboard') }}" :active="request()->routeIs('coach.dashboard')">
    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    Dashboard
</x-sidebar-link>

<x-sidebar-section label="Attendance">
    <x-sidebar-link href="{{ route('coach.qr-scanner') }}" :active="request()->routeIs('coach.qr-scanner')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
        </svg>
        QR Scanner
    </x-sidebar-link>
    <x-sidebar-link href="{{ route('coach.checkin') }}" :active="request()->routeIs('coach.checkin')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        Check-In
    </x-sidebar-link>
    <x-sidebar-link href="{{ route('coach.roster') }}" :active="request()->routeIs('coach.roster')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        Daily Roster
    </x-sidebar-link>
</x-sidebar-section>

<x-sidebar-section label="Sessions">
    <x-sidebar-link href="{{ route('coach.schedules') }}" :active="request()->routeIs('coach.schedules')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        My Schedules
    </x-sidebar-link>
    <x-sidebar-link href="{{ route('coach.attendance') }}" :active="request()->routeIs('coach.attendance')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Take Attendance
    </x-sidebar-link>
    <x-sidebar-link href="{{ route('coach.report-cards') }}" :active="request()->routeIs('coach.report-cards')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        Report Cards
    </x-sidebar-link>
</x-sidebar-section>
```

- [ ] **Step 2:** `npm run build` (must succeed).

---

### Task 6: Verify & commit

- [ ] **Step 1:** `npm run build` (success).
- [ ] **Step 2:** `/opt/homebrew/bin/php artisan test` — all 244 pass.
- [ ] **Step 3:** Commit:

```bash
git add resources/views/components/sidebar-link.blade.php resources/views/components/sidebar-section.blade.php resources/views/components/admin.blade.php resources/views/components/coach.blade.php resources/views/components/parent-portal.blade.php resources/views/components/superadmin.blade.php resources/views/components/admin-nav.blade.php resources/views/components/coach-nav.blade.php
git commit -m "feat(design): light shells + off-white sidebar (navy active), grouped admin nav, English coach nav

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

- [ ] **Step 4 (manual, optional):** render `/admin/dashboard`, `/coach/dashboard`, `/parent/dashboard`, `/superadmin/dashboard`. Confirm: off-white sidebar, navy active item highlights the current page, no dark-navy sidebar, mobile hamburger opens the drawer, no horizontal scroll at 375px.
