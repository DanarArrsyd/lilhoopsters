<x-card class="mb-4">
    <div class="flex items-center gap-2 mb-4">
        <span class="w-7 h-7 rounded-lg bg-navy/8 text-navy flex items-center justify-center shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
        </span>
        <p class="text-xs font-bold uppercase tracking-wide text-muted">{{ __('messages.portal.home.quick_actions') }}</p>
    </div>

    <div class="grid grid-cols-4 gap-2">
        <a href="{{ route('parent.enroll') }}" class="flex flex-col items-center gap-1.5 text-center group">
            <span class="w-12 h-12 rounded-2xl bg-navy/8 text-navy flex items-center justify-center group-hover:bg-navy/15 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
            </span>
            <span class="text-[11px] font-semibold text-ink leading-tight">{{ __('messages.portal.home.enroll_package') }}</span>
        </a>
        <a href="{{ route('parent.leaves') }}" class="flex flex-col items-center gap-1.5 text-center group">
            <span class="w-12 h-12 rounded-2xl bg-navy/8 text-navy flex items-center justify-center group-hover:bg-navy/15 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </span>
            <span class="text-[11px] font-semibold text-ink leading-tight">{{ __('messages.portal.home.leave_request') }}</span>
        </a>
        <a href="{{ route('parent.makeup') }}" class="flex flex-col items-center gap-1.5 text-center group">
            <span class="w-12 h-12 rounded-2xl bg-navy/8 text-navy flex items-center justify-center group-hover:bg-navy/15 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </span>
            <span class="text-[11px] font-semibold text-ink leading-tight">{{ __('messages.portal.home.makeup_class') }}</span>
        </a>
        <a href="{{ route('parent.private') }}" class="flex flex-col items-center gap-1.5 text-center group">
            <span class="w-12 h-12 rounded-2xl bg-navy/8 text-navy flex items-center justify-center group-hover:bg-navy/15 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </span>
            <span class="text-[11px] font-semibold text-ink leading-tight">{{ __('messages.portal.home.private_session') }}</span>
        </a>
    </div>
</x-card>
