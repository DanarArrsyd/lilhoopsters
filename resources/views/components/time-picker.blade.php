{{--
    Time picker — hour : minute + AM/PM, in one bordered field.

    Reads as a single control rather than three loose inputs: the two selects sit
    on a shared baseline in the numeric face, the colon is a fixed pivot between
    them, and the meridiem is a segmented pair so the current value is visible
    without clicking (the old single toggle button only ever showed one state,
    which made "is that the value or the action?" ambiguous).

    Expects the Livewire component to expose {prefix}Hour, {prefix}Minute and a
    set{Prefix}Period(string) method — see App\Livewire\Admin\Schedules.
--}}
@props([
    'label',
    'prefix',                 // start | end
    'period',                 // current AM/PM value
    'error'    => null,
    'required' => true,
])

@php
    $hourModel   = $prefix . 'Hour';
    $minuteModel = $prefix . 'Minute';
    $setter      = 'set' . ucfirst($prefix) . 'Period';
    $minutes     = ['00','05','10','15','20','25','30','35','40','45','50','55'];
@endphp

<div class="space-y-1.5">
    <p class="block text-xs font-semibold uppercase tracking-wide text-navy">
        {{ $label }}
        @if ($required)<span class="text-danger ml-0.5">*</span>@endif
    </p>

    {{-- min-h matches the 46px of x-input / x-select so a time picker sitting in
         the same grid row as a normal field lines up exactly, not 2px off. --}}
    <div class="flex items-center gap-1.5 rounded-xl border bg-surface px-1.5 min-h-[2.875rem]
                transition-colors duration-150 focus-within:border-navy focus-within:ring-2 focus-within:ring-navy/15
                {{ $error ? 'border-danger' : 'border-line' }}">

        <div class="flex-1 min-w-0">
            <x-select variant="bare" :searchable="false"
                      wire:key="{{ $prefix }}-hour"
                      wire:model.live="{{ $hourModel }}"
                      trigger-class="tabular-nums text-base tracking-tight"
                      aria-label="{{ $label }} — hour">
                @foreach (range(1, 12) as $h)
                    <option value="{{ $h }}">{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}</option>
                @endforeach
            </x-select>
        </div>

        <span class="shrink-0 text-base font-bold text-faint select-none" aria-hidden="true">:</span>

        <div class="flex-1 min-w-0">
            <x-select variant="bare" :searchable="false"
                      wire:key="{{ $prefix }}-minute"
                      wire:model.live="{{ $minuteModel }}"
                      trigger-class="tabular-nums text-base tracking-tight"
                      aria-label="{{ $label }} — minute">
                @foreach ($minutes as $m)
                    <option value="{{ $m }}">{{ $m }}</option>
                @endforeach
            </x-select>
        </div>

        {{-- Meridiem: both options always visible, current one filled --}}
        <div class="shrink-0 flex items-center gap-0.5 rounded-lg bg-off p-0.5"
             role="group" aria-label="{{ $label }} — AM or PM">
            @foreach (['AM', 'PM'] as $meridiem)
                <button type="button"
                        wire:click="{{ $setter }}('{{ $meridiem }}')"
                        aria-pressed="{{ $period === $meridiem ? 'true' : 'false' }}"
                        @class([
                            'px-2.5 py-1.5 rounded-md text-2xs font-bold tracking-wide transition-colors duration-150',
                            'focus:outline-none focus-visible:ring-2 focus-visible:ring-navy/40',
                            'bg-navy text-off shadow-sm'            => $period === $meridiem,
                            'text-muted hover:text-navy hover:bg-surface' => $period !== $meridiem,
                        ])>
                    {{ $meridiem }}
                </button>
            @endforeach
        </div>
    </div>

    @if ($error)
        <p class="text-xs text-danger">{{ $error }}</p>
    @endif
</div>
