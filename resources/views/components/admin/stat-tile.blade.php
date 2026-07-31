{{--
    One number on the admin dashboard, and the list it belongs to.

    Every tile is a link: a count that can't be acted on is trivia. Counts that
    represent a queue (something waiting on staff) carry an accent when they are
    above zero and go quiet at zero, so a clear board looks clear.
--}}
@props([
    'label',
    'value',
    'href',
    'hint'  => null,
    // queue = needs someone to act on it · scale = just how big the academy is
    'kind'  => 'scale',
])

@php
    $isQueue   = $kind === 'queue';
    $needsWork = $isQueue && (int) $value > 0;
@endphp

<a href="{{ $href }}"
   @class([
       'group block rounded-2xl border bg-surface px-4 py-4 transition-colors',
       'border-navy/25 hover:border-navy' => $needsWork,
       'border-line hover:border-navy/30' => ! $needsWork,
   ])>
    <p class="text-3xs font-bold uppercase tracking-widest text-muted">{{ $label }}</p>

    <p class="mt-2 flex items-baseline gap-2">
        <span @class([
            'tabular-nums text-3xl font-extrabold leading-none tabular-nums',
            'text-navy'  => $needsWork,
            'text-ink'   => ! $needsWork,
        ])>{{ $value }}</span>

        @if ($needsWork)
            <span class="w-1.5 h-1.5 rounded-full bg-[#B45309]" aria-hidden="true"></span>
        @endif
    </p>

    @if ($hint)
        <p class="mt-1.5 text-2xs text-faint group-hover:text-muted transition-colors">{{ $hint }}</p>
    @endif
</a>
