# Admin/Superadmin Top-Nav Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the admin and superadmin sidebar shells with the parent portal's horizontal top-nav + avatar-menu theme (navy/off-white, dropdown groups on desktop, full-screen drawer on mobile) so all three roles share one visual language.

**Architecture:** Both `components/admin.blade.php` and `components/superadmin.blade.php` are single Blade shell components every page renders through — swap their markup once and all 22 pages (19 admin + 3 superadmin) inherit the new chrome automatically, with zero per-page edits. Desktop nav becomes a horizontal bar (logo left, nav center, locale/bell/avatar right) matching `<x-portal.top-nav>`'s `grid-cols-[1fr_auto_1fr]` header pattern; admin's 5 route groups (People/Programs/Operations/Finance/Reports) become click-triggered dropdowns, superadmin's 2 routes stay flat links. Mobile collapses to logo+hamburger+avatar, with a full-screen drawer (not a persistent sidebar) reusing the existing `<x-sidebar-section>`/`<x-sidebar-link>` accordion content verbatim.

**Tech Stack:** Laravel 11 Blade components, Alpine.js (`x-data`, `@click.outside`, `x-show`, `x-transition`), Tailwind v4, existing `AdminNavComposer` view-composer for badge counts.

## Global Constraints

- Design tokens are locked: navy `#1A2F5E`, off-white `#F4F7FC`, surface `#FFFFFF`, line `#DDE4F0` — reuse existing Tailwind utility classes (`bg-navy`, `bg-off`, `bg-surface`, `border-line`, `text-navy`, `text-muted`, `text-ink`) already used throughout the codebase. Do not introduce new colors.
- No new automated tests planned per the spec — the existing `assertOk()`/content-assertion tests per admin/superadmin page plus `tests/Feature/I18nTest.php` are the regression safety net. Each task ends with `php artisan test` (relevant subset) + a manual browser check, not new PHPUnit/Pest tests.
- `npm run build` must be run after every Blade/CSS edit (XAMPP serves pre-built assets).
- The shell's `$title` prop only feeds `<title>` in `components/app.blade.php:7` (browser tab) — it is NOT the source of any on-page heading text (verified: every admin page's visible heading comes from an in-content `<h2>` inside its own Livewire view, e.g. `messages.admin.dashboard.title` → "Dasbor" in `tests/Feature/I18nTest.php`). The new shell header does not need to render `$title` as visible text.
- Reuse existing lang keys verbatim — no new translation keys needed. Dropdown/drawer group labels reuse `messages.admin.section.*` (people/programs/operations/finance/reports/account), avatar-menu items reuse `messages.admin.nav.profile` ("Profile Settings") and `messages.admin.sign_out` ("Sign out").
- `AdminNavComposer` (`app/View/Composers/AdminNavComposer.php`) is bound in `app/Providers/AppServiceProvider.php:26` to the exact view name `components.admin-nav`. Any new view that needs `$navBadges` must get its own composer binding line, or reuse the same view path.

---

### Task 1: Admin desktop dropdown nav

**Files:**
- Create: `resources/views/components/nav-dropdown.blade.php`
- Create: `resources/views/components/admin-nav-desktop.blade.php`
- Modify: `app/Providers/AppServiceProvider.php:26` (add composer binding)

**Interfaces:**
- Produces: `<x-nav-dropdown label="...">...</x-nav-dropdown>` — generic click-triggered dropdown, Alpine `x-data="{ open: false }"`, `@click.outside="open = false"`. Slot renders the dropdown panel content (a list of `<x-sidebar-link>` items).
- Produces: `<x-admin-nav-desktop />` — full desktop nav row (Dashboard link + 5 `<x-nav-dropdown>` groups), consumed by Task 2's `admin.blade.php` rewrite.
- Consumes: `$navBadges` array (keys: `parents`, `enrollments`, `payments`, `leave_requests`, `makeup_classes`, `report_cards`) — auto-injected by `AdminNavComposer` once bound to `components.admin-nav-desktop`.
- Consumes: `<x-sidebar-link>` (existing, `resources/views/components/sidebar-link.blade.php`) — unchanged, reused as-is for dropdown panel items.

