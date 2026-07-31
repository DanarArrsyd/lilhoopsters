@props(['status'])

@php
$classes = match($status) {
    'approved', 'active', 'present', 'paid', 'published', 'auto_approved' => 'bg-[#15803D]/10 text-[#15803D]',
    'pending', 'warning'                                  => 'bg-[#B45309]/10 text-[#B45309]',
    'rejected', 'inactive', 'no_show', 'danger'           => 'bg-[#B91C1C]/10 text-[#B91C1C]',
    'sick', 'info', 'permit'                              => 'bg-[#1D4ED8]/10 text-[#1D4ED8]',
    'make_up'                                             => 'bg-navy/10 text-navy',
    'draft', 'expired', 'unregistered'                    => 'bg-navy/5 text-muted',
    default                                               => 'bg-navy/5 text-muted',
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-2xs font-semibold uppercase tracking-wide $classes"]) }}>
    {{ $slot }}
</span>
