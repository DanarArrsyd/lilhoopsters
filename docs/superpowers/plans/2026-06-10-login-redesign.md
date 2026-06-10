# Login Page Redesign + Minimal Foundations — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Redesign the login page in the new two-tone (navy `#0A0F1E` + off-white `#FAF9F6`), Arial, English design language, and land the minimal shared foundations (CSS tokens, `x-btn`, `x-input`, `x-alert`, `auth` shell) that the rest of the redesign will reuse.

**Architecture:** This is a presentation-only change. The login POST flow, route names, form field names (`email`, `password`, `remember`), and CSRF stay identical so the existing backend feature tests keep passing. Verification is therefore **regression-based** (existing suite stays green) + **build** (`npm run build` compiles) + **manual browser render** at three widths — not new unit tests, since there is no meaningful unit to assert on for pure markup/CSS.

**Tech Stack:** Laravel 12, Livewire 3, Tailwind CSS 4 (`@tailwindcss/vite`), Alpine.js, Blade components. PHP CLI: `/opt/homebrew/bin/php`.

**Design tokens reference:** see `docs/superpowers/specs/2026-06-10-frontend-redesign-design.md` §2.

---

### Task 1: CSS design tokens (two-tone + status + Arial)

Replace the orange/Inter token set with the new two-tone palette. In Tailwind v4, `@theme` color tokens auto-generate utilities (e.g. `--color-navy` → `bg-navy`, `text-navy`, `border-navy`); `--font-sans` makes `font-sans` resolve to Arial and the `body` rule applies it globally.

**Files:**
- Modify: `resources/css/app.css` (replace entire file)

- [ ] **Step 1: Replace `resources/css/app.css` with:**

```css
/* resources/css/app.css */
@import "tailwindcss";

@theme {
    --font-sans: Arial, Helvetica, sans-serif;

    /* Brand two-tone */
    --color-navy:    #0A0F1E;
    --color-navy-2:  #141B2E;
    --color-off:     #FAF9F6;
    --color-surface: #FFFFFF;
    --color-line:    #E6E3DC;

    /* Text */
    --color-ink:     #0A0F1E;
    --color-muted:   #6B7280;
    --color-faint:   #9AA0AC;

    /* Functional status (small doses only) */
    --color-success: #15803D;
    --color-warning: #B45309;
    --color-danger:  #B91C1C;
    --color-info:    #1D4ED8;
}

@layer base {
    body {
        font-family: var(--font-sans);
        background-color: var(--color-off);
        color: var(--color-ink);
    }

    * {
        -webkit-font-smoothing: antialiased;
    }
}

@layer components {
    /* Active sidebar item: navy fill, off-white text (used by later nav redesign) */
    .sidebar-active {
        @apply bg-navy text-off font-semibold;
    }
}
```

- [ ] **Step 2: Build to verify the CSS compiles**

Run: `npm run build`
Expected: build completes with no errors; `public/build/` assets regenerated.

- [ ] **Step 3: Commit**

```bash
git add resources/css/app.css
git commit -m "feat(design): two-tone navy/off-white tokens + Arial, drop Inter/orange

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 2: Redesign `x-btn` component

Navy primary / outline secondary, uppercase, tactile press, 44px-friendly. Keep the `variant`/`size`/`type`/`href` props so existing usages don't break.

**Files:**
- Modify: `resources/views/components/btn.blade.php` (replace entire file)

- [ ] **Step 1: Replace `resources/views/components/btn.blade.php` with:**

```blade
@props([
    'variant' => 'primary',
    'size'    => 'md',
    'type'    => 'button',
    'href'    => null,
])

@php
$variantClass = match($variant) {
    'primary'   => 'bg-navy text-off hover:bg-navy-2',
    'secondary' => 'bg-transparent border border-navy text-navy hover:bg-navy/5',
    'ghost'     => 'bg-transparent text-navy hover:bg-navy/5',
    'danger'    => 'bg-[#DC2626] text-off hover:bg-[#B91C1C]',
    default     => 'bg-navy text-off hover:bg-navy-2',
};
$sizeClass = match($size) {
    'sm'  => 'text-xs px-3 py-2',
    'md'  => 'text-sm px-4 py-2.5',
    'lg'  => 'text-sm px-5 py-3',
    default => 'text-sm px-4 py-2.5',
};
$base = "inline-flex items-center justify-center gap-2 font-bold uppercase tracking-wide rounded-xl
         transition-colors active:translate-y-px select-none $variantClass $sizeClass";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $base]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $base]) }}>{{ $slot }}</button>
@endif
```

- [ ] **Step 2: Build to verify it compiles**

Run: `npm run build`
Expected: build completes, no errors.

- [ ] **Step 3: Commit**

```bash
git add resources/views/components/btn.blade.php
git commit -m "feat(design): navy/uppercase button variants

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 3: Redesign `x-input` component

