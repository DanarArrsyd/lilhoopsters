<div class="relative">

    {{-- ══════════════════════════════════════════════
         FILTER BAR
    ══════════════════════════════════════════════ --}}
    <div class="bg-surface border border-line rounded-xl px-4 py-3 mb-5 flex flex-wrap gap-3 items-center">

        {{-- Preset pill group --}}
        <div class="flex items-center bg-off rounded-lg p-1 gap-0.5">
            @foreach (['month' => 'This Month', '30d' => '30 Days', 'year' => 'This Year'] as $key => $label)
                <button wire:click="setPreset('{{ $key }}')"
                        class="px-3 py-1 text-xs font-semibold rounded-md transition-all duration-150
                               {{ $preset === $key
                                   ? 'bg-navy text-off shadow-sm'
                                   : 'text-muted hover:text-ink hover:bg-line/60' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="w-px h-5 bg-line hidden sm:block shrink-0"></div>

        {{-- Date range --}}
        <div class="flex items-center gap-2">
            <span class="text-xs text-muted hidden sm:inline">From</span>
            <input type="date" wire:model.live="dateFrom"
                   class="text-xs border rounded-lg px-2.5 py-1.5 text-ink bg-off focus:outline-none
                          focus:ring-2 focus:ring-navy/20 focus:border-navy/40
                          {{ $preset === 'custom' ? 'border-navy/40' : 'border-line' }}" />
            <span class="text-xs text-muted">–</span>
            <input type="date" wire:model.live="dateTo"
                   class="text-xs border rounded-lg px-2.5 py-1.5 text-ink bg-off focus:outline-none
                          focus:ring-2 focus:ring-navy/20 focus:border-navy/40
                          {{ $preset === 'custom' ? 'border-navy/40' : 'border-line' }}" />
        </div>

        <div class="w-px h-5 bg-line hidden sm:block shrink-0"></div>

        {{-- Location --}}
        <div class="min-w-36">
            <x-select wire:model.live="filterLocation">
                <option value="">All Locations</option>
                @foreach ($locations as $loc)
                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                @endforeach
            </x-select>
        </div>

        {{-- Active range badge --}}
        <span class="text-xs font-medium text-muted bg-off border border-line rounded-lg px-3 py-1.5 hidden lg:inline-flex items-center gap-1.5 ml-auto">
            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} — {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
        </span>

        {{-- Livewire loading --}}
        <div wire:loading wire:target="setPreset,dateFrom,dateTo,filterLocation"
             class="flex items-center gap-1.5 text-xs text-muted lg:ml-0 ml-auto">
            <svg class="w-3.5 h-3.5 animate-spin text-navy" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
            </svg>
            Loading…
        </div>

        {{-- Export CSV --}}
        <button wire:click="export"
                wire:loading.attr="disabled" wire:target="export"
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-off bg-navy
                       hover:bg-navy/90 disabled:opacity-50 rounded-lg px-3 py-1.5 transition-colors shrink-0">
            <svg wire:loading.remove wire:target="export" class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            <svg wire:loading wire:target="export" class="w-3.5 h-3.5 shrink-0 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
            </svg>
            Export CSV
        </button>
    </div>

    {{-- Content fades while a filter reload is in flight --}}
    <div wire:loading.class="opacity-40 pointer-events-none"
         wire:target="setPreset,dateFrom,dateTo,filterLocation"
         class="transition-opacity duration-200">

    {{-- ══════════════════════════════════════════════
         KPI CARDS
    ══════════════════════════════════════════════ --}}
    @php
        $kpiCards = [
            [
                'key'     => 'total_revenue',
                'label'   => 'Total Revenue',
                'value'   => 'Rp ' . number_format($kpis['total_revenue'], 0, ',', '.'),
                'sub'     => 'paid transactions',
                'ib'      => 'bg-[#15803D]/10',
                'it'      => 'text-[#15803D]',
                'bar'     => 'bg-[#15803D]',
                'good'    => 'up',
                'icon'    => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 8v1m0 0c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'key'     => 'paid_count',
                'label'   => 'Paid Transactions',
                'value'   => number_format($kpis['paid_count']),
                'sub'     => 'completed transactions',
                'ib'      => 'bg-navy/10',
                'it'      => 'text-navy',
                'bar'     => 'bg-navy',
                'good'    => 'up',
                'icon'    => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'key'     => 'avg_transaction',
                'label'   => 'Average Transaction',
                'value'   => 'Rp ' . number_format($kpis['avg_transaction'], 0, ',', '.'),
                'sub'     => 'average value',
                'ib'      => 'bg-[#1D4ED8]/10',
                'it'      => 'text-[#1D4ED8]',
                'bar'     => 'bg-[#1D4ED8]',
                'good'    => 'up',
                'icon'    => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
            ],
            [
                'key'     => 'conversion_rate',
                'label'   => 'Conversion Rate',
                'value'   => $kpis['conversion_rate'] . '%',
                'sub'     => 'paid ÷ all initiated',
                'ib'      => 'bg-[#B45309]/10',
                'it'      => 'text-[#B45309]',
                'bar'     => 'bg-[#B45309]',
                'good'    => 'up',
                'icon'    => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
            ],
        ];
    @endphp

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        @foreach ($kpiCards as $card)
            <div class="bg-surface border border-line rounded-xl px-4 pt-4 pb-3 flex flex-col gap-3">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-[11px] font-semibold text-muted uppercase tracking-wide leading-tight">{{ $card['label'] }}</p>
                    <div class="w-7 h-7 rounded-lg {{ $card['ib'] }} flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5 {{ $card['it'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}"/>
                        </svg>
                    </div>
                </div>
                <div>
                    <p class="text-xl font-extrabold text-navy leading-none tracking-tight">{{ $card['value'] }}</p>
                    <div class="flex items-center gap-1.5 mt-1.5">
                        @php $d = $deltas[$card['key']] ?? null; @endphp
                        @if ($d)
                            @php
                                $isGood = $d['dir'] === $card['good'];
                                $isFlat = $d['dir'] === 'flat';
                                $badge  = $isFlat
                                    ? 'bg-off text-muted border-line'
                                    : ($isGood ? 'bg-[#15803D]/10 text-[#15803D] border-[#15803D]/20'
                                               : 'bg-[#B91C1C]/10 text-[#B91C1C] border-[#B91C1C]/20');
                            @endphp
                            <span class="inline-flex items-center gap-0.5 text-[10px] font-bold px-1.5 py-0.5 rounded-md border {{ $badge }}">
                                @if (! $isFlat)
                                    <svg class="w-2.5 h-2.5 shrink-0 {{ $d['dir'] === 'down' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/>
                                    </svg>
                                @endif
                                {{ $d['pct'] }}%
                            </span>
                            <span class="text-[10px] text-faint">vs previous period</span>
                        @else
                            <p class="text-[11px] text-muted">{{ $card['sub'] }}</p>
                        @endif
                    </div>
                </div>
                <div class="h-0.5 w-10 {{ $card['bar'] }} rounded-full"></div>
            </div>
        @endforeach
    </div>

    {{-- ══════════════════════════════════════════════
         REVENUE OVER TIME — CHART.JS
    ══════════════════════════════════════════════ --}}
    <div class="bg-surface border border-line rounded-xl mb-5 overflow-hidden">
        <div class="px-5 py-3.5 border-b border-line flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="text-sm font-extrabold text-navy uppercase tracking-wide">Revenue Over Time</h3>
                <p class="text-xs text-muted mt-0.5">
                    Based on paid_at · {{ $bucketMode === 'daily' ? 'Daily' : 'Monthly' }}
                </p>
            </div>
            <div class="flex items-center gap-4 text-xs text-muted">
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-4 h-3 rounded bg-navy"></span>
                    Above average
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-4 h-3 rounded bg-[#94a3b8]"></span>
                    Below average
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-4 border-t-2 border-dashed border-[#B45309]"></span>
                    Rata-rata
                </span>
            </div>
        </div>

        @if (empty($chartLabels) || array_sum($chartAmounts) === 0)
            <div class="py-20 flex flex-col items-center gap-3 text-center">
                <div class="w-14 h-14 rounded-xl bg-off border border-line flex items-center justify-center">
                    <svg class="w-7 h-7 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-ink">No data yet</p>
                    <p class="text-xs text-muted mt-0.5">No paid transactions in this period.</p>
                </div>
            </div>
        @else
            <div wire:ignore
                 x-data="revenueChart(@js($chartLabels), @js($chartAmounts), {{ $chartAvg }})"
                 class="px-5 py-5">
                <div style="height: 300px; position: relative;">
                    <canvas></canvas>
                </div>
            </div>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════
         BREAKDOWN: BY TYPE + BY LOCATION
    ══════════════════════════════════════════════ --}}
    @php
        $typeMeta = [
            'registration' => ['label' => 'Registration', 'class' => 'bg-[#1D4ED8]/10 text-[#1D4ED8]', 'bar' => 'bg-[#1D4ED8]'],
            'regular'      => ['label' => 'Regular',      'class' => 'bg-navy/10 text-navy',           'bar' => 'bg-navy'],
            'drop_in'      => ['label' => 'Drop-in',      'class' => 'bg-[#B45309]/10 text-[#B45309]', 'bar' => 'bg-[#B45309]'],
            'private'      => ['label' => 'Private',      'class' => 'bg-[#7C3AED]/10 text-[#7C3AED]', 'bar' => 'bg-[#7C3AED]'],
        ];
        $maxTypeRev     = $byType->max('revenue')     ?: 1;
        $maxLocationRev = $byLocation->max('revenue') ?: 1;
        $totalTypeRev   = $byType->sum('revenue')     ?: 1;
        $totalLocRev    = $byLocation->sum('revenue') ?: 1;
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-5">

        {{-- By package type --}}
        <div class="bg-surface border border-line rounded-xl overflow-hidden">
            <div class="px-4 py-3 border-b border-line">
                <h3 class="text-sm font-extrabold text-navy uppercase tracking-wide">By Package Type</h3>
            </div>
            <div class="p-4 space-y-4">
                @forelse ($byType as $type => $data)
                    @php
                        $meta = $typeMeta[$type] ?? ['label' => $type, 'class' => 'bg-line text-ink', 'bar' => 'bg-ink'];
                        $pct  = round($data['revenue'] / $totalTypeRev * 100);
                    @endphp
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $meta['class'] }}">
                                    {{ $meta['label'] }}
                                </span>
                                <span class="text-xs text-muted">{{ $data['count'] }} txn</span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-bold text-ink tabular-nums">
                                    Rp {{ number_format($data['revenue'], 0, ',', '.') }}
                                </span>
                                <span class="text-xs text-muted ml-1.5">{{ $pct }}%</span>
                            </div>
                        </div>
                        <div class="h-2.5 bg-off rounded-full overflow-hidden border border-line">
                            <div class="{{ $meta['bar'] }} h-full rounded-full transition-all duration-500"
                                 style="width:{{ round($data['revenue'] / $maxTypeRev * 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-muted py-6 text-center">No data in this period.</p>
                @endforelse
            </div>
        </div>

        {{-- By location --}}
        <div class="bg-surface border border-line rounded-xl overflow-hidden">
            <div class="px-4 py-3 border-b border-line">
                <h3 class="text-sm font-extrabold text-navy uppercase tracking-wide">By Location</h3>
            </div>
            <div class="p-4 space-y-4">
                @forelse ($byLocation as $locName => $data)
                    @php $pct = round($data['revenue'] / $totalLocRev * 100); @endphp
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <div class="w-1.5 h-1.5 rounded-full bg-navy shrink-0"></div>
                                <span class="text-xs font-semibold text-ink truncate max-w-[40%]">{{ $locName }}</span>
                                <span class="text-xs text-muted">{{ $data['count'] }} txn</span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-bold text-ink tabular-nums">
                                    Rp {{ number_format($data['revenue'], 0, ',', '.') }}
                                </span>
                                <span class="text-xs text-muted ml-1.5">{{ $pct }}%</span>
                            </div>
                        </div>
                        <div class="h-2.5 bg-off rounded-full overflow-hidden border border-line">
                            <div class="bg-navy h-full rounded-full transition-all duration-500"
                                 style="width:{{ round($data['revenue'] / $maxLocationRev * 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-muted py-6 text-center">No data in this period.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════
         TOP PACKAGES TABLE
    ══════════════════════════════════════════════ --}}
    <div class="bg-surface border border-line rounded-xl mb-5 overflow-hidden">
        <div class="px-5 py-3 border-b border-line">
            <h3 class="text-sm font-extrabold text-navy uppercase tracking-wide">Top Packages</h3>
            <p class="text-xs text-muted mt-0.5">Top 10 packages by revenue.</p>
        </div>

        @if ($topPackages->isEmpty())
            <div class="py-12 flex flex-col items-center gap-3 text-center">
                <div class="w-12 h-12 rounded-xl bg-off border border-line flex items-center justify-center">
                    <svg class="w-6 h-6 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-ink">No packages sold yet</p>
                    <p class="text-xs text-muted">No paid transactions in this period.</p>
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-line bg-off/50">
                            <th class="px-5 py-2.5 text-left font-semibold text-muted uppercase tracking-wide text-[10px] w-8">#</th>
                            <th class="px-5 py-2.5 text-left font-semibold text-muted uppercase tracking-wide text-[10px]">Package</th>
                            <th class="px-5 py-2.5 text-left font-semibold text-muted uppercase tracking-wide text-[10px]">Tipe</th>
                            <th class="px-5 py-2.5 text-left font-semibold text-muted uppercase tracking-wide text-[10px] hidden md:table-cell">Location</th>
                            <th class="px-5 py-2.5 text-right font-semibold text-muted uppercase tracking-wide text-[10px]">Unit</th>
                            <th class="px-5 py-2.5 text-right font-semibold text-muted uppercase tracking-wide text-[10px]">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($topPackages as $idx => $pkg)
                            @php $meta = $typeMeta[$pkg['type']] ?? ['label' => $pkg['type'], 'class' => 'bg-line text-ink']; @endphp
                            <tr class="hover:bg-off/60 transition-colors group">
                                <td class="px-5 py-3 text-muted tabular-nums font-semibold">{{ $idx + 1 }}</td>
                                <td class="px-5 py-3 font-semibold text-ink group-hover:text-navy transition-colors">{{ $pkg['name'] }}</td>
                                <td class="px-5 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $meta['class'] }}">
                                        {{ $meta['label'] }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-muted hidden md:table-cell">{{ $pkg['location'] }}</td>
                                <td class="px-5 py-3 text-right tabular-nums text-muted">{{ $pkg['units'] }}</td>
                                <td class="px-5 py-3 text-right tabular-nums font-bold text-ink">
                                    Rp {{ number_format($pkg['revenue'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════
         PAYMENT FUNNEL
    ══════════════════════════════════════════════ --}}
    @php
        $funnelMeta = [
            'paid'     => ['label' => 'Paid',     'bg' => 'bg-green-500',  'text' => 'text-green-700',  'light' => 'bg-green-50',  'border' => 'border-green-200'],
            'pending'  => ['label' => 'Pending',  'bg' => 'bg-amber-400',  'text' => 'text-amber-700',  'light' => 'bg-amber-50',  'border' => 'border-amber-200'],
            'rejected' => ['label' => 'Rejected', 'bg' => 'bg-red-500',    'text' => 'text-red-700',    'light' => 'bg-red-50',    'border' => 'border-red-200'],
            'expired'  => ['label' => 'Expired',  'bg' => 'bg-slate-400',  'text' => 'text-slate-600',  'light' => 'bg-slate-50',  'border' => 'border-slate-200'],
        ];
    @endphp

    <div class="bg-surface border border-line rounded-xl mb-5 overflow-hidden">
        <div class="px-5 py-3 border-b border-line">
            <h3 class="text-sm font-extrabold text-navy uppercase tracking-wide">Payment Funnel</h3>
            <p class="text-xs text-muted mt-0.5">Based on transaction initiation date (created_at).</p>
        </div>

        <div class="p-5">
            @if ($funnelTotal === 0)
                <div class="py-6 text-center">
                    <p class="text-sm font-bold text-ink">No transactions yet</p>
                    <p class="text-xs text-muted mt-1">No transactions in this period.</p>
                </div>
            @else
                {{-- Proportional bar --}}
                <div class="flex h-8 rounded-xl overflow-hidden mb-5" style="gap:1px; background:#E6E3DC;">
                    @foreach ($funnelMeta as $status => $meta)
                        @php $count = $funnel[$status] ?? 0; $pct = round($count / $funnelTotal * 100); @endphp
                        @if ($pct > 0)
                            <div class="{{ $meta['bg'] }} flex items-center justify-center"
                                 style="width:{{ $pct }}%"
                                 title="{{ $meta['label'] }}: {{ $count }} ({{ $pct }}%)">
                                @if ($pct >= 7)
                                    <span class="text-white text-[10px] font-bold select-none">{{ $pct }}%</span>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>

                {{-- Status cards --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    @foreach ($funnelMeta as $status => $meta)
                        @php
                            $count = $funnel[$status] ?? 0;
                            $pct   = $funnelTotal > 0 ? round($count / $funnelTotal * 100, 1) : 0;
                        @endphp
                        <div class="border rounded-xl p-4 {{ $meta['light'] }} {{ $meta['border'] }}">
                            <div class="flex items-center gap-1.5 mb-3">
                                <span class="w-2.5 h-2.5 rounded-full {{ $meta['bg'] }} shrink-0"></span>
                                <span class="{{ $meta['text'] }} text-[11px] font-bold uppercase tracking-wide">{{ $meta['label'] }}</span>
                            </div>
                            <p class="{{ $meta['text'] }} text-3xl font-extrabold leading-none tabular-nums">{{ $count }}</p>
                            <p class="{{ $meta['text'] }} text-xs mt-2 font-medium">{{ $pct }}% of total</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    </div>{{-- /content fade wrapper --}}

</div>
