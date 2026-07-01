<div class="flex min-h-[100dvh]">

    {{-- ── Sidebar (matches parent-portal) ── --}}
    <aside id="sidebar"
           class="fixed inset-y-0 left-0 z-50 w-60 bg-surface border-r border-line flex flex-col
                  transform -translate-x-full lg:translate-x-0 transition-transform duration-300">

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
            <x-parent-nav activeRoute="parent.home" />
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

    {{-- ── Main ── --}}
    <div class="flex-1 flex flex-col min-w-0 lg:ml-60">

        {{-- Topbar --}}
        <header class="h-14 bg-surface border-b border-line flex items-center px-4 gap-3 sticky top-0 z-30">
            <h1 class="flex-1 text-sm font-bold uppercase tracking-wide text-navy">{{ __('messages.portal.home.title') }}</h1>
            <livewire:locale-switcher />
            <livewire:notification-bell />
        </header>

        {{-- ── Scrollable content ── --}}
        <div class="flex-1 bg-off">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 pt-6 pb-24 lg:pb-20">

                <div class="mb-6">
                    <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">{{ __('messages.portal.home.title') }}</h2>
                    <p class="text-sm text-muted">{{ __('messages.portal.home.subtitle') }}</p>
                </div>

                @if ($children->isEmpty())
                    <x-empty-state
                        :title="__('messages.portal.home.empty_title')"
                        :description="__('messages.portal.home.empty_desc')">
                        <x-slot name="action">
                            <x-btn href="{{ route('parent.players') }}" variant="add">{{ __('messages.portal.home.add_player') }}</x-btn>
                        </x-slot>
                    </x-empty-state>
                @else
                    @if ($sectionFailed ?? false)
                        <div class="mb-4 px-4 py-3 rounded-xl bg-[#B91C1C]/10 text-[#B91C1C] text-sm flex items-center justify-between">
                            <span>{{ __('messages.portal.home.section_error') }}</span>
                            <button wire:click="$refresh" class="font-semibold underline shrink-0 ml-3">{{ __('messages.portal.home.retry') }}</button>
                        </div>
                    @endif
                    <x-portal.child-switcher :children="$children" :active-child-id="$activeChildId" />
                    <x-portal.event-banner :active-event="$activeEvent" />
                    <x-portal.schedule-card :next-session="$nextSession" :week-sessions="$weekSessions" />
                    <x-portal.payment-card :transactions="$transactions" :pending-amount="$pendingAmount" />
                    <x-portal.attendance-strip :attendance-counts="$attendanceCounts" />
                    <x-portal.quick-actions />
                    {{-- Sections added in Task 6 --}}
                @endif

            </div>{{-- /max-w-2xl --}}
        </div>{{-- /flex-1 bg-off --}}

    </div>{{-- /main --}}

    <x-portal.bottom-nav />

</div>{{-- /flex --}}
