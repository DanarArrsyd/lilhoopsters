@props(['label', 'count' => 0])

@php
    // Auto-open if any child link is currently active
    $hasActive = str_contains((string) $slot, 'bg-navy text-off font-semibold');
@endphp

<div x-data="{ open: {{ $hasActive ? 'true' : 'false' }} }" class="mt-3">
    <button type="button"
            @click="open = !open"
            class="w-full flex items-center justify-between px-3 py-1.5 group">
        <span class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-muted group-hover:text-navy transition-colors sidebar-brand">
            {{ $label }}
            @if ((int) $count > 0)
                <span class="bg-[#DC2626] text-off text-[10px] font-bold rounded-full px-1.5 py-0.5 min-w-[18px] text-center leading-none normal-case tracking-normal">
                    {{ $count > 99 ? '99+' : $count }}
                </span>
            @endif
        </span>
        <svg class="w-3.5 h-3.5 text-muted group-hover:text-navy transition-transform duration-200"
             :class="open ? 'rotate-0' : '-rotate-90'"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div x-show="open"
         x-transition:enter="transition-all duration-200 ease-out"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition-all duration-150 ease-in"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-1"
         class="mt-0.5 space-y-0.5">
        {{ $slot }}
    </div>
</div>
