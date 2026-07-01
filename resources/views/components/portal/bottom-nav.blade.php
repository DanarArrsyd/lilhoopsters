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
    <a href="{{ route('parent.news') }}"
       class="flex-1 flex flex-col items-center gap-1 py-2.5 {{ request()->routeIs('parent.news') ? 'text-navy' : 'text-faint' }}">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m0 0h2a2 2 0 012 2v9a2 2 0 11-4 0V5a2 2 0 00-2-2H5"/>
        </svg>
        <span class="text-[11px] font-semibold">{{ __('messages.nav.news') }}</span>
    </a>
</nav>
