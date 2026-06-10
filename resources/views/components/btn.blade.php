@props([
    'variant' => 'primary',
    'size'    => 'md',
    'type'    => 'button',
    'href'    => null,
])

@php
$variantClass = match($variant) {
    'primary'   => 'bg-navy text-off hover:bg-navy-2',
    'secondary' => 'bg-transparent border border-navy text-navy hover:bg-navy/5',
    'ghost'     => 'bg-transparent text-navy hover:bg-navy/5',
    'danger'    => 'bg-[#DC2626] text-off hover:bg-[#B91C1C]',
    default     => 'bg-navy text-off hover:bg-navy-2',
};
$sizeClass = match($size) {
    'sm'  => 'text-xs px-3 py-2',
    'md'  => 'text-sm px-4 py-2.5',
    'lg'  => 'text-sm px-5 py-3',
    default => 'text-sm px-4 py-2.5',
};
$base = "inline-flex items-center justify-center gap-2 font-bold uppercase tracking-wide rounded-xl
         transition-colors active:translate-y-px select-none $variantClass $sizeClass";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $base]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $base]) }}>{{ $slot }}</button>
@endif