- [ ] **Step 1: Read the existing admin-nav for exact hrefs/labels/badges**

Already captured below from `resources/views/components/admin-nav.blade.php` (do not modify this file in this task — it stays as mobile-drawer content until Task 2).

- [ ] **Step 2: Create the generic dropdown component**

`resources/views/components/nav-dropdown.blade.php`:
```blade
@props(['label'])

<div x-data="{ open: false }" @click.outside="open = false" class="relative">
    <button @click="open = !open" type="button"
            class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold transition-colors
                   {{ $attributes->get('data-has-active') === 'true' ? 'bg-navy/8 text-navy' : 'text-muted hover:text-navy hover:bg-off' }}">
        {{ $label }}
        <svg class="w-3.5 h-3.5 transition-transform duration-150" :class="open ? 'rotate-180' : ''"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div x-show="open" x-cloak x-transition.opacity.duration.150ms
         class="absolute left-0 mt-2 w-56 bg-surface border border-line rounded-xl shadow-lg py-1.5 z-40">
        {{ $slot }}
    </div>
</div>
```

- [ ] **Step 3: Create the desktop nav content**

`resources/views/components/admin-nav-desktop.blade.php` (labels/hrefs/badges copied from the existing `admin-nav.blade.php` sections, grouped per the approved design — People includes Members Import, Reports includes Report Cards + News):
```blade
@php
    $isActive = fn(string ...$routes) => collect($routes)->contains(fn($r) => request()->routeIs($r));
@endphp

<nav class="hidden lg:flex items-center gap-1">
    <a href="{{ route('admin.dashboard') }}"
       class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold transition-colors {{ $isActive('admin.dashboard') ? 'bg-navy/8 text-navy' : 'text-muted hover:text-navy hover:bg-off' }}">
        {{ __('messages.admin.nav.dashboard') }}
    </a>

    <x-nav-dropdown :label="__('messages.admin.section.people')" data-has-active="{{ $isActive('admin.parents','admin.players','admin.leads','admin.coaches','admin.members-import') ? 'true' : 'false' }}">
        <x-sidebar-link href="{{ route('admin.parents') }}" :active="request()->routeIs('admin.parents')" :badge="$navBadges['parents'] ?: null">{{ __('messages.admin.nav.parents') }}</x-sidebar-link>
        <x-sidebar-link href="{{ route('admin.players') }}" :active="request()->routeIs('admin.players')">{{ __('messages.admin.nav.players') }}</x-sidebar-link>
        <x-sidebar-link href="{{ route('admin.leads') }}" :active="request()->routeIs('admin.leads')">{{ __('messages.admin.nav.leads') }}</x-sidebar-link>
        <x-sidebar-link href="{{ route('admin.coaches') }}" :active="request()->routeIs('admin.coaches')">{{ __('messages.admin.nav.coaches') }}</x-sidebar-link>
        <x-sidebar-link href="{{ route('admin.members-import') }}" :active="request()->routeIs('admin.members-import')">{{ __('messages.admin.nav.import_members') }}</x-sidebar-link>
    </x-nav-dropdown>

    <x-nav-dropdown :label="__('messages.admin.section.programs')" data-has-active="{{ $isActive('admin.locations','admin.programs','admin.packages','admin.schedules','admin.events') ? 'true' : 'false' }}">
        <x-sidebar-link href="{{ route('admin.locations') }}" :active="request()->routeIs('admin.locations')">{{ __('messages.admin.nav.locations') }}</x-sidebar-link>
        <x-sidebar-link href="{{ route('admin.programs') }}" :active="request()->routeIs('admin.programs')">{{ __('messages.admin.nav.programs') }}</x-sidebar-link>
        <x-sidebar-link href="{{ route('admin.packages') }}" :active="request()->routeIs('admin.packages')">{{ __('messages.admin.nav.packages') }}</x-sidebar-link>
        <x-sidebar-link href="{{ route('admin.schedules') }}" :active="request()->routeIs('admin.schedules')">{{ __('messages.admin.nav.schedules') }}</x-sidebar-link>
        <x-sidebar-link href="{{ route('admin.events') }}" :active="request()->routeIs('admin.events')">{{ __('messages.admin.nav.events') }}</x-sidebar-link>
    </x-nav-dropdown>

    <x-nav-dropdown :label="__('messages.admin.section.operations')" data-has-active="{{ $isActive('admin.attendances','admin.leave-requests','admin.makeup-classes','admin.enrollments') ? 'true' : 'false' }}">
        <x-sidebar-link href="{{ route('admin.attendances') }}" :active="request()->routeIs('admin.attendances')">{{ __('messages.admin.nav.attendances') }}</x-sidebar-link>
        <x-sidebar-link href="{{ route('admin.leave-requests') }}" :active="request()->routeIs('admin.leave-requests')" :badge="$navBadges['leave_requests'] ?: null">{{ __('messages.admin.nav.leave_requests') }}</x-sidebar-link>
        <x-sidebar-link href="{{ route('admin.makeup-classes') }}" :active="request()->routeIs('admin.makeup-classes')" :badge="$navBadges['makeup_classes'] ?: null">{{ __('messages.admin.nav.makeup_classes') }}</x-sidebar-link>
        <x-sidebar-link href="{{ route('admin.enrollments') }}" :active="request()->routeIs('admin.enrollments')" :badge="$navBadges['enrollments'] ?: null">{{ __('messages.admin.nav.enrollments') }}</x-sidebar-link>
    </x-nav-dropdown>

    <x-nav-dropdown :label="__('messages.admin.section.finance')" data-has-active="{{ $isActive('admin.payments','admin.reports','admin.owner') ? 'true' : 'false' }}">
        <x-sidebar-link href="{{ route('admin.payments') }}" :active="request()->routeIs('admin.payments')" :badge="$navBadges['payments'] ?: null">{{ __('messages.admin.nav.payments') }}</x-sidebar-link>
        <x-sidebar-link href="{{ route('admin.reports') }}" :active="request()->routeIs('admin.reports')">{{ __('messages.admin.nav.reports') }}</x-sidebar-link>
        <x-sidebar-link href="{{ route('admin.owner') }}" :active="request()->routeIs('admin.owner')">{{ __('messages.admin.nav.owner_insights') }}</x-sidebar-link>
    </x-nav-dropdown>

    <x-nav-dropdown :label="__('messages.admin.section.reports')" data-has-active="{{ $isActive('admin.report-cards','admin.news') ? 'true' : 'false' }}">
        <x-sidebar-link href="{{ route('admin.report-cards') }}" :active="request()->routeIs('admin.report-cards')" :badge="$navBadges['report_cards'] ?: null">{{ __('messages.admin.nav.report_cards') }}</x-sidebar-link>
        <x-sidebar-link href="{{ route('admin.news') }}" :active="request()->routeIs('admin.news')">{{ __('messages.admin.nav.news') }}</x-sidebar-link>
    </x-nav-dropdown>
</nav>
```

