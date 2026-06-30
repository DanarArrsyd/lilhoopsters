@props(['attendanceCounts'])

@php
    $present = $attendanceCounts->get('present', 0);
    $absent  = $attendanceCounts->get('no_show', 0);
@endphp

<x-card class="mb-4">
    <p class="text-xs font-bold uppercase tracking-wide text-muted mb-2">{{ __('messages.portal.home.attendance_this_month') }}</p>
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
