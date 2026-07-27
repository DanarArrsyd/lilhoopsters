<div
    class="min-h-[100dvh] flex flex-col bg-white relative"
    x-data
    x-on:keydown.enter.window="
        if ($event.target.tagName === 'TEXTAREA') return;
        {{ $step < $totalSteps ? '$wire.nextStep()' : '$wire.submit()' }}
    "
>
    {{-- Progress bar --}}
    <div class="fixed top-0 left-0 right-0 h-[3px] bg-gray-100 z-20">
        <div class="h-full bg-navy transition-all duration-300" style="width: {{ round($step / $totalSteps * 100) }}%"></div>
    </div>

    {{-- Logo --}}
    <div class="fixed top-5 left-6 z-20 flex items-center gap-2.5">
        <img src="{{ asset('basket_logo.jpeg') }}" alt="Lil' Hoopsters" class="w-8 h-8 rounded-xl object-cover">
        <span class="text-navy font-extrabold text-sm uppercase tracking-tight">Lil' Hoopsters</span>
    </div>

    {{-- Step counter --}}
    <div class="fixed top-5 right-6 z-20 text-xs text-gray-400 font-medium tabular-nums">
        {{ $step }} / {{ $totalSteps }}
    </div>

    {{-- Content --}}
    <div class="flex-1 flex items-center justify-center px-6 py-24">
        <div class="w-full max-w-xl">

            {{-- ── Step 1: Full Name ──────────────────────────────── --}}
            @if($step === 1)

                <div class="flex items-center gap-2 mb-4">
                    <span class="text-navy font-bold text-sm">1</span>
                    <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="text-xs font-semibold uppercase tracking-widest text-gray-400">Parent's Name</span>
                    <span class="text-[#DC2626] text-sm font-bold">*</span>
                </div>

                <h1 class="text-3xl sm:text-4xl font-extrabold text-navy leading-tight mb-8">
                    What's your full name?
                </h1>

                <input wire:key="rw-name"
                    wire:model="name"
                    type="text"
                    placeholder="e.g. Budi Santoso"
                    autocomplete="name"
                    autofocus
                    class="w-full border-0 border-b-2 border-gray-200 focus:border-navy bg-transparent text-xl sm:text-2xl text-navy py-3 focus:outline-none placeholder:text-gray-300 transition-colors duration-200 caret-navy"
                >

                @error('name')
                    <p class="mt-3 text-sm text-[#DC2626] flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        {{ $message }}
                    </p>
                @enderror

                <div class="mt-8 flex items-center gap-4">
                    <button wire:click="nextStep" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 bg-navy text-white text-sm font-bold uppercase tracking-wide px-5 py-2.5 rounded-xl hover:bg-navy/90 active:scale-[0.97] transition-all duration-150 disabled:opacity-50">
                        <svg wire:loading.remove wire:target="nextStep" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        <svg wire:loading wire:target="nextStep" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                        OK
                    </button>
                    <span class="text-xs text-gray-400">Press <kbd class="px-1.5 py-0.5 text-[10px] font-mono bg-gray-100 border border-gray-200 rounded text-gray-500">Enter ↵</kbd></span>
                </div>

                {{-- Google option --}}
                <div class="mt-10 pt-8 border-t border-gray-100">
                    <p class="text-xs text-gray-400 mb-3">Or sign up faster with</p>
                    <a href="{{ route('auth.google') }}"
                       class="flex items-center justify-center gap-2.5 w-full border border-gray-200 rounded-xl px-4 py-3 text-sm font-semibold text-gray-600 hover:border-gray-300 hover:bg-gray-50 transition-colors">
                        <svg class="w-4 h-4" viewBox="0 0 24 24">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                        </svg>
                        Continue with Google
                    </a>
                </div>

            @endif

            {{-- ── Step 2: Email ──────────────────────────────────── --}}
            @if($step === 2)

                <div class="flex items-center gap-2 mb-4">
                    <span class="text-navy font-bold text-sm">2</span>
                    <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="text-xs font-semibold uppercase tracking-widest text-gray-400">Email Address</span>
                    <span class="text-[#DC2626] text-sm font-bold">*</span>
                </div>

                <h1 class="text-3xl sm:text-4xl font-extrabold text-navy leading-tight mb-8">
                    What's your email address?
                </h1>

                <input wire:key="rw-email"
                    wire:model="email"
                    type="email"
                    placeholder="you@example.com"
                    autocomplete="email"
                    autofocus
                    class="w-full border-0 border-b-2 border-gray-200 focus:border-navy bg-transparent text-xl sm:text-2xl text-navy py-3 focus:outline-none placeholder:text-gray-300 transition-colors duration-200 caret-navy"
                >

                @error('email')
                    <p class="mt-3 text-sm text-[#DC2626] flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        {{ $message }}
                    </p>
                @enderror

                <div class="mt-8 flex items-center gap-4">
                    <button wire:click="nextStep" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 bg-navy text-white text-sm font-bold uppercase tracking-wide px-5 py-2.5 rounded-xl hover:bg-navy/90 active:scale-[0.97] transition-all duration-150 disabled:opacity-50">
                        <svg wire:loading.remove wire:target="nextStep" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        <svg wire:loading wire:target="nextStep" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                        OK
                    </button>
                    <span class="text-xs text-gray-400">Press <kbd class="px-1.5 py-0.5 text-[10px] font-mono bg-gray-100 border border-gray-200 rounded text-gray-500">Enter ↵</kbd></span>
                </div>

            @endif

            {{-- ── Step 3: Password ───────────────────────────────── --}}
            @if($step === 3)

                <div class="flex items-center gap-2 mb-4">
                    <span class="text-navy font-bold text-sm">3</span>
                    <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="text-xs font-semibold uppercase tracking-widest text-gray-400">Password</span>
                    <span class="text-[#DC2626] text-sm font-bold">*</span>
                </div>

                <h1 class="text-3xl sm:text-4xl font-extrabold text-navy leading-tight mb-3">
                    Create a password
                </h1>
                <p class="text-sm text-gray-400 mb-8">At least 8 characters.</p>

                <div class="space-y-6">
                    {{-- Password --}}
                    <div x-data="{ show: false }">
                        <div class="relative">
                            <input wire:key="rw-password"
                                wire:model="password"
                                :type="show ? 'text' : 'password'"
                                placeholder="Password"
                                autocomplete="new-password"
                                autofocus
                                class="w-full border-0 border-b-2 border-gray-200 focus:border-navy bg-transparent text-xl sm:text-2xl text-navy py-3 pr-10 focus:outline-none placeholder:text-gray-300 transition-colors duration-200 caret-navy"
                            >
                            <button type="button" @click="show = !show" tabindex="-1"
                                class="absolute inset-y-0 right-0 flex items-center px-2 text-gray-300 hover:text-gray-500 transition-colors">
                                <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.477 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 4.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="mt-2 text-sm text-[#DC2626] flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Confirm Password --}}
                    <div x-data="{ show: false }">
                        <div class="relative">
                            <input wire:key="rw-password-confirm"
                                wire:model="password_confirmation"
                                :type="show ? 'text' : 'password'"
                                placeholder="Confirm password"
                                autocomplete="new-password"
                                class="w-full border-0 border-b-2 border-gray-200 focus:border-navy bg-transparent text-xl sm:text-2xl text-navy py-3 pr-10 focus:outline-none placeholder:text-gray-300 transition-colors duration-200 caret-navy"
                            >
                            <button type="button" @click="show = !show" tabindex="-1"
                                class="absolute inset-y-0 right-0 flex items-center px-2 text-gray-300 hover:text-gray-500 transition-colors">
                                <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.477 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 4.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                        @error('password_confirmation')
                            <p class="mt-2 text-sm text-[#DC2626] flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <div class="mt-8 flex items-center gap-4">
                    <button wire:click="nextStep" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 bg-navy text-white text-sm font-bold uppercase tracking-wide px-5 py-2.5 rounded-xl hover:bg-navy/90 active:scale-[0.97] transition-all duration-150 disabled:opacity-50">
                        <svg wire:loading.remove wire:target="nextStep" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        <svg wire:loading wire:target="nextStep" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                        OK
                    </button>
                    <span class="text-xs text-gray-400">Press <kbd class="px-1.5 py-0.5 text-[10px] font-mono bg-gray-100 border border-gray-200 rounded text-gray-500">Enter ↵</kbd></span>
                </div>

            @endif

            {{-- ── Step 4: WhatsApp ───────────────────────────────── --}}
            @if($step === 4)

                <div class="flex items-center gap-2 mb-4">
                    <span class="text-navy font-bold text-sm">4</span>
                    <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="text-xs font-semibold uppercase tracking-widest text-gray-400">WhatsApp Number</span>
                    <span class="text-[#DC2626] text-sm font-bold">*</span>
                </div>

                <h1 class="text-3xl sm:text-4xl font-extrabold text-navy leading-tight mb-3">
                    Your WhatsApp number?
                </h1>
                <p class="text-sm text-gray-400 mb-8">Start with country code, no + or leading 0.</p>

                <input wire:key="rw-whatsapp"
                    wire:model="whatsapp_number"
                    type="tel"
                    placeholder="e.g. 628123456789"
                    autocomplete="tel"
                    autofocus
                    class="w-full border-0 border-b-2 border-gray-200 focus:border-navy bg-transparent text-xl sm:text-2xl text-navy py-3 focus:outline-none placeholder:text-gray-300 transition-colors duration-200 caret-navy tracking-wide"
                >

                @error('whatsapp_number')
                    <p class="mt-3 text-sm text-[#DC2626] flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        {{ $message }}
                    </p>
                @enderror

                <div class="mt-8 flex items-center gap-4">
                    <button wire:click="nextStep" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 bg-navy text-white text-sm font-bold uppercase tracking-wide px-5 py-2.5 rounded-xl hover:bg-navy/90 active:scale-[0.97] transition-all duration-150 disabled:opacity-50">
                        <svg wire:loading.remove wire:target="nextStep" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        <svg wire:loading wire:target="nextStep" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                        OK
                    </button>
                    <span class="text-xs text-gray-400">Press <kbd class="px-1.5 py-0.5 text-[10px] font-mono bg-gray-100 border border-gray-200 rounded text-gray-500">Enter ↵</kbd></span>
                </div>

            @endif

            {{-- ── Step 5: Address ────────────────────────────────── --}}
            @if($step === 5)

                <div class="flex items-center gap-2 mb-4">
                    <span class="text-navy font-bold text-sm">5</span>
                    <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="text-xs font-semibold uppercase tracking-widest text-gray-400">Home Address</span>
                    <span class="text-xs text-gray-300 ml-1">(optional)</span>
                </div>

                <h1 class="text-3xl sm:text-4xl font-extrabold text-navy leading-tight mb-8">
                    What's your home address?
                </h1>

                <textarea wire:key="rw-address"
                    wire:model="address"
                    rows="2"
                    placeholder="e.g. Jl. Sudirman No. 1, Jakarta"
                    class="w-full border-0 border-b-2 border-gray-200 focus:border-navy bg-transparent text-xl sm:text-2xl text-navy py-3 focus:outline-none placeholder:text-gray-300 transition-colors duration-200 caret-navy resize-none leading-snug"
                ></textarea>

                @error('address')
                    <p class="mt-3 text-sm text-[#DC2626] flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        {{ $message }}
                    </p>
                @enderror

                <div class="mt-8 flex items-center gap-4">
                    <button wire:click="nextStep" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 bg-navy text-white text-sm font-bold uppercase tracking-wide px-5 py-2.5 rounded-xl hover:bg-navy/90 active:scale-[0.97] transition-all duration-150 disabled:opacity-50">
                        <svg wire:loading.remove wire:target="nextStep" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        <svg wire:loading wire:target="nextStep" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                        OK
                    </button>
                    <button wire:click="nextStep" class="text-xs text-gray-400 hover:text-navy transition-colors duration-150">
                        Skip
                    </button>
                </div>

            @endif

            {{-- ── Step 6: Occupation ─────────────────────────────── --}}
            @if($step === 6)

                <div class="flex items-center gap-2 mb-4">
                    <span class="text-navy font-bold text-sm">6</span>
                    <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="text-xs font-semibold uppercase tracking-widest text-gray-400">Occupation</span>
                    <span class="text-xs text-gray-300 ml-1">(optional)</span>
                </div>

                <h1 class="text-3xl sm:text-4xl font-extrabold text-navy leading-tight mb-8">
                    What's your occupation?
                </h1>

                <input wire:key="rw-occupation"
                    wire:model="occupation"
                    type="text"
                    placeholder="e.g. Business Owner, Teacher..."
                    autocomplete="organization-title"
                    autofocus
                    class="w-full border-0 border-b-2 border-gray-200 focus:border-navy bg-transparent text-xl sm:text-2xl text-navy py-3 focus:outline-none placeholder:text-gray-300 transition-colors duration-200 caret-navy"
                >

                @error('occupation')
                    <p class="mt-3 text-sm text-[#DC2626] flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        {{ $message }}
                    </p>
                @enderror

                <div class="mt-8 flex items-center gap-4">
                    <button wire:click="submit" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 bg-[#15803D] text-white text-sm font-bold uppercase tracking-wide px-5 py-2.5 rounded-xl hover:bg-[#166534] active:scale-[0.97] transition-all duration-150 disabled:opacity-50">
                        <svg wire:loading.remove wire:target="submit" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        <svg wire:loading wire:target="submit" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                        <span wire:loading.remove wire:target="submit">Create Account</span>
                        <span wire:loading wire:target="submit">Creating...</span>
                    </button>
                    <button wire:click="submit" class="text-xs text-gray-400 hover:text-navy transition-colors duration-150">
                        Skip
                    </button>
                </div>

                <p class="mt-6 text-xs text-gray-400 leading-relaxed max-w-sm">
                    After signing up, our team will review your registration within <strong class="text-gray-500">1–2 business days</strong>.
                </p>

            @endif

        </div>
    </div>

    {{-- Arrow nav --}}
    <div class="fixed bottom-6 right-6 flex flex-col gap-1.5 z-20">
        <button
            wire:click="prevStep"
            @class([
                'w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-400 transition-colors duration-150',
                'opacity-30 pointer-events-none' => $step === 1,
                'hover:border-navy hover:text-navy' => $step > 1,
            ])
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/>
            </svg>
        </button>
        <button
            wire:key="rw-primary-{{ $step < $totalSteps ? 'next' : 'submit' }}" wire:click="{{ $step < $totalSteps ? 'nextStep' : 'submit' }}"
            class="w-8 h-8 flex items-center justify-center rounded-lg border border-navy text-navy hover:bg-navy hover:text-white transition-colors duration-150"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
    </div>

    {{-- Sign in link --}}
    <div class="fixed bottom-6 left-6 z-20">
        <a href="{{ route('login') }}" class="text-xs text-gray-400 hover:text-navy transition-colors duration-150">
            Already have an account? <span class="font-semibold">Sign in</span>
        </a>
    </div>

</div>
