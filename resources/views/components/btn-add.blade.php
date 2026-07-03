@props(['label' => null, 'href' => null])

@php
$cls = 'shrink-0 w-8 h-8 flex items-center justify-center rounded-lg border border-[#059669]/30 bg-[#059669]/10 text-[#059669] hover:bg-[#059669]/20 hover:border-[#059669]/50 active:scale-95 transition-all';
$icon = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $cls, 'title' => $label, 'aria-label' => $label]) }}>
        {!! $icon !!}
    </a>
@else
    <button type="button" {{ $attributes->merge(['class' => $cls, 'title' => $label, 'aria-label' => $label]) }}>
        {!! $icon !!}
    </button>
@endif
