<x-app>
    <x-slot name="title">{{ $title ?? 'Admin' }}</x-slot>

    {{--
        Desktop gets a persistent left sidebar; below lg the topbar + drawer stay
        as they were. The admin panel carries 24 pages across 6 sections — that
        never fit a topbar without burying everything two clicks deep in
        dropdowns, and it cost the operator any sense of where they are.

        Both the sidebar and the drawer render the same <x-admin-nav>, so the
        badge composer bound to components.admin-nav feeds both.
    --}}
    {{-- `collapsed` is desktop only and remembered between pages; `mobileOpen`
         is the transient drawer and deliberately is not. Reading localStorage
         in x-data rather than x-init means the width is already right the
         first time Alpine paints the bound class, instead of snapping shut a
         frame later. --}}
    <div class="min-h-[100dvh] lg:flex"
         x-data="{
             mobileOpen: false,
             collapsed: localStorage.getItem('adminSidebarCollapsed') === '1',
             toggle() { this.collapsed = !this.collapsed; localStorage.setItem('adminSidebarCollapsed', this.collapsed ? '1' : '0') }
         }">

        {{-- ── Sidebar (lg and up) ──
             Collapsing animates the width rather than sliding the panel out,
             so <main> reflows into the space instead of the two moving
             independently. The inner column keeps a fixed w-64 so the nav does
             not reflow while that width animates. --}}
        <aside class="hidden lg:flex lg:flex-col shrink-0 sticky top-0 h-[100dvh] bg-surface overflow-hidden
                      transition-[width] duration-300 ease-out motion-reduce:transition-none"
               :class="collapsed ? 'w-0 border-r-0' : 'w-64 border-r border-line'">
            <div class="w-64 shrink-0 flex flex-col h-full">
                <a href="{{ route('admin.dashboard') }}"
                   class="h-14 shrink-0 flex items-center gap-2.5 px-4 border-b border-line hover:bg-off transition-colors">
                    <img src="{{ asset('basket_logo.jpeg') }}" alt="" class="w-8 h-8 rounded-lg object-cover shrink-0">
                    <span class="sidebar-brand text-sm font-extrabold uppercase tracking-tight text-navy truncate">
                        Lil' Hoopsters
                    </span>
                </a>

                {{-- Its own scroll container: long nav must never push the viewport --}}
                <nav class="flex-1 overflow-y-auto px-2 py-3" aria-label="{{ __('messages.admin.panel') }}">
                    <x-admin-nav />
                </nav>
            </div>
        </aside>

        {{-- ── Content column ──
             min-w-0 so a wide table scrolls inside <main> instead of stretching
             the flex row and pushing the sidebar off-screen. --}}
        <div class="flex-1 min-w-0 flex flex-col">

            <header class="h-14 bg-surface border-b border-line flex items-center justify-between px-4 gap-4 sticky top-0 z-30">
                <div class="flex items-center gap-3 min-w-0">
                    {{-- Below lg this opens the drawer; at lg and up the same
                         control collapses the sidebar. One button, one place. --}}
                    <button type="button"
                            @click="window.innerWidth >= 1024 ? toggle() : mobileOpen = true"
                            class="p-2 -ml-2 rounded-lg hover:bg-off text-muted transition-colors"
                            :aria-expanded="window.innerWidth >= 1024 ? String(!collapsed) : String(mobileOpen)"
                            :aria-label="collapsed ? '{{ __('messages.admin.show_menu') }}' : '{{ __('messages.admin.hide_menu') }}'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    {{-- The mark lives in the sidebar when it is open, so bring it
                         back here once that is hidden — otherwise a collapsed
                         desktop loses the brand entirely. --}}
                    <a href="{{ route('admin.dashboard') }}" class="block shrink-0" :class="!collapsed && 'lg:hidden'">
                        <img src="{{ asset('basket_logo.jpeg') }}" alt="Lil' Hoopsters" class="w-8 h-8 rounded-lg object-cover">
                    </a>
                </div>

                <div class="flex items-center justify-end gap-4 ml-auto">
                    <div class="hidden lg:flex items-center gap-4">
                        <livewire:locale-switcher />
                        <livewire:notification-bell />
                    </div>
                    <x-admin.avatar-menu />
                </div>
            </header>

            {{-- Mobile drawer --}}
            <x-admin.mobile-drawer x-show="mobileOpen" @click.outside="mobileOpen = false" :title="__('messages.admin.panel')">
                <x-admin-nav />
            </x-admin.mobile-drawer>

            <main class="admin-main flex-1 bg-off p-4 sm:p-6">
                {{ $slot }}
            </main>
        </div>
    </div>

</x-app>
