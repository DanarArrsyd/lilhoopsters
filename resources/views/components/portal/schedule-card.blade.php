@props(['nextSession', 'weekSessions'])

<x-card class="mb-4">
    <div class="flex items-center gap-2 mb-3">
        <span class="w-7 h-7 rounded-lg bg-navy/8 text-navy flex items-center justify-center shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </span>
        <p class="text-xs font-bold uppercase tracking-wide text-muted">{{ __('messages.portal.home.next_session') }}</p>
    </div>

    @if ($nextSession)
        <div class="flex items-start justify-between"
             x-data="{ open: false }">
            <div>
                <p class="text-base font-semibold text-ink">{{ $nextSession['program'] }}</p>
                <p class="text-sm text-muted">
                    {{ $nextSession['coach'] ?? __('messages.portal.home.no_coach') }} · {{ $nextSession['location'] }}
                </p>
                <p class="text-xs text-faint mt-1">{{ $nextSession['date']->translatedFormat('l, d M') }}</p>
            </div>
            <span class="font-mono text-sm text-navy font-medium shrink-0">
                {{ \Illuminate\Support\Carbon::parse($nextSession['start'])->format('H:i') }}
            </span>
        </div>

        @if ($weekSessions->isNotEmpty())
            <div x-data="{ open: false }" class="mt-3 pt-3 border-t border-line">
                <button @click="open = !open" class="text-xs font-semibold text-navy">
                    <span x-show="!open">{{ __('messages.portal.home.view_week') }}</span>
                    <span x-show="open" x-cloak>{{ __('messages.portal.home.hide_week') }}</span>
                </button>
                <div x-show="open" x-collapse x-cloak class="mt-3 space-y-2">
                    @foreach ($weekSessions as $day => $sessions)
                        @foreach ($sessions as $session)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-ink capitalize">{{ ucfirst($day) }} — {{ $session['program'] }}</span>
                                <span class="font-mono text-muted">{{ \Illuminate\Support\Carbon::parse($session['start'])->format('H:i') }}</span>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        @endif
    @else
        <p class="text-sm text-muted">{{ __('messages.portal.home.no_session') }}</p>
    @endif
</x-card>