- [ ] **Step 4: Bind the badge composer to the new view**

Read `app/Providers/AppServiceProvider.php` around line 26 first (`View::composer('components.admin-nav', AdminNavComposer::class);`), then add a second binding directly below it:
```php
View::composer('components.admin-nav', AdminNavComposer::class);
View::composer('components.admin-nav-desktop', AdminNavComposer::class);
```

- [ ] **Step 5: Build assets and verify no Blade errors**

```bash
npm run build
```
Expected: build succeeds with no errors (the component isn't wired into any page yet, so this just checks for Blade/Vite syntax errors — Laravel compiles Blade lazily on request, so also smoke-test with `php artisan view:clear && php -l resources/views/components/nav-dropdown.blade.php` is not applicable to Blade files; instead confirm via Task 2's page load).

- [ ] **Step 6: Commit**

```bash
git add resources/views/components/nav-dropdown.blade.php resources/views/components/admin-nav-desktop.blade.php app/Providers/AppServiceProvider.php
git commit -m "feat(admin): add desktop dropdown nav components"
```

---

### Task 2: Admin shell rewrite (mobile drawer + avatar-menu + header)

**Files:**
- Create: `resources/views/components/admin/mobile-drawer.blade.php`
- Create: `resources/views/components/admin/avatar-menu.blade.php`
- Modify: `resources/views/components/admin.blade.php` (full rewrite)

