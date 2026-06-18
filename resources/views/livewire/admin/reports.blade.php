<div class="relative">
    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Reports</h2>
        <p class="text-sm text-muted">Revenue & payment analytics.</p>
    </div>

    {{-- Filter bar --}}
    <x-card class="mb-4" padding="p-4">
        <div class="flex flex-wrap gap-2 items-center">
            {{-- Presets --}}
            @foreach (['month' => 'Bulan Ini', '30d' => '30 Hari', 'year' => 'Tahun Ini'] as $key => $label)
                <button wire:click="setPreset('{{ $key }}')"
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg border transition-colors
                               {{ $preset === $key
                                   ? 'bg-navy text-off border-navy'
                                   : 'bg-off text-navy border-line hover:border-navy/40' }}">
                    {{ $label }}
                </button>
            @endforeach

            {{-- Custom date range --}}
            <div class="flex items-center gap-1.5">
                <input type="date" wire:model.live="dateFrom"
                       class="text-xs border border-line rounded-lg px-2 py-1.5 text-ink bg-off
                              focus:outline-none focus:ring-1 focus:ring-navy/30" />
                <span class="text-xs text-muted">–</span>
                <input type="date" wire:model.live="dateTo"
                       class="text-xs border border-line rounded-lg px-2 py-1.5 text-ink bg-off
                              focus:outline-none focus:ring-1 focus:ring-navy/30" />
            </div>

            {{-- Location filter --}}
            <div class="w-full sm:w-48">
                <x-select wire:model.live="filterLocation">
                    <option value="">All Locations</option>
                    @foreach ($locations as $loc)
                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                    @endforeach
                </x-select>
            </div>
        </div>
    </x-card>

    {{-- KPI cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        @php
            $kpiCards = [
                ['label' => 'Total Revenue',      'value' => 'Rp ' . number_format($kpis['total_revenue'], 0, ',', '.'), 'sub' => 'paid transactions'],
                ['label' => 'Transactions Paid',  'value' => number_format($kpis['paid_count']),            'sub' => 'completed'],
                ['label' => 'Avg per Transaction','value' => 'Rp ' . number_format($kpis['avg_transaction'], 0, ',', '.'), 'sub' => 'average value'],
                ['label' => 'Conversion Rate',    'value' => $kpis['conversion_rate'] . '%',                'sub' => 'paid ÷ all initiated'],
            ];
        @endphp

        @foreach ($kpiCards as $card)
            <x-card padding="p-4">
                <p class="text-xs text-muted mb-1">{{ $card['label'] }}</p>
                <p class="text-xl font-extrabold text-navy leading-tight">{{ $card['value'] }}</p>
                <p class="text-xs text-muted mt-0.5">{{ $card['sub'] }}</p>
            </x-card>
        @endforeach
    </div>
</div>
