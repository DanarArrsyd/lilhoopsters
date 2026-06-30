@props(['nextSession', 'weekSessions'])

<x-card class="mb-4">
    <p class="text-xs font-bold uppercase tracking-wide text-muted mb-2">{{ __('messages.portal.home.next_session') }}</p>

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
