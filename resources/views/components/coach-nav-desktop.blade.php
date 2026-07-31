@php
    $isActive = fn(string ...$routes) => collect($routes)->contains(fn($r) => request()->routeIs($r));
@endphp

<nav class="hidden lg:flex items-center gap-1">
    <a href="{{ route('coach.dashboard') }}"
       class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold transition-colors {{ $isActive('coach.dashboard') ? 'bg-navy/8 text-navy' : 'text-muted hover:text-navy hover:bg-off' }}">
        {{ __('messages.nav.dashboard') }}
    </a>
    <a href="{{ route('coach.news') }}"
       class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold transition-colors {{ $isActive('coach.news') ? 'bg-navy/8 text-navy' : 'text-muted hover:text-navy hover:bg-off' }}">
        {{ __('messages.nav.news') }}
    </a>

    <x-nav-dropdown :label="__('messages.nav.attendance')" data-has-active="{{ $isActive('coach.attendance','coach.qr-scanner','coach.checkin','coach.roster') ? 'true' : 'false' }}">
        <x-sidebar-link href="{{ route('coach.attendance') }}" :active="request()->routeIs('coach.attendance')">{{ __('messages.coach.nav.take_attendance') }}</x-sidebar-link>
        <x-sidebar-link href="{{ route('coach.qr-scanner') }}" :active="request()->routeIs('coach.qr-scanner')">{{ __('messages.coach.nav.qr_scanner') }}</x-sidebar-link>
        <x-sidebar-link href="{{ route('coach.checkin') }}" :active="request()->routeIs('coach.checkin')">{{ __('messages.coach.nav.checkin') }}</x-sidebar-link>
        <x-sidebar-link href="{{ route('coach.roster') }}" :active="request()->routeIs('coach.roster')">{{ __('messages.coach.nav.roster') }}</x-sidebar-link>
    </x-nav-dropdown>

    <x-nav-dropdown :label="__('messages.coach.nav.sessions_section')" data-has-active="{{ $isActive('coach.schedules','coach.report-cards') ? 'true' : 'false' }}">
        <x-sidebar-link href="{{ route('coach.schedules') }}" :active="request()->routeIs('coach.schedules')">{{ __('messages.coach.nav.schedules') }}</x-sidebar-link>
        <x-sidebar-link href="{{ route('coach.report-cards') }}" :active="request()->routeIs('coach.report-cards')">{{ __('messages.nav.report_cards') }}</x-sidebar-link>
    </x-nav-dropdown>
</nav>