Label above, white fill, navy focus ring, error below. Keep `label`/`error`/`helper` props.

**Files:**
- Modify: `resources/views/components/input.blade.php` (replace entire file)

- [ ] **Step 1: Replace `resources/views/components/input.blade.php` with:**

```blade
@props(['label' => null, 'error' => null, 'helper' => null])

<div class="space-y-1.5">
    @if ($label)
        <label class="block text-xs font-semibold uppercase tracking-wide text-navy">
            {{ $label }}
            @if ($attributes->get('required'))
                <span class="text-[#DC2626] ml-0.5">*</span>
            @endif
        </label>
    @endif

    <input {{ $attributes->merge([
        'class' => 'block w-full rounded-xl px-3.5 py-3 text-sm bg-surface text-ink
                    border border-line placeholder:text-faint
                    focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy
                    disabled:bg-off disabled:text-faint
                    ' . ($error ? 'border-[#DC2626] focus:ring-[#DC2626]/20 focus:border-[#DC2626]' : ''),
    ]) }}>

    @if ($error)
        <p class="text-xs text-[#B91C1C]">{{ $error }}</p>
    @elseif ($helper)
        <p class="text-xs text-muted">{{ $helper }}</p>
    @endif
</div>
```

- [ ] **Step 2: Build to verify it compiles**

Run: `npm run build`
Expected: build completes, no errors.

- [ ] **Step 3: Commit**

```bash
git add resources/views/components/input.blade.php
git commit -m "feat(design): input with navy focus ring + uppercase label

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 4: Redesign `x-alert` component

Keep status semantics (success/error/warning/info) but in the new restrained style. Keep `type`/`dismissible` props.

**Files:**
- Modify: `resources/views/components/alert.blade.php` (replace entire file)

- [ ] **Step 1: Replace `resources/views/components/alert.blade.php` with:**

```blade
@props(['type' => 'success', 'dismissible' => false])

@php
$classes = match($type) {
    'success' => 'border-l-4 border-[#15803D] bg-[#15803D]/8 text-[#15803D]',
    'error'   => 'border-l-4 border-[#B91C1C] bg-[#B91C1C]/8 text-[#B91C1C]',
    'warning' => 'border-l-4 border-[#B45309] bg-[#B45309]/8 text-[#B45309]',
    'info'    => 'border-l-4 border-[#1D4ED8] bg-[#1D4ED8]/8 text-[#1D4ED8]',
    default   => 'border-l-4 border-[#15803D] bg-[#15803D]/8 text-[#15803D]',
};
@endphp

<div {{ $attributes->merge(['class' => "rounded-lg p-4 $classes text-sm"]) }}>
    {{ $slot }}
</div>
```

- [ ] **Step 2: Build to verify it compiles**

Run: `npm run build`
Expected: build completes, no errors.

- [ ] **Step 3: Commit**

```bash
git add resources/views/components/alert.blade.php
git commit -m "feat(design): restrained status alert styles

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 5: Redesign `auth` layout shell

Light backdrop, prominent navy logo, light card. Keep the `<x-app>` wrapper (it provides `<head>`, `@vite`, Livewire) and the `title` slot so `login`, `register`, `pending` keep working. Remove the dark gradient.

**Files:**
- Modify: `resources/views/components/auth.blade.php` (replace entire file)

- [ ] **Step 1: Replace `resources/views/components/auth.blade.php` with:**

```blade
<x-app>
    <x-slot name="title">{{ $title ?? 'Sign In' }}</x-slot>

    <div class="min-h-[100dvh] flex items-center justify-center p-4 bg-off">
        <div class="w-full max-w-md">
            {{-- Logo --}}
            <div class="text-center mb-8">
                <div class="inline-flex items-center gap-3">
                    <div class="w-12 h-12 bg-navy rounded-2xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-off" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <circle cx="12" cy="12" r="10" stroke-width="2"/>
                            <path d="M12 2C12 2 8 7 8 12s4 10 4 10" stroke-width="1.5"/>
                            <path d="M12 2C12 2 16 7 16 12s-4 10-4 10" stroke-width="1.5"/>
                            <path d="M2 12h20" stroke-width="1.5"/>
                        </svg>
                    </div>
                    <span class="text-navy font-extrabold text-2xl uppercase tracking-tight">BasketManage</span>
                </div>
                <p class="text-muted text-sm mt-2">Lil' Hoopsters Basketball Academy</p>
            </div>

            {{-- Card --}}
            <div class="bg-surface border border-line rounded-2xl shadow-sm p-8">
                {{ $slot }}
            </div>
        </div>
    </div>
</x-app>
```

