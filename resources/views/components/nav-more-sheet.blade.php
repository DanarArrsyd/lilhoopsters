{{--
    Bottom-nav overflow: the tab bar holds five slots, and both portals have more
    destinations than that. Everything that doesn't fit lives here instead of
    being reachable only from a card on the home screen.

    Renders as the last tab. The sheet itself is teleported to <body> so it sits
    above the fixed tab bar and outside any transformed ancestor.

    Usage:
        <x-nav-more-sheet :active="request()->routeIs('parent.payments', ...)">
            <x-nav-more-item :href="route('parent.payments')" :label="__('...')" :active="...">
                <svg .../>
            </x-nav-more-item>
        </x-nav-more-sheet>
--}}
@props([
    'active' => false,
    'label'  => null,
])

@php $label = $label ?? __('messages.nav.more'); @endphp

<div x-data="{ open: false }" class="flex-1 flex" @keydown.escape.window="open = false">

    <button type="button"
            @click="open = true"
            :aria-expanded="open"
            aria-haspopup="dialog"
            class="flex-1 flex flex-col items-center gap-1 py-2.5 {{ $active ? 'text-navy' : 'text-faint' }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
        <span class="text-[11px] font-semibold">{{ $label }}</span>
    </button>

    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-50 lg:hidden">

            <div x-show="open"
                 x-transition:enter="transition-opacity ease-out duration-200"
                 x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-in duration-150"
                 x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 @click="open = false"
                 class="absolute inset-0 bg-navy/40"></div>

            <div x-show="open"
                 role="dialog" aria-modal="true" aria-label="{{ $label }}"
                 x-transition:enter="transition-transform ease-out duration-250"
                 x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
                 x-transition:leave="transition-transform ease-in duration-200"
                 x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
                 class="absolute inset-x-0 bottom-0 rounded-t-2xl bg-surface border-t border-line px-4 pt-3
                        pb-[calc(1.25rem+env(safe-area-inset-bottom))] shadow-2xl shadow-navy/20">

                <div class="mx-auto mb-4 h-1 w-10 rounded-full bg-line"></div>

                <p class="mb-3 text-[10px] font-bold uppercase tracking-widest text-muted">{{ $label }}</p>

                <div class="grid grid-cols-3 gap-2">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </template>
</div>
