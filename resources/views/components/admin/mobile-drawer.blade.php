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
