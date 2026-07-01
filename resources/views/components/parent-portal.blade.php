<x-app>
    <x-slot name="title">{{ $title ?? 'Parent' }}</x-slot>

    <div class="flex min-h-[100dvh]"
         x-data="{ collapsed: false }"
         x-init="collapsed = localStorage.getItem('sidebarCollapsed') === '1'; $watch('collapsed', v => localStorage.setItem('sidebarCollapsed', v ? '1' : '0'))">
        {{-- Sidebar (desktop only — mobile uses the bottom nav) --}}
        <aside class="fixed inset-y-0 left-0 z-50 w-60 bg-surface border-r border-line hidden flex-col
                      transform transition-transform duration-300"
               :class="collapsed ? 'lg:-translate-x-full' : 'lg:flex lg:translate-x-0'">

            {{-- Logo --}}
            <div class="h-16 flex items-center gap-3 px-4 border-b border-line">
                <img src="{{ asset('basket_logo.jpeg') }}" alt="Lil' Hoopsters" class="w-9 h-9 rounded-xl object-cover shrink-0">
                <div>
                    <p class="text-navy font-extrabold text-sm uppercase tracking-tight leading-tight">Lil' Hoopsters</p>
                    <p class="text-faint text-[10px] uppercase tracking-wide">Parent Portal</p>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                {{ $navigation ?? '' }}
            </nav>

            {{-- User --}}
            <div class="border-t border-line p-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 bg-navy rounded-full flex items-center justify-center text-off font-bold text-sm shrink-0">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-ink text-sm font-semibold truncate">{{ auth()->user()->name }}</p>
                        <p class="text-muted text-xs truncate">{{ auth()->user()->email }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-muted hover:text-[#B91C1C] transition-colors p-1" title="Sign out">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Main --}}
        <div class="flex-1 flex flex-col min-w-0 transition-[margin] duration-300"
             :class="collapsed ? 'lg:ml-0' : 'lg:ml-60'">
            {{-- Topbar --}}
            <header class="h-14 bg-surface border-b border-line flex items-center px-4 gap-4 sticky top-0 z-30">
                <button type="button" @click="collapsed = !collapsed" title="Toggle menu"
                        class="hidden lg:block p-2 rounded-lg hover:bg-off text-muted">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <div class="flex-1">
                    <h1 class="text-sm font-bold uppercase tracking-wide text-navy">{{ $title ?? 'Dashboard' }}</h1>
                </div>
                {{ $actions ?? '' }}
                <livewire:locale-switcher />
                <livewire:notification-bell />
            </header>

            {{-- Content --}}
            <main class="flex-1 bg-off p-6 pb-24 lg:pb-6">
                {{ $slot }}
            </main>
        </div>

        <x-portal.bottom-nav />
    </div>

</x-app>
