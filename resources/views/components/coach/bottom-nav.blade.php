@php
    $isActive = fn(string ...$routes) => collect($routes)->contains(fn($r) => request()->routeIs($r));
@endphp

<nav class="fixed bottom-0 inset-x-0 z-40 bg-surface border-t border-line flex items-stretch lg:hidden"
     style="padding-bottom: env(safe-area-inset-bottom)">
    <a href="{{ route('coach.dashboard') }}"
       class="flex-1 flex flex-col items-center gap-1 py-2.5 {{ $isActive('coach.dashboard') ? 'text-navy' : 'text-faint' }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
        <span class="text-[11px] font-semibold">{{ __('messages.nav.dashboard') }}</span>
    </a>
    <a href="{{ route('coach.qr-scanner') }}"
       class="flex-1 flex flex-col items-center gap-1 py-2.5 {{ $isActive('coach.qr-scanner', 'coach.checkin', 'coach.attendance', 'coach.roster') ? 'text-navy' : 'text-faint' }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
        </svg>
        <span class="text-[11px] font-semibold">{{ __('messages.nav.attendance') }}</span>
    </a>
    <a href="{{ route('coach.schedules') }}"
       class="flex-1 flex flex-col items-center gap-1 py-2.5 {{ $isActive('coach.schedules', 'coach.report-cards') ? 'text-navy' : 'text-faint' }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <span class="text-[11px] font-semibold">{{ __('messages.coach.nav.schedules') }}</span>
    </a>
    <a href="{{ route('coach.news') }}"
       class="flex-1 flex flex-col items-center gap-1 py-2.5 {{ $isActive('coach.news') ? 'text-navy' : 'text-faint' }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m0 0h2a2 2 0 012 2v9a2 2 0 11-4 0V5a2 2 0 00-2-2H5"/>
        </svg>
        <span class="text-[11px] font-semibold">{{ __('messages.nav.news') }}</span>
    </a>
</nav>
