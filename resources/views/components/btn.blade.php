@props([
    'variant' => 'primary',
    'size'    => 'md',
    'type'    => 'button',
    'href'    => null,
])

@php
$variantClass = match($variant) {
    'primary'   => 'bg-orange-500 hover:bg-orange-600 text-white',
    'secondary' => 'bg-white border border-slate-200 hover:bg-slate-50 text-slate-700',
    'danger'    => 'bg-red-500 hover:bg-red-600 text-white',
    'ghost'     => 'hover:bg-slate-100 text-slate-500',
    default     => 'bg-orange-500 hover:bg-orange-600 text-white',
};
$sizeClass = match($size) {
    'sm'  => 'text-xs px-3 py-1.5',
    'md'  => 'text-sm px-4 py-2',
    'lg'  => 'text-base px-5 py-2.5',
    default => 'text-sm px-4 py-2',
};
$base = "inline-flex items-center gap-2 font-medium rounded-lg transition-colors $variantClass $sizeClass";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $base]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $base]) }}>{{ $slot }}</button>
@endif
