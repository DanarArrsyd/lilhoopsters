<x-app>
    <x-slot name="title">{{ $title ?? 'Super Admin' }}</x-slot>

    <div class="flex flex-col min-h-[100dvh]" x-data="{ mobileOpen: false }">

        {{-- Topbar --}}
        <header class="h-14 bg-surface border-b border-line flex justify-between lg:grid lg:grid-cols-[1fr_auto_1fr] items-center px-4 gap-4 sticky top-0 z-30">
            <div class="flex items-center gap-3 min-w-0">
                <button type="button" @click="mobileOpen = true" class="lg:hidden p-2 -ml-2 rounded-lg hover:bg-off text-muted" aria-label="Menu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <img src="{{ asset('basket_logo.jpeg') }}" alt="Lil' Hoopsters" class="w-8 h-8 rounded-lg object-cover shrink-0">
            </div>
            <x-superadmin.top-nav />
            <div class="flex items-center justify-end gap-4">
                <div class="hidden lg:block">
                    <livewire:locale-switcher />
                </div>
                <x-superadmin.avatar-menu />
            </div>
        </header>

        {{-- Mobile drawer --}}
        <x-admin.mobile-drawer x-show="mobileOpen" @click.outside="mobileOpen = false" :title="__('messages.superadmin.panel')">
            <x-superadmin-nav />
        </x-admin.mobile-drawer>

        {{-- Content --}}
        <main class="flex-1 bg-off p-6">
            {{ $slot }}
        </main>
    </div>

</x-app>
