<div x-data="{ open: false }" @click.outside="open = false" class="relative shrink-0">
    <button @click="open = !open" type="button"
            class="w-8 h-8 rounded-full bg-navy text-off flex items-center justify-center font-bold text-xs hover:bg-navy/90 transition-colors">
        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
    </button>

    <div x-show="open" x-cloak x-transition.opacity.duration.150ms
         class="absolute right-0 mt-2 w-52 bg-surface border border-line rounded-xl shadow-lg py-1.5 z-40">
        <div class="px-3.5 py-2 border-b border-line mb-1">
            <p class="text-sm font-semibold text-ink truncate">{{ auth()->user()->name }}</p>
            <p class="text-xs text-muted truncate">{{ auth()->user()->email }}</p>
        </div>

        <a href="{{ route('parent.profile') }}" @click="open = false"
           class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-ink hover:bg-off transition-colors">
            <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            {{ __('messages.portal.home.manage_profile') }}
        </a>

        <a href="{{ route('parent.guide') }}" @click="open = false"
           class="flex items-center gap-2.5 px-3.5 py-2 text-sm text-ink hover:bg-off transition-colors">
            <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s4.332.477 5.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            {{ __('messages.portal.home.user_guide') }}
        </a>

        <div class="border-t border-line mt-1 pt-1">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-2.5 px-3.5 py-2 text-sm text-[#B91C1C] hover:bg-[#B91C1C]/5 transition-colors text-left">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    {{ __('messages.portal.home.sign_out') }}
                </button>
            </form>
        </div>
    </div>
</div>
