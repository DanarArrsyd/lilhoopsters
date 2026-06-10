@props(['type' => 'success', 'dismissible' => false])

@php
$classes = match($type) {
    'success' => 'border-l-4 border-[#15803D] bg-[#15803D]/8 text-[#15803D]',
    'error'   => 'border-l-4 border-[#B91C1C] bg-[#B91C1C]/8 text-[#B91C1C]',
    'warning' => 'border-l-4 border-[#B45309] bg-[#B45309]/8 text-[#B45309]',
    'info'    => 'border-l-4 border-[#1D4ED8] bg-[#1D4ED8]/8 text-[#1D4ED8]',
    default   => 'border-l-4 border-[#15803D] bg-[#15803D]/8 text-[#15803D]',
};
@endphp

<div {{ $attributes->merge(['class' => "rounded-lg p-4 $classes text-sm"]) }}>
    {{ $slot }}
</div>
