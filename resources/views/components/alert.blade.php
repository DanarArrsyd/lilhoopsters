@props(['type' => 'success', 'dismissible' => false])

@php
$classes = match($type) {
    'success' => 'border-l-4 border-green-500 bg-green-50 text-green-800',
    'error'   => 'border-l-4 border-red-500 bg-red-50 text-red-800',
    'warning' => 'border-l-4 border-amber-500 bg-amber-50 text-amber-800',
    'info'    => 'border-l-4 border-blue-500 bg-blue-50 text-blue-800',
    default   => 'border-l-4 border-green-500 bg-green-50 text-green-800',
};
@endphp

<div {{ $attributes->merge(['class' => "rounded-lg p-4 $classes text-sm"]) }}>
    {{ $slot }}
</div>
