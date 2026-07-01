@props(['weekStrip', 'selectedDate' => null])

<x-card class="mb-4">
    <div class="flex items-center gap-2 mb-3">
        <span class="w-7 h-7 rounded-lg bg-navy/8 text-navy flex items-center justify-center shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </span>
        <p class="text-xs font-bold uppercase tracking-wide text-muted">{{ __('messages.portal.home.this_week') }}</p>
    </div>

    <div class="grid grid-cols-7 gap-1.5">
        @foreach ($weekStrip as $d)
            <button wire:click="selectDate('{{ $d['date'] }}')"
                    class="flex flex-col items-center gap-1.5 py-2 rounded-xl transition-colors {{ $selectedDate === $d['date'] ? 'bg-navy/8' : 'hover:bg-off' }}">
                <span class="text-[10px] font-semibold uppercase text-faint">{{ $d['label'] }}</span>
                <span @class([
                    'w-8 h-8 flex items-center justify-center rounded-full text-sm font-bold',
                    'bg-navy text-white' => $d['isToday'],
                    'text-ink'           => !$d['isToday'],
                ])>{{ $d['day'] }}</span>
                @if ($d['count'] > 0)
                    <span class="w-1.5 h-1.5 rounded-full bg-navy"></span>
                @else
                    <span class="w-1.5 h-1.5"></span>
                @endif
            </button>
        @endforeach
    </div>
</x-card>
