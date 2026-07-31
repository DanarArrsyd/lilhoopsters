{{--
    Two tiers, not one.

    Every block on every page used to be the same white rounded box with the same
    border and the same shadow, which meant a page had no hierarchy — the thing
    you came for and the thing you rarely touch looked identical. `tone` picks
    the tier:

      raised (default) — the page's primary surfaces. Unchanged, so existing
                         call sites keep their look.
      flat             — supporting blocks. Same white as raised, because the
                         page tint is already off-white and a tinted card would
                         disappear into it — the difference is the dropped
                         shadow and the softer border, so it reads as sitting on
                         the page rather than above it.
--}}
@props(['title' => null, 'padding' => 'p-6', 'tone' => 'raised'])

@php
    $toneClass = $tone === 'flat'
        ? 'bg-surface rounded-2xl border border-line/70'
        : 'bg-surface rounded-2xl border border-line shadow-sm';
@endphp

<div {{ $attributes->merge(['class' => $toneClass]) }}>
    @if ($title)
        <div class="flex items-center justify-between px-6 py-4 border-b border-line">
            <h3 class="text-sm font-bold uppercase tracking-wide text-navy">{{ $title }}</h3>
            {{ $action ?? '' }}
        </div>
    @endif
    <div class="{{ $padding }}">
        {{ $slot }}
    </div>
</div>
