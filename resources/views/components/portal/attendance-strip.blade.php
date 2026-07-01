@props(['attendanceCounts'])

@php
    $present = $attendanceCounts->get('present', 0);
    $absent  = $attendanceCounts->get('no_show', 0);
@endphp

<x-card class="mb-4">
    <div class="flex items-center gap-2 mb-3">
        <span class="w-7 h-7 rounded-lg bg-navy/8 text-navy flex items-center justify-center shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
        </span>
        <p class="text-xs font-bold uppercase tracking-wide text-muted">{{ __('messages.portal.home.attendance_this_month') }}</p>
    </div>
    <div class="flex gap-6">
        <div>
            <p class="font-mono text-2xl text-[#15803D] font-medium">{{ $present }}</p>
            <p class="text-xs text-muted">{{ __('messages.portal.home.present') }}</p>
        </div>
        <div>
            <p class="font-mono text-2xl text-[#B91C1C] font-medium">{{ $absent }}</p>
            <p class="text-xs text-muted">{{ __('messages.portal.home.absent') }}</p>
        </div>
    </div>
</x-card>
