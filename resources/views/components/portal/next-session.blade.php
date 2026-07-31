{{--
    The one thing a parent opens this app to find out: when does my child train
    next, and where.

    It was already computed and passed to the view, and then never rendered — so
    the answer lived as the smallest line inside the week card ("Next: Saturday")
    while an eight-tile action grid took the top of the page. This gives it the
    first line instead.
--}}
@props(['session' => null])

@php
    $date     = $session ? \Illuminate\Support\Carbon::parse($session['date']) : null;
    $start    = $session ? \Illuminate\Support\Carbon::parse($session['start']) : null;
    $end      = $session ? \Illuminate\Support\Carbon::parse($session['end']) : null;
    $daysAway = $date ? now()->startOfDay()->diffInDays($date->copy()->startOfDay(), false) : null;

    $when = match (true) {
        $daysAway === null => null,
        $daysAway <= 0     => __('messages.portal.home.when_today'),
        $daysAway === 1    => __('messages.portal.home.when_tomorrow'),
        default            => trans_choice('messages.portal.home.when_in_days', $daysAway, ['count' => $daysAway]),
    };
@endphp

@if ($session)
    <div class="rounded-2xl bg-navy text-off px-5 py-5 sm:px-6 sm:py-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-widest text-off/50">
                    {{ __('messages.portal.home.next_session') }}
                </p>

                <p class="mt-2 text-xl sm:text-2xl font-extrabold tracking-tight">
                    {{ $date->translatedFormat('l') }}
                    <span class="font-numeric">{{ $start->format('H:i') }}–{{ $end->format('H:i') }}</span>
                </p>

                <p class="mt-1.5 text-sm text-off/75">
                    {{ $session['program'] }}
                    <span class="text-off/40">·</span>
                    {{ $session['location'] }}
                    @if ($session['coach'])
                        <span class="text-off/40">·</span> {{ $session['coach'] }}
                    @endif
                </p>
            </div>

            <div class="flex items-center gap-3 shrink-0">
                <span class="rounded-full bg-off/10 px-3 py-1.5 text-[11px] font-bold uppercase tracking-wide">
                    {{ $when }}
                </span>
                @if ($session['location_maps_url'])
                    <a href="{{ $session['location_maps_url'] }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1.5 rounded-xl bg-off px-3 py-2 text-xs font-bold text-navy
                              hover:bg-white transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        {{ __('messages.portal.home.directions') }}
                    </a>
                @endif
            </div>
        </div>
    </div>
@else
    {{-- No enrolment, or none left in the next two weeks: say so plainly and
         point at the one action that changes it. --}}
    <div class="rounded-2xl border border-line bg-surface px-5 py-5 sm:px-6">
        <p class="text-[10px] font-bold uppercase tracking-widest text-faint">
            {{ __('messages.portal.home.next_session') }}
        </p>
        <p class="mt-2 text-sm font-semibold text-ink">{{ __('messages.portal.home.no_upcoming') }}</p>
        <a href="{{ route('parent.enroll') }}"
           class="mt-3 inline-flex items-center gap-1.5 text-xs font-bold text-navy hover:underline underline-offset-2">
            {{ __('messages.portal.home.enroll_package') }}
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
@endif
