<div
    class="min-h-[100dvh] flex flex-col bg-white relative"
    x-data
    x-on:keydown.enter.window="
        if ($event.target.tagName === 'TEXTAREA') return;
        {{ $step < $totalSteps ? '$wire.nextStep()' : '$wire.save()' }}
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

            {{-- ── Step 1: Name ──────────────────────────────────── --}}
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

                <input wire:key="cp-name"
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
                    <span class="text-xs text-gray-400">Press <kbd class="px-1.5 py-0.5 text-3xs font-mono bg-gray-100 border border-gray-200 rounded text-gray-500">Enter ↵</kbd></span>
                </div>

            @endif

            {{-- ── Step 2: WhatsApp ───────────────────────────────── --}}
            @if($step === 2)

                <div class="flex items-center gap-2 mb-4">
                    <span class="text-navy font-bold text-sm">2</span>
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

                <input wire:key="cp-whatsapp"
                    wire:model="whatsappNumber"
                    type="tel"
                    placeholder="e.g. 628123456789"
                    autocomplete="tel"
                    autofocus
                    class="w-full border-0 border-b-2 border-gray-200 focus:border-navy bg-transparent text-xl sm:text-2xl text-navy py-3 focus:outline-none placeholder:text-gray-300 transition-colors duration-200 caret-navy tracking-wide"
                >

                @error('whatsappNumber')
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
                    <span class="text-xs text-gray-400">Press <kbd class="px-1.5 py-0.5 text-3xs font-mono bg-gray-100 border border-gray-200 rounded text-gray-500">Enter ↵</kbd></span>
                </div>

            @endif

            {{-- ── Step 3: Address ────────────────────────────────── --}}
            @if($step === 3)

                <div class="flex items-center gap-2 mb-4">
                    <span class="text-navy font-bold text-sm">3</span>
                    <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="text-xs font-semibold uppercase tracking-widest text-gray-400">Home Address</span>
                    <span class="text-xs text-gray-300 ml-1">(optional)</span>
                </div>

                <h1 class="text-3xl sm:text-4xl font-extrabold text-navy leading-tight mb-8">
                    What's your home address?
                </h1>

                <textarea wire:key="cp-address"
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

            {{-- ── Step 4: Occupation ─────────────────────────────── --}}
            @if($step === 4)

                <div class="flex items-center gap-2 mb-4">
                    <span class="text-navy font-bold text-sm">4</span>
                    <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="text-xs font-semibold uppercase tracking-widest text-gray-400">Occupation</span>
                    <span class="text-xs text-gray-300 ml-1">(optional)</span>
                </div>

                <h1 class="text-3xl sm:text-4xl font-extrabold text-navy leading-tight mb-8">
                    What's your occupation?
                </h1>

                <input wire:key="cp-occupation"
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
                    <button wire:click="save" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 bg-[#15803D] text-white text-sm font-bold uppercase tracking-wide px-5 py-2.5 rounded-xl hover:bg-[#166534] active:scale-[0.97] transition-all duration-150 disabled:opacity-50">
                        <svg wire:loading.remove wire:target="save" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        <svg wire:loading wire:target="save" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                        <span wire:loading.remove wire:target="save">Save & Continue</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </button>
                    <button wire:click="save" class="text-xs text-gray-400 hover:text-navy transition-colors duration-150">
                        Skip
                    </button>
                </div>

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
            wire:key="cp-primary-{{ $step < $totalSteps ? 'next' : 'save' }}" wire:click="{{ $step < $totalSteps ? 'nextStep' : 'save' }}"
            class="w-8 h-8 flex items-center justify-center rounded-lg border border-navy text-navy hover:bg-navy hover:text-white transition-colors duration-150"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
    </div>

    {{-- Sign out --}}
    <div class="fixed bottom-6 left-6 z-20">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-xs text-gray-400 hover:text-navy transition-colors duration-150">
                Not you? Sign out
            </button>
        </form>
    </div>

</div>
