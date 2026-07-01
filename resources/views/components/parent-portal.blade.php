<x-app>
    <x-slot name="title">{{ $title ?? 'Parent' }}</x-slot>

    <div class="flex flex-col min-h-[100dvh]">
        {{-- Topbar --}}
        <header class="h-14 bg-surface border-b border-line flex justify-between lg:grid lg:grid-cols-[1fr_auto_1fr] items-center px-4 gap-4 sticky top-0 z-30">
            <div class="flex items-center min-w-0">
                <img src="{{ asset('basket_logo.jpeg') }}" alt="Lil' Hoopsters" class="w-8 h-8 rounded-lg object-cover shrink-0">
            </div>
            <x-portal.top-nav />
            <div class="flex items-center justify-end gap-4">
                {{ $actions ?? '' }}
                <livewire:locale-switcher />
                <livewire:notification-bell />
                <x-portal.avatar-menu />
            </div>
        </header>

        {{-- Content --}}
        <main class="flex-1 bg-off p-6 pb-24 lg:pb-6">
            {{ $slot }}
        </main>

        <x-portal.bottom-nav />
    </div>

</x-app>
