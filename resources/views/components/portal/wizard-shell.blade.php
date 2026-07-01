@props(['step', 'totalSteps'])

<div class="flex flex-col min-h-[100dvh]">

    {{-- Topbar --}}
    <header class="h-14 bg-surface border-b border-line flex justify-between lg:grid lg:grid-cols-[1fr_auto_1fr] items-center px-4 gap-4 sticky top-0 z-30">
        <div class="flex items-center min-w-0">
            <img src="{{ asset('basket_logo.jpeg') }}" alt="Lil' Hoopsters" class="w-8 h-8 rounded-lg object-cover shrink-0">
        </div>
        <x-portal.top-nav />
        <div class="flex items-center justify-end gap-4">
            <span class="text-[11px] text-gray-400 font-medium tabular-nums shrink-0">{{ $step }} / {{ $totalSteps }}</span>
            <livewire:locale-switcher />
            <livewire:notification-bell />
            <x-portal.avatar-menu />
        </div>
    </header>

    {{-- Progress bar (sits flush below topbar) --}}
    <div class="h-[3px] bg-gray-100 sticky top-14 z-20 shrink-0">
        <div class="h-full bg-navy transition-all duration-500" style="width: {{ round($step / $totalSteps * 100) }}%"></div>
    </div>

    {{-- ── Scrollable wizard content ── --}}
    <div class="flex-1 bg-white">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 pt-6 pb-24 lg:pb-20">
            {{ $slot }}
        </div>
    </div>

    {{-- Back arrow (fixed bottom-right, only step 2+) --}}
    @if ($step > 1)
        <div class="fixed bottom-20 right-4 lg:bottom-6 lg:right-6 z-20">
            <button wire:click="back" wire:loading.attr="disabled"
                class="w-8 h-8 sm:w-9 sm:h-9 flex items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-400 hover:border-navy hover:text-navy shadow-sm transition-colors duration-150">
                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </button>
        </div>
    @endif

    <x-portal.bottom-nav />

</div>
