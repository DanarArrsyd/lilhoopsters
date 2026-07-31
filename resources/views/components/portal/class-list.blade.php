@props(['classes', 'classesTab' => 'regular'])

@php
    $filtered = $classes->filter(fn ($c) => $c['type'] === $classesTab);
@endphp

<x-card class="mb-4">
    <div class="flex items-center gap-1 mb-4 border-b border-line -mx-6 px-6">
        <button wire:click="selectClassesTab('regular')"
                class="px-1 pb-3 -mb-px text-sm font-semibold border-b-2 transition-colors {{ $classesTab === 'regular' ? 'border-navy text-navy' : 'border-transparent text-faint hover:text-muted' }}">
            {{ __('messages.portal.home.regular_classes') }}
        </button>
        <button wire:click="selectClassesTab('private')"
                class="ml-5 px-1 pb-3 -mb-px text-sm font-semibold border-b-2 transition-colors {{ $classesTab === 'private' ? 'border-navy text-navy' : 'border-transparent text-faint hover:text-muted' }}">
            {{ __('messages.portal.home.private_classes') }}
        </button>
    </div>

    @if ($filtered->isEmpty())
        <p class="text-sm text-muted py-4 text-center">{{ __('messages.portal.home.no_classes') }}</p>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach ($filtered as $c)
                <div class="border border-line rounded-xl p-4">
                    <p class="font-semibold text-ink text-sm">{{ $c['program'] }}</p>
                    <p class="text-xs text-muted mt-0.5">{{ $c['coach'] ?? __('messages.portal.home.no_coach') }}</p>
                    <p class="text-xs text-faint mt-1">
                        {{ ucfirst($c['day']) }}, {{ \Illuminate\Support\Carbon::parse($c['start'])->format('H:i') }}–{{ \Illuminate\Support\Carbon::parse($c['end'])->format('H:i') }}
                        · {{ $c['location'] }}
                    </p>

                    <div class="flex items-center justify-between mt-3 mb-1">
                        <span class="text-2xs text-muted">{{ __('messages.portal.home.attendance_label', ['a' => $c['attended'], 'b' => $c['total']]) }}</span>
                        <span class="text-2xs font-bold text-navy">{{ $c['pct'] }}%</span>
                    </div>
                    <div class="h-1.5 bg-line rounded-full overflow-hidden">
                        <div class="h-full bg-navy rounded-full" style="width: {{ $c['pct'] }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-card>
