@php
    $isActive = fn(string ...$routes) => collect($routes)->contains(fn($r) => request()->routeIs($r));
@endphp

<nav class="hidden lg:flex items-center gap-1">
    <a href="{{ route('parent.home') }}"
       class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold transition-colors {{ $isActive('parent.home', 'parent.dashboard') ? 'bg-navy/8 text-navy' : 'text-muted hover:text-navy hover:bg-off' }}">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
        {{ __('messages.nav.dashboard') }}
    </a>
    <a href="{{ route('parent.players') }}"
       class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold transition-colors {{ $isActive('parent.players') ? 'bg-navy/8 text-navy' : 'text-muted hover:text-navy hover:bg-off' }}">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        {{ __('messages.nav.players') }}
    </a>
    <a href="{{ route('parent.events') }}"
       class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold transition-colors {{ $isActive('parent.events') ? 'bg-navy/8 text-navy' : 'text-muted hover:text-navy hover:bg-off' }}">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        {{ __('messages.nav.events') }}
    </a>
    <a href="{{ route('parent.news') }}"
       class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold transition-colors {{ $isActive('parent.news') ? 'bg-navy/8 text-navy' : 'text-muted hover:text-navy hover:bg-off' }}">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m0 0h2a2 2 0 012 2v9a2 2 0 11-4 0V5a2 2 0 00-2-2H5"/>
        </svg>
        {{ __('messages.nav.news') }}
    </a>

    {{-- Desktop counterpart of the bottom-nav More sheet: the same destinations
         that were previously only on the Home quick-actions card. --}}
    <x-nav-dropdown :label="__('messages.nav.more')"
                    data-has-active="{{ $isActive('parent.payments', 'parent.attendance', 'parent.report-cards', 'parent.enroll', 'parent.private', 'parent.leaves', 'parent.makeup') ? 'true' : 'false' }}">
        <x-sidebar-link :href="route('parent.payments')" :active="request()->routeIs('parent.payments')">{{ __('messages.nav.payments') }}</x-sidebar-link>
        <x-sidebar-link :href="route('parent.attendance')" :active="request()->routeIs('parent.attendance')">{{ __('messages.nav.attendance') }}</x-sidebar-link>
        <x-sidebar-link :href="route('parent.report-cards')" :active="request()->routeIs('parent.report-cards')">{{ __('messages.nav.report_cards') }}</x-sidebar-link>
        <x-sidebar-link :href="route('parent.enroll')" :active="request()->routeIs('parent.enroll')">{{ __('messages.nav.enroll') }}</x-sidebar-link>
        <x-sidebar-link :href="route('parent.private')" :active="request()->routeIs('parent.private')">{{ __('messages.nav.private') }}</x-sidebar-link>
        <x-sidebar-link :href="route('parent.leaves')" :active="request()->routeIs('parent.leaves')">{{ __('messages.nav.leaves') }}</x-sidebar-link>
        <x-sidebar-link :href="route('parent.makeup')" :active="request()->routeIs('parent.makeup')">{{ __('messages.nav.makeup') }}</x-sidebar-link>
    </x-nav-dropdown>
</nav>
