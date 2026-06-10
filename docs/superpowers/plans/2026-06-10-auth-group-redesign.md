# Auth Group Redesign (Register Wizard + Pending) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development. Steps use checkbox (`- [ ]`) syntax.

**Goal:** Apply the locked two-tone design system to the remaining auth pages — register page wrapper, the 3-step register wizard, and the pending page — so the whole auth flow is visually consistent with login.

**Architecture:** Presentation-only. All Livewire bindings (`wire:model`, `wire:click="nextStep|prevStep|submit"`, `wire:loading`), route names (`auth.google`, `login`, `pending`, `logout`), `@csrf`, and field names stay identical so the 244-test baseline holds. Copy is already English; this is a token/class swap (orange/slate/amber → navy/off-white/status tokens) plus replacing one raw `<textarea>`'s inline classes with the design-system equivalents.

**Tech Stack:** Laravel 12, Livewire 3, Tailwind CSS 4. PHP CLI `/opt/homebrew/bin/php`. Verify each file with `npm run build`.

**Design tokens:** `docs/superpowers/specs/2026-06-10-frontend-redesign-design.md` §2. Reuse existing redesigned `x-btn`/`x-input`/`x-alert`.

---

### Task 1: Register page wrapper (`resources/views/auth/register.blade.php`)

**Files:** Modify: `resources/views/auth/register.blade.php` (replace entire file)

- [ ] **Step 1: Replace with:**

```blade
<x-auth title="Create Account">
    <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy mb-1">Join the academy</h2>
    <p class="text-sm text-muted mb-6">Create your parent account</p>

    <a href="{{ route('auth.google') }}"
       class="flex items-center justify-center gap-3 w-full border border-line rounded-xl px-4 py-3 text-sm font-semibold text-ink hover:bg-off transition-colors mb-6">
        <svg class="w-5 h-5" viewBox="0 0 24 24">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
        </svg>
        Sign up with Google
    </a>

    <div class="flex items-center gap-3 mb-6">
        <div class="flex-1 h-px bg-line"></div>
        <span class="text-xs text-faint uppercase tracking-wide">or sign up with email</span>
        <div class="flex-1 h-px bg-line"></div>
    </div>

    <livewire:auth.register-wizard />

    <p class="text-center text-sm text-muted mt-6">
        Already have an account? <a href="{{ route('login') }}" class="text-navy font-bold hover:underline">Sign in</a>
    </p>
</x-auth>
```

- [ ] **Step 2:** `npm run build` (must succeed).

---

### Task 2: Register wizard (`resources/views/livewire/auth/register-wizard.blade.php`)

Convert progress bar, headings, the raw textarea, and radios to design tokens. **Keep every `wire:model`, `wire:click`, `wire:loading`, and `@error` exactly as-is.**

**Files:** Modify: `resources/views/livewire/auth/register-wizard.blade.php` (replace entire file)

- [ ] **Step 1: Replace with:**