**Interfaces:**
- Consumes: `<x-admin-nav-desktop />` from Task 1.
- Consumes: `<x-admin-nav />` (existing, unmodified — `resources/views/components/admin-nav.blade.php`, already `<x-sidebar-section>`/`<x-sidebar-link>` accordion markup) as the mobile drawer's nav content.
- Produces: `<x-admin.mobile-drawer x-show="mobileOpen" @click.outside="mobileOpen = false">...</x-admin.mobile-drawer>` — full-screen overlay shell (backdrop + close button + slot), Alpine attributes forwarded via `$attributes`. Consumed identically by Task 3's superadmin shell.
- Produces: `<x-admin.avatar-menu />` — dropdown with "Profile Settings" (→ `route('admin.profile')`) + "Sign out" items.
- Produces: `<x-admin title="...">...<x-slot name="navigation">...</x-slot>...</x-admin>` keeps its existing public API (`title` prop, default slot, `navigation` slot) so none of the 19 page files (`resources/views/admin/*.blade.php`) need to change — `navigation` slot content becomes unused by the new shell but stays accepted (harmless) so no page edit is required in this task.

- [ ] **Step 1: Create the mobile drawer shell**

`resources/views/components/admin/mobile-drawer.blade.php`:
```blade
@props(['title' => null])

<div {{ $attributes->merge(['class' => 'fixed inset-0 z-50 lg:hidden']) }} x-cloak>
    <div class="absolute inset-0 bg-navy/40" @click="mobileOpen = false"></div>
    <div class="absolute inset-y-0 left-0 w-72 max-w-[85vw] bg-surface flex flex-col overflow-y-auto"
         x-transition:enter="transition-transform duration-200 ease-out"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition-transform duration-150 ease-in"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full">
        <div class="h-14 flex items-center justify-between px-4 border-b border-line shrink-0">
            <div class="flex items-center gap-2.5">
                <img src="{{ asset('basket_logo.jpeg') }}" alt="Lil' Hoopsters" class="w-8 h-8 rounded-lg object-cover shrink-0">
                <p class="text-navy font-extrabold text-sm uppercase tracking-tight">{{ $title ?? 'Menu' }}</p>
            </div>
            <button type="button" @click="mobileOpen = false" class="p-1.5 rounded-lg hover:bg-off text-muted">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="flex items-center gap-3 px-4 py-3 border-b border-line shrink-0">
            <livewire:locale-switcher />
            <livewire:notification-bell />
        </div>
        <nav class="flex-1 overflow-y-auto py-3 px-3 space-y-1">
            {{ $slot }}
        </nav>
    </div>
</div>
```
Note: `$title` here is a plain optional prop (not the page's `$title`) — pass `title="Admin Panel"` from the parent shell, defaulting to "Menu" if omitted.

- [ ] **Step 2: Create the admin avatar-menu**

`resources/views/components/admin/avatar-menu.blade.php`:
```blade
<div x-data="{ open: false }" @click.outside="open = false" class="relative shrink-0">
    <button @click="open = !open" type="button"
            class="w-8 h-8 rounded-full bg-navy text-off flex items-center justify-center font-bold text-xs hover:bg-navy/90 transition-colors">
        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
    </button>

    <div x-show="open" x-cloak x-transition.opacity.duration.150ms
         class="absolute right-0 mt-2 w-52 bg-surface border border-line rounded-xl shadow-lg py-1.5 z-40">
        <div class="px-3.5 py-2 border-b border-line mb-1">
            <p class="text-sm font-semibold text-ink truncate">{{ auth()->user()->name }}</p>
            <p class="text-xs text-muted truncate">{{ auth()->user()->email }}</p>
        </div>

        <a href="{{ route('admin.profile') }}" @click="open = false"
           class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-ink hover:bg-off transition-colors">
            <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            {{ __('messages.admin.nav.profile') }}
        </a>

        <div class="border-t border-line mt-1 pt-1">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-2.5 px-3.5 py-2 text-sm text-[#B91C1C] hover:bg-[#B91C1C]/5 transition-colors text-left">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    {{ __('messages.admin.sign_out') }}
                </button>
            </form>
        </div>
    </div>
</div>
```

- [ ] **Step 3: Rewrite the admin shell**

Read `resources/views/components/admin.blade.php` in full first (current sidebar version), then replace its entire contents with:
```blade
<x-app>
    <x-slot name="title">{{ $title ?? 'Admin' }}</x-slot>

    <div class="flex flex-col min-h-[100dvh]" x-data="{ mobileOpen: false }">

        {{-- Topbar --}}
        <header class="h-14 bg-surface border-b border-line flex justify-between lg:grid lg:grid-cols-[1fr_auto_1fr] items-center px-4 gap-4 sticky top-0 z-30">
            <div class="flex items-center gap-3 min-w-0">
                <button type="button" @click="mobileOpen = true" class="lg:hidden p-2 -ml-2 rounded-lg hover:bg-off text-muted" aria-label="Menu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <img src="{{ asset('basket_logo.jpeg') }}" alt="Lil' Hoopsters" class="w-8 h-8 rounded-lg object-cover shrink-0">
            </div>
            <x-admin-nav-desktop />
            <div class="flex items-center justify-end gap-4">
                <div class="hidden lg:flex items-center gap-4">
                    <livewire:locale-switcher />
                    <livewire:notification-bell />
                </div>
                <x-admin.avatar-menu />
            </div>
        </header>

        {{-- Mobile drawer --}}
        <x-admin.mobile-drawer x-show="mobileOpen" @click.outside="mobileOpen = false" :title="__('messages.admin.panel')">
            <x-admin-nav />
        </x-admin.mobile-drawer>

        {{-- Content --}}
        <main class="flex-1 bg-off p-4 sm:p-6">
            {{ $slot }}
        </main>
    </div>

</x-app>
```
Note: the `navigation` named slot from the old shell is no longer referenced — every admin page still passes `<x-slot name="navigation"><x-admin-nav /></x-slot>`, which Blade silently accepts as an unused slot. This keeps all 19 page files untouched in this task; a later cleanup task (outside this plan's scope) can strip the now-dead `navigation` slot from each page.

- [ ] **Step 4: Run the admin test subset**

```bash
php artisan test tests/Feature/Admin tests/Feature/I18nTest.php
```
Expected: all pass (same count as before this change — this is a presentational-only change, no route/controller/Livewire logic touched).

- [ ] **Step 5: Build assets**

```bash
npm run build
```

- [ ] **Step 6: Manual browser verification**

Log in as an admin (`admin@demo.com` / `password` or the seeded admin account), then check:
- `/admin/dashboard` — top-nav shows Dashboard + 5 dropdown groups, centered; avatar "A"-style initial top-right opens Profile Settings/Sign out.
- Click "People" dropdown — panel opens below, shows Parents/Players/Leads/Coaches/Import Members, closes on outside click.
- Any page with a badge (e.g. if a pending leave request exists) — badge number shows inside the Operations dropdown next to "Leave Requests".
- Resize to mobile width — topbar collapses to hamburger+logo+avatar; tapping hamburger opens the left drawer with locale/bell at top and the full accordion nav below; tapping a link or the backdrop closes it.

- [ ] **Step 7: Commit**

```bash
git add resources/views/components/admin/mobile-drawer.blade.php resources/views/components/admin/avatar-menu.blade.php resources/views/components/admin.blade.php
git commit -m "feat(admin): replace sidebar shell with top-nav + mobile drawer"
```

---

### Task 3: Superadmin shell rewrite

**Files:**
- Create: `resources/views/components/superadmin/top-nav.blade.php`
- Create: `resources/views/components/superadmin/avatar-menu.blade.php`
- Modify: `resources/views/components/superadmin.blade.php` (full rewrite)

**Interfaces:**
- Consumes: `<x-admin.mobile-drawer>` from Task 2 (generic, no admin-specific logic — reused as-is for superadmin's drawer).
- Consumes: `<x-superadmin-nav />` (existing, unmodified — `resources/views/components/superadmin-nav.blade.php`) as the mobile drawer's nav content.
- Produces: `<x-superadmin.top-nav />` — flat 3-link desktop nav (Dashboard, Admin Accounts, System Settings).
- Produces: `<x-superadmin.avatar-menu />` — dropdown with Sign Out only (no profile route exists for superadmin — confirmed via `grep -n "superadmin.profile" routes/web.php` returning no matches).

- [ ] **Step 1: Create the flat desktop nav**

`resources/views/components/superadmin/top-nav.blade.php`:
```blade
@php
    $isActive = fn(string ...$routes) => collect($routes)->contains(fn($r) => request()->routeIs($r));
@endphp

<nav class="hidden lg:flex items-center gap-1">
    <a href="{{ route('superadmin.dashboard') }}"
       class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold transition-colors {{ $isActive('superadmin.dashboard') ? 'bg-navy/8 text-navy' : 'text-muted hover:text-navy hover:bg-off' }}">
        {{ __('messages.superadmin.nav.dashboard') }}
    </a>
    <a href="{{ route('superadmin.admins') }}"
       class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold transition-colors {{ $isActive('superadmin.admins') ? 'bg-navy/8 text-navy' : 'text-muted hover:text-navy hover:bg-off' }}">
        {{ __('messages.superadmin.nav.admin_accounts') }}
    </a>
    <a href="{{ route('superadmin.system-settings') }}"
       class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold transition-colors {{ $isActive('superadmin.system-settings') ? 'bg-navy/8 text-navy' : 'text-muted hover:text-navy hover:bg-off' }}">
        {{ __('messages.superadmin.nav.system_settings') }}
    </a>
</nav>
```

- [ ] **Step 2: Create the superadmin avatar-menu (sign-out only)**

`resources/views/components/superadmin/avatar-menu.blade.php`:
```blade
<div x-data="{ open: false }" @click.outside="open = false" class="relative shrink-0">
    <button @click="open = !open" type="button"
            class="w-8 h-8 rounded-full bg-navy text-off flex items-center justify-center font-bold text-xs hover:bg-navy/90 transition-colors">
        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
    </button>

    <div x-show="open" x-cloak x-transition.opacity.duration.150ms
         class="absolute right-0 mt-2 w-52 bg-surface border border-line rounded-xl shadow-lg py-1.5 z-40">
        <div class="px-3.5 py-2 border-b border-line mb-1">
            <p class="text-sm font-semibold text-ink truncate">{{ auth()->user()->name }}</p>
            <p class="text-xs text-muted truncate">{{ auth()->user()->email }}</p>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="w-full flex items-center gap-2.5 px-3.5 py-2 text-sm text-[#B91C1C] hover:bg-[#B91C1C]/5 transition-colors text-left">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                {{ __('messages.superadmin.sign_out') }}
            </button>
        </form>
    </div>
</div>
```

- [ ] **Step 3: Rewrite the superadmin shell**

Read `resources/views/components/superadmin.blade.php` in full first, then replace its entire contents with:
```blade
<x-app>
    <x-slot name="title">{{ $title ?? 'Super Admin' }}</x-slot>

    <div class="flex flex-col min-h-[100dvh]" x-data="{ mobileOpen: false }">

        {{-- Topbar --}}
        <header class="h-14 bg-surface border-b border-line flex justify-between lg:grid lg:grid-cols-[1fr_auto_1fr] items-center px-4 gap-4 sticky top-0 z-30">
            <div class="flex items-center gap-3 min-w-0">
                <button type="button" @click="mobileOpen = true" class="lg:hidden p-2 -ml-2 rounded-lg hover:bg-off text-muted" aria-label="Menu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <img src="{{ asset('basket_logo.jpeg') }}" alt="Lil' Hoopsters" class="w-8 h-8 rounded-lg object-cover shrink-0">
            </div>
            <x-superadmin.top-nav />
            <div class="flex items-center justify-end gap-4">
                <div class="hidden lg:block">
                    <livewire:locale-switcher />
                </div>
                <x-superadmin.avatar-menu />
            </div>
        </header>

        {{-- Mobile drawer --}}
        <x-admin.mobile-drawer x-show="mobileOpen" @click.outside="mobileOpen = false" :title="__('messages.superadmin.panel')">
            <x-superadmin-nav />
        </x-admin.mobile-drawer>

        {{-- Content --}}
        <main class="flex-1 bg-off p-6">
            {{ $slot }}
        </main>
    </div>

</x-app>
```
Note: same as Task 2, the `navigation` slot in each of the 3 superadmin page files becomes unused but harmless — no page file edits needed.

- [ ] **Step 4: Run the superadmin test subset**

```bash
php artisan test tests/Feature/Superadmin
```
Expected: all pass.

- [ ] **Step 5: Build assets**

```bash
npm run build
```

- [ ] **Step 6: Manual browser verification**

Log in as a superadmin, check `/superadmin/dashboard`, `/superadmin/admins`, `/superadmin/system-settings` — flat 3-link nav centered, avatar menu shows Sign Out only (no Profile item), mobile hamburger opens the drawer with the existing 2-section accordion content.

- [ ] **Step 7: Commit**

```bash
git add resources/views/components/superadmin/top-nav.blade.php resources/views/components/superadmin/avatar-menu.blade.php resources/views/components/superadmin.blade.php
git commit -m "feat(superadmin): replace sidebar shell with top-nav + mobile drawer"
```

---

### Task 4: Full verification

**Files:** none (verification only).

- [ ] **Step 1: Run the full test suite**

```bash
php artisan test
```
Expected: 345 passed (same count as before this plan — no tests added or removed by this plan; if the baseline count differs at execution time due to other work, confirm it matches whatever `main`/branch tip showed immediately before Task 1 started).

- [ ] **Step 2: Manual cross-role browser pass**

Visit one page per admin dropdown group plus superadmin, confirm: correct group highlighted as active when on one of its pages, badges visible where pending counts exist, avatar-menu sign-out actually logs out and redirects to login, no leftover sidebar/collapse-toggle artifacts, no console errors (check via browser devtools or `mcp__claude-in-chrome__read_console_messages` if available).

- [ ] **Step 3: Confirm no dead sidebar-only code remains**

```bash
grep -rn "sidebarCollapsed\|collapsed ? 'lg:ml" resources/views/components/admin.blade.php resources/views/components/superadmin.blade.php
```
Expected: no output (the collapse-toggle logic was fully removed in Tasks 2–3).
