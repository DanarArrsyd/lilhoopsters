@props([
    'variant' => 'primary',
    'size'    => 'md',
    'type'    => 'button',
    'href'    => null,
])

@php
$variantClass = match($variant) {
    // Modal CTAs — save, create, submit
    'primary'   => 'bg-navy text-off shadow-sm hover:bg-[#1a2540] hover:shadow-lg focus-visible:ring-navy/30',
    // Approve, Verify, Activate, Publish, Confirm, Check-in
    'success'   => 'bg-[#15803D] text-white shadow-sm hover:bg-[#166534] hover:shadow-[0_8px_24px_rgba(21,128,61,0.35)] focus-visible:ring-[#15803D]/40',
    // Reject, Delete, Remove, Check-out
    'danger'    => 'bg-[#DC2626] text-white shadow-sm hover:bg-[#B91C1C] hover:shadow-[0_8px_24px_rgba(220,38,38,0.35)] focus-visible:ring-[#DC2626]/40',
    // Edit, Update, Modify
    'edit'      => 'bg-[#1D4ED8] text-white shadow-sm hover:bg-[#1E40AF] hover:shadow-[0_8px_24px_rgba(29,78,216,0.35)] focus-visible:ring-[#1D4ED8]/40',
    // Add, Create, New
    'add'       => 'bg-[#059669] text-white shadow-sm hover:bg-[#047857] hover:shadow-[0_8px_24px_rgba(5,150,105,0.35)] focus-visible:ring-[#059669]/40',
    // Deactivate, Hold, Warning
    'warning'   => 'bg-[#D97706] text-white shadow-sm hover:bg-[#B45309] hover:shadow-[0_8px_24px_rgba(217,119,6,0.35)] focus-visible:ring-[#D97706]/40',
    // Revoke, Undo, Rollback
    'purple'    => 'bg-[#7C3AED] text-white shadow-sm hover:bg-[#6D28D9] hover:shadow-[0_8px_24px_rgba(124,58,237,0.35)] focus-visible:ring-[#7C3AED]/40',
    // Cancel, Back, Close
    'secondary' => 'bg-transparent border border-navy/25 text-navy hover:border-navy/60 hover:bg-navy/[0.04] hover:shadow-sm focus-visible:ring-navy/20',
    // Subtle row actions
    'ghost'     => 'bg-navy/[0.05] text-navy hover:bg-navy/[0.1] hover:shadow-sm focus-visible:ring-navy/20',
    default     => 'bg-navy text-off shadow-sm hover:bg-[#1a2540] hover:shadow-lg focus-visible:ring-navy/30',
};

$sizeClass = match($size) {
    'sm'  => 'text-xs px-3 py-1.5',
    'md'  => 'text-sm px-4 py-2.5',
    'lg'  => 'text-sm px-5 py-3',
    default => 'text-sm px-4 py-2.5',
};

$base = "inline-flex items-center justify-center gap-1.5 font-bold uppercase tracking-wide rounded-xl
         transition-all duration-200 cursor-pointer select-none
         hover:-translate-y-0.5
         active:scale-[0.97] active:translate-y-0 active:shadow-none
         focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-1
         disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none
         $variantClass $sizeClass";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $base]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $base]) }}>{{ $slot }}</button>
@endif
