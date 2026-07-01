<x-app>
    <x-slot name="title">{{ $title ?? 'Parent' }}</x-slot>

    <div class="flex min-h-[100dvh]"
         x-data="{ collapsed: false, mobileOpen: false, toggle() { window.innerWidth >= 1024 ? (this.collapsed = !this.collapsed) : (this.mobileOpen = !this.mobileOpen) } }"
         x-init="collapsed = localStorage.getItem('sidebarCollapsed') === '1'; $watch('collapsed', v => localStorage.setItem('sidebarCollapsed', v ? '1' : '0'))">
        {{-- Sidebar --}}
        <aside id="sidebar"
               class="fixed inset-y-0 left-0 z-50 w-60 bg-surface border-r border-line flex flex-col
                      transform transition-transform duration-300"
               :class="{ 'translate-x-0': mobileOpen, '-translate-x-full': !mobileOpen, 'lg:translate-x-0': !collapsed, 'lg:-translate-x-full': collapsed }">

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

        {{-- Overlay mobile --}}
        <div x-show="mobileOpen" x-transition.opacity @click="mobileOpen = false"
             class="fixed inset-0 bg-navy/40 z-40 lg:hidden" style="display:none"></div>

        {{-- Main --}}
        <div class="flex-1 flex flex-col min-w-0 transition-[margin] duration-300"
             :class="collapsed ? 'lg:ml-0' : 'lg:ml-60'">
            {{-- Topbar --}}
            <header class="h-14 bg-surface border-b border-line flex items-center px-4 gap-4 sticky top-0 z-30">
                <button type="button" @click="toggle()" title="Toggle menu"
                        class="p-2 rounded-lg hover:bg-off text-muted">
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

        {{-- Mobile bottom nav --}}
        <nav class="fixed bottom-0 inset-x-0 z-40 bg-surface border-t border-line flex items-stretch lg:hidden"
             style="padding-bottom: env(safe-area-inset-bottom)">
            <a href="{{ route('parent.home') }}"
               class="flex-1 flex flex-col items-center gap-1 py-2.5 {{ request()->routeIs('parent.home', 'parent.dashboard') ? 'text-navy' : 'text-faint' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span class="text-[11px] font-semibold">{{ __('messages.nav.dashboard') }}</span>
            </a>
            <a href="{{ route('parent.news') }}"
               class="flex-1 flex flex-col items-center gap-1 py-2.5 {{ request()->routeIs('parent.news') ? 'text-navy' : 'text-faint' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m0 0h2a2 2 0 012 2v9a2 2 0 11-4 0V5a2 2 0 00-2-2H5"/>
                </svg>
                <span class="text-[11px] font-semibold">{{ __('messages.nav.news') }}</span>
            </a>
            <a href="{{ route('parent.profile') }}"
               class="flex-1 flex flex-col items-center gap-1 py-2.5 {{ request()->routeIs('parent.profile') ? 'text-navy' : 'text-faint' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span class="text-[11px] font-semibold">{{ __('messages.nav.profile') }}</span>
            </a>
        </nav>
    </div>

</x-app>
