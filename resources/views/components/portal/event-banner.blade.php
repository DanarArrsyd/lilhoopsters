@props(['activeEvent'])

@if ($activeEvent)
    <div class="flex items-center gap-3 mb-4 px-4 py-3 rounded-xl bg-navy text-off">
        <span class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
            </svg>
        </span>
        <div>
            <p class="text-sm font-semibold">{{ $activeEvent->name }}</p>
            <p class="text-xs opacity-80">{{ __('messages.portal.home.event_open') }}</p>
        </div>
    </div>
@endif
