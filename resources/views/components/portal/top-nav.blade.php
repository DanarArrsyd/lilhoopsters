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
    <a href="{{ route('parent.news') }}"
       class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold transition-colors {{ $isActive('parent.news') ? 'bg-navy/8 text-navy' : 'text-muted hover:text-navy hover:bg-off' }}">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m0 0h2a2 2 0 012 2v9a2 2 0 11-4 0V5a2 2 0 00-2-2H5"/>
        </svg>
        {{ __('messages.nav.news') }}
    </a>
    <a href="{{ route('parent.profile') }}"
       class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold transition-colors {{ $isActive('parent.profile') ? 'bg-navy/8 text-navy' : 'text-muted hover:text-navy hover:bg-off' }}">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
        {{ __('messages.nav.profile') }}
    </a>
</nav>
