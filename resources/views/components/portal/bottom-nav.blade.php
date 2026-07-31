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
    <a href="{{ route('parent.players') }}"
       class="flex-1 flex flex-col items-center gap-1 py-2.5 {{ request()->routeIs('parent.players') ? 'text-navy' : 'text-faint' }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        <span class="text-[11px] font-semibold">{{ __('messages.nav.players') }}</span>
    </a>
    <a href="{{ route('parent.events') }}"
       class="flex-1 flex flex-col items-center gap-1 py-2.5 {{ request()->routeIs('parent.events') ? 'text-navy' : 'text-faint' }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <span class="text-[11px] font-semibold">{{ __('messages.nav.events') }}</span>
    </a>
    <a href="{{ route('parent.news') }}"
       class="flex-1 flex flex-col items-center gap-1 py-2.5 {{ request()->routeIs('parent.news') ? 'text-navy' : 'text-faint' }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m0 0h2a2 2 0 012 2v9a2 2 0 11-4 0V5a2 2 0 00-2-2H5"/>
        </svg>
        <span class="text-[11px] font-semibold">{{ __('messages.nav.news') }}</span>
    </a>

    {{-- Everything the five slots can't hold. Before this, these seven pages were
         reachable only through the Quick Actions card on Home — so a parent on
         Payments had to go back home to file a leave request. --}}
    <x-nav-more-sheet :active="request()->routeIs('parent.enroll', 'parent.private', 'parent.payments', 'parent.leaves', 'parent.makeup', 'parent.attendance', 'parent.report-cards')">

        <x-nav-more-item :href="route('parent.payments')" :label="__('messages.nav.payments')" :active="request()->routeIs('parent.payments')">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
        </x-nav-more-item>

        <x-nav-more-item :href="route('parent.attendance')" :label="__('messages.nav.attendance')" :active="request()->routeIs('parent.attendance')">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
        </x-nav-more-item>

        <x-nav-more-item :href="route('parent.report-cards')" :label="__('messages.nav.report_cards')" :active="request()->routeIs('parent.report-cards')">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </x-nav-more-item>

        <x-nav-more-item :href="route('parent.enroll')" :label="__('messages.nav.enroll')" :active="request()->routeIs('parent.enroll')">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </x-nav-more-item>

        <x-nav-more-item :href="route('parent.private')" :label="__('messages.nav.private')" :active="request()->routeIs('parent.private')">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
        </x-nav-more-item>

        <x-nav-more-item :href="route('parent.leaves')" :label="__('messages.nav.leaves')" :active="request()->routeIs('parent.leaves')">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </x-nav-more-item>

        <x-nav-more-item :href="route('parent.makeup')" :label="__('messages.nav.makeup')" :active="request()->routeIs('parent.makeup')">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
        </x-nav-more-item>
    </x-nav-more-sheet>
</nav>