- [ ] **Step 2: Build + confirm `app.blade.php` no longer needs the Inter font link (optional cleanup)**

Open `resources/views/components/app.blade.php`. Remove the two Google Fonts `<link>` lines (preconnect + Inter stylesheet) since we now use Arial. Leave `@vite`, `@livewireStyles`, the `<title>`, and `<body>` as-is, but change the body classes from `bg-[#F8FAFC] text-slate-700` to `bg-off text-ink`.

Run: `npm run build`
Expected: build completes, no errors.

- [ ] **Step 3: Commit**

```bash
git add resources/views/components/auth.blade.php resources/views/components/app.blade.php
git commit -m "feat(design): light auth shell + drop Inter web font

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 6: Redesign the login view

Full English copy, new tokens, uses redesigned components. **Critical:** keep `name="email"`, `name="password"`, `name="remember"`, `method="POST"`, `action="{{ route('login.post') }}"`, `@csrf`, and the `route('auth.google')` / `route('register')` links — these are what the feature tests and controller depend on.

**Files:**
- Modify: `resources/views/auth/login.blade.php` (replace entire file)

- [ ] **Step 1: Replace `resources/views/auth/login.blade.php` with:**

```blade
<x-auth title="Sign In">
    <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy mb-1">Welcome back</h2>
    <p class="text-sm text-muted mb-6">Sign in to your account</p>

    @if (session('error'))
        <x-alert type="error" class="mb-4">{{ session('error') }}</x-alert>
    @endif

    <a href="{{ route('auth.google') }}"
       class="flex items-center justify-center gap-3 w-full border border-line rounded-xl px-4 py-3 text-sm font-semibold text-ink hover:bg-off transition-colors mb-6">
        <svg class="w-5 h-5" viewBox="0 0 24 24">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
        </svg>
        Continue with Google
    </a>

    <div class="flex items-center gap-3 mb-6">
        <div class="flex-1 h-px bg-line"></div>
        <span class="text-xs text-faint uppercase tracking-wide">or</span>
        <div class="flex-1 h-px bg-line"></div>
    </div>

    <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
        @csrf
        <x-input label="Email" type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required autocomplete="email" :error="$errors->first('email')" />
        <x-input label="Password" type="password" name="password" placeholder="••••••••" required autocomplete="current-password" :error="$errors->first('password')" />
        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-muted cursor-pointer">
                <input type="checkbox" name="remember" class="rounded border-line text-navy focus:ring-navy/20"> Remember me
            </label>
        </div>
        <x-btn type="submit" class="w-full">Sign In</x-btn>
    </form>

    <p class="text-center text-sm text-muted mt-6">
        New here? <a href="{{ route('register') }}" class="text-navy font-bold hover:underline">Create an account</a>
    </p>
</x-auth>
```

- [ ] **Step 2: Run the auth/login feature tests to confirm no regression**

Run: `/opt/homebrew/bin/php artisan test --filter=Login`
Expected: PASS. If the filter matches zero tests, run `/opt/homebrew/bin/php artisan test --filter=Auth` instead, then fall back to the full suite in Task 7.

- [ ] **Step 3: Commit**

```bash
git add resources/views/auth/login.blade.php
git commit -m "feat(design): redesign login page (two-tone, English)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 7: Full verification & visual check

**Files:** none (verification only)

- [ ] **Step 1: Full build**

Run: `npm run build`
Expected: success, no errors.

- [ ] **Step 2: Full backend suite stays green**

Run: `/opt/homebrew/bin/php artisan test`
Expected: all tests pass (the 244 baseline). If anything fails, it is a regression in this task — fix before proceeding.

- [ ] **Step 3: Render the login page**

Start the dev servers if not running: `npm run dev` (Vite) and `/opt/homebrew/bin/php artisan serve`.
Open `http://127.0.0.1:8000/login` in a browser.

Verify visually:
- Off-white background, navy logo, light card.
- Heading "WELCOME BACK" uppercase navy; navy "Sign In" button (uppercase).
- Inputs have uppercase labels and navy focus ring (click into a field).
- No orange anywhere; copy is all English.

- [ ] **Step 4: Responsive check**

Resize the browser (or use devtools device mode) to ~375px, ~768px, ~1280px.
Expected: card stays centered and readable; no horizontal scroll; tap targets comfortable on mobile.

- [ ] **Step 5: Final commit (if any tweaks were made during verification)**

```bash
git add -A
git commit -m "chore: login redesign verification tweaks

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

(If no tweaks were needed, skip this commit.)

---

## Notes for the next task

Once login is approved, the next page (register wizard or pending — both already use the now-redesigned `auth` shell) will be fast. The shared shells + grouped admin nav + off-white sidebar (with navy active item) get redesigned when we reach the first admin/coach/portal page, per spec §4.
