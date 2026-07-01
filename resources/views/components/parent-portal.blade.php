<x-app>
    <x-slot name="title">{{ $title ?? 'Parent' }}</x-slot>

    <div class="flex flex-col min-h-[100dvh]">
        {{-- Topbar --}}
        <header class="h-14 bg-surface border-b border-line flex items-center px-4 gap-4 sticky top-0 z-30">
            <img src="{{ asset('basket_logo.jpeg') }}" alt="Lil' Hoopsters" class="w-8 h-8 rounded-lg object-cover shrink-0">
            <x-portal.top-nav />
            <div class="flex-1"></div>
            {{ $actions ?? '' }}
            <livewire:locale-switcher />
            <livewire:notification-bell />
        </header>

        {{-- Content --}}
        <main class="flex-1 bg-off p-6 pb-24 lg:pb-6">
            {{ $slot }}
        </main>

        <x-portal.bottom-nav />
    </div>

</x-app>