```blade
<div>
    {{-- Progress bar --}}
    <div class="flex items-center gap-2 mb-3">
        @for ($i = 1; $i <= $totalSteps; $i++)
            <div class="flex-1 h-1 rounded-full {{ $i <= $step ? 'bg-navy' : 'bg-line' }}"></div>
        @endfor
    </div>
    <p class="text-xs font-semibold uppercase tracking-wide text-faint mb-6">Step {{ $step }} of {{ $totalSteps }}</p>

    {{-- Step 1: Account --}}
    @if ($step === 1)
        <h2 class="text-xl font-extrabold uppercase tracking-tight text-navy mb-1">Create your account</h2>
        <p class="text-sm text-muted mb-6">Your sign-in details</p>
        <div class="space-y-4">
            <x-input wire:model="name" label="Full Name" placeholder="e.g. Budi Santoso" required :error="$errors->first('name')" />
            <x-input wire:model="email" type="email" label="Email" placeholder="you@example.com" required :error="$errors->first('email')" />
            <x-input wire:model="password" type="password" label="Password" placeholder="Min. 8 characters" required :error="$errors->first('password')" />
            <x-input wire:model="password_confirmation" type="password" label="Confirm Password" placeholder="Repeat your password" required :error="$errors->first('password_confirmation')" />
        </div>
        <x-btn wire:click="nextStep" class="w-full mt-6">Next →</x-btn>
    @endif

    {{-- Step 2: Parent Info --}}
    @if ($step === 2)
        <h2 class="text-xl font-extrabold uppercase tracking-tight text-navy mb-1">Parent information</h2>
        <p class="text-sm text-muted mb-6">Your contact details</p>
        <div class="space-y-4">
            <x-input wire:model="whatsapp_number" label="WhatsApp Number" placeholder="e.g. 08123456789" required :error="$errors->first('whatsapp_number')" helper="We'll use this for important updates" />
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Address <span class="text-faint font-normal normal-case">(optional)</span></label>
                <textarea wire:model="address" rows="2" placeholder="e.g. Jl. Sudirman No. 1, Jakarta"
                          class="block w-full rounded-xl px-3.5 py-3 text-sm bg-surface text-ink border border-line placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy"></textarea>
            </div>
            <x-input wire:model="occupation" label="Occupation" placeholder="e.g. Business Owner" :error="$errors->first('occupation')" />
        </div>
        <div class="flex gap-3 mt-6">
            <x-btn wire:click="prevStep" variant="secondary" class="flex-1">← Back</x-btn>
            <x-btn wire:click="nextStep" class="flex-1">Next →</x-btn>
        </div>
    @endif

    {{-- Step 3: Player --}}
    @if ($step === 3)
        <h2 class="text-xl font-extrabold uppercase tracking-tight text-navy mb-1">Add your player</h2>
        <p class="text-sm text-muted mb-6">You can add more players after signing in</p>
        <div class="space-y-4">
            <x-input wire:model="child_name" label="Player's Name" placeholder="e.g. Rafi Santoso" required :error="$errors->first('child_name')" />
            <x-input wire:model="child_birth_date" type="date" label="Date of Birth" required :error="$errors->first('child_birth_date')" />
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Gender <span class="text-[#DC2626]">*</span></label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer text-sm text-ink">
                        <input type="radio" wire:model="child_gender" value="male" class="accent-navy"> Boy
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-sm text-ink">
                        <input type="radio" wire:model="child_gender" value="female" class="accent-navy"> Girl
                    </label>
                </div>
                @error('child_gender') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
            </div>
            <x-input wire:model="child_school" label="School" placeholder="e.g. SPH Kemang" :error="$errors->first('child_school')" />
        </div>
        <x-alert type="info" class="mt-4 text-xs">
            After signing up, our team will review your registration within <strong>1–2 business days</strong>.
        </x-alert>
        <div class="flex gap-3 mt-6">
            <x-btn wire:click="prevStep" variant="secondary" class="flex-1">← Back</x-btn>
            <x-btn wire:click="submit" class="flex-1" wire:loading.attr="disabled">
                <span wire:loading.remove>Join Lil' Hoopsters!</span>
                <span wire:loading>Submitting...</span>
            </x-btn>
        </div>
    @endif
</div>
```

- [ ] **Step 2:** `npm run build` (must succeed).

---

### Task 3: Pending page (`resources/views/auth/pending.blade.php`)

Convert the amber status circle to a navy brand circle, slate text to tokens, orange WA link to navy.

**Files:** Modify: `resources/views/auth/pending.blade.php` (replace entire file)

- [ ] **Step 1: Replace with:**

```blade
<x-auth title="You're Almost In!">
    <div class="text-center">
        <div class="w-16 h-16 bg-navy rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-off" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy mb-2">You're almost in!</h2>
        <p class="text-sm text-muted mb-6">
            Our team is reviewing your registration.<br>
            We'll get back to you within <strong>1–2 business days</strong>.
        </p>
        <x-alert type="info" class="text-left mb-6">
            We'll send you a WhatsApp message once your account is approved. 🏀
        </x-alert>
        <p class="text-xs text-muted">
            Need help? Reach us on WhatsApp<br>
            <a href="https://wa.me/6281770212177" class="text-navy font-bold hover:underline" target="_blank">+62 817-7021-2177</a>
        </p>
        <form method="POST" action="{{ route('logout') }}" class="mt-6">
            @csrf
            <x-btn type="submit" variant="secondary" class="w-full">Sign Out</x-btn>
        </form>
    </div>
</x-auth>
```

- [ ] **Step 2:** `npm run build` (must succeed).

---

### Task 4: Verify & commit

- [ ] **Step 1:** `npm run build` (success).
- [ ] **Step 2:** `/opt/homebrew/bin/php artisan test` — all 244 pass (no regression). Register flow tests especially must stay green.
- [ ] **Step 3:** Commit all three files:

```bash
git add resources/views/auth/register.blade.php resources/views/livewire/auth/register-wizard.blade.php resources/views/auth/pending.blade.php
git commit -m "feat(design): redesign register wizard + pending (two-tone)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

- [ ] **Step 4 (manual, optional):** render `/register` and `/pending`, step through the wizard, check no orange/amber remains and no horizontal scroll at 375px.
