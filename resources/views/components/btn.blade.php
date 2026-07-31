@props([
    'variant' => 'primary',
    'size'    => 'md',
    'type'    => 'button',
    'href'    => null,
])

@php
$isSm = $size === 'sm';

// Small buttons = table row actions. Render as light tinted chips (flat,
// compact, colour-coded) instead of heavy solid pills so rows don't get
// crowded. Larger sizes keep the solid CTA look used in modals/headers.
$variantClass = $isSm
    ? match($variant) {
        'edit'      => 'bg-[#1D4ED8]/10 text-[#1D4ED8] hover:bg-[#1D4ED8]/20',
        'danger'    => 'bg-[#DC2626]/10 text-[#DC2626] hover:bg-[#DC2626]/20',
        'warning'   => 'bg-[#D97706]/10 text-[#D97706] hover:bg-[#D97706]/20',
        'success'   => 'bg-[#15803D]/10 text-[#15803D] hover:bg-[#15803D]/20',
        'add'       => 'bg-[#059669]/10 text-[#059669] hover:bg-[#059669]/20',
        'purple'    => 'bg-[#7C3AED]/10 text-[#7C3AED] hover:bg-[#7C3AED]/20',
        'secondary' => 'text-muted hover:text-navy hover:bg-navy/5',
        default     => 'bg-navy/5 text-navy hover:bg-navy/10',
    }
    : match($variant) {
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
    'sm'  => 'text-2xs px-2 py-1',
    'md'  => 'text-sm px-4 py-2.5',
    'lg'  => 'text-sm px-5 py-3',
    default => 'text-sm px-4 py-2.5',
};

// Compact, flat shape for sm; bold elevated CTA shape for larger sizes.
$shape = $isSm
    ? 'gap-1 font-semibold rounded-lg'
    : 'gap-1.5 font-bold uppercase tracking-wide rounded-xl shadow-sm hover:-translate-y-0.5';

$base = "inline-flex items-center justify-center transition-all duration-150 cursor-pointer select-none
         active:scale-[0.97] active:translate-y-0
         focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-1
         disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none
         $shape $variantClass $sizeClass";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $base]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $base]) }}>{{ $slot }}</button>
@endif
