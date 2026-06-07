@props(['status'])

@php
$classes = match($status) {
    'approved', 'active', 'present', 'paid', 'published' => 'bg-green-100 text-green-700',
    'pending', 'warning'                                  => 'bg-amber-100 text-amber-700',
    'rejected', 'inactive', 'no_show', 'danger'           => 'bg-red-100 text-red-700',
    'sick', 'info'                                        => 'bg-blue-100 text-blue-700',
    'permit'                                              => 'bg-purple-100 text-purple-700',
    'make_up'                                             => 'bg-orange-100 text-orange-700',
    'auto_approved'                                       => 'bg-teal-100 text-teal-700',
    'draft', 'expired', 'unregistered'                    => 'bg-slate-100 text-slate-500',
    default                                               => 'bg-slate-100 text-slate-500',
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide $classes"]) }}>
    {{ $slot }}
</span>
