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

    {{-- Revenue over time --}}
    <x-card padding="p-4" class="mb-6">
        <h3 class="text-sm font-bold text-navy uppercase tracking-wide mb-4">Revenue Over Time
            <span class="font-normal normal-case text-muted">({{ $bucketMode === 'daily' ? 'daily' : 'monthly' }})</span>
        </h3>

        @php
            $chartData  = $chart;
            $maxAmount  = collect($chartData)->max('amount') ?: 1;
            $chartH     = 120;   // px — SVG inner height for bars
            $barCount   = count($chartData);
            $svgW       = max($barCount * 28, 300);
            $barW       = max(14, intdiv($svgW, max($barCount, 1)) - 4);
            $labelStep  = max(1, (int) ceil($barCount / 12)); // show at most 12 labels
        @endphp

        @if ($barCount === 0 || $maxAmount === 0)
            <x-empty-state>No paid transactions in this period.</x-empty-state>
        @else
            <div class="overflow-x-auto relative" x-data="{ tooltip: null, tooltipX: 0, tooltipY: 0 }"
                 x-on:mousemove="tooltipX = $event.offsetX; tooltipY = $event.offsetY">
                <svg viewBox="0 0 {{ $svgW }} {{ $chartH + 36 }}"
                     width="100%" preserveAspectRatio="none"
                     style="min-width:{{ min($svgW, 300) }}px; height:{{ $chartH + 36 }}px;"
                     xmlns="http://www.w3.org/2000/svg">

                    {{-- Gridlines (4 lines) --}}
                    @foreach ([0.25, 0.5, 0.75, 1.0] as $frac)
                        @php $y = $chartH - ($frac * $chartH) @endphp
                        <line x1="0" y1="{{ $y }}" x2="{{ $svgW }}" y2="{{ $y }}"
                              stroke="#e5e7eb" stroke-width="1"/>
                        <text x="2" y="{{ $y - 2 }}" font-size="8" fill="#9ca3af">
                            Rp {{ number_format($maxAmount * $frac / 1000, 0, ',', '.') }}k
                        </text>
                    @endforeach

                    {{-- Bars --}}
                    @foreach ($chartData as $i => $bucket)
                        @php
                            $barH    = (int) round($bucket['amount'] / $maxAmount * $chartH);
                            $barX    = $i * ($barW + 4) + 2;
                            $barY    = $chartH - $barH;
                            $labelId = "tip-{$i}";
                        @endphp

                        <g x-on:mouseenter="tooltip='{{ addslashes($bucket['label']) }}: Rp {{ number_format($bucket['amount'], 0, ',', '.') }}'"
                           x-on:mouseleave="tooltip=null"
                           style="cursor:pointer">
                            <rect x="{{ $barX }}" y="{{ $barY }}"
                                  width="{{ $barW }}" height="{{ max($barH, 1) }}"
                                  rx="2" fill="var(--color-navy, #1e3a5f)" opacity="0.85"/>
                        </g>

                        {{-- X-axis label (pruned) --}}
                        @if ($i % $labelStep === 0)
                            <text x="{{ $barX + $barW / 2 }}" y="{{ $chartH + 14 }}"
                                  text-anchor="middle" font-size="8" fill="#6b7280">
                                {{ $bucket['label'] }}
                            </text>
                        @endif
                    @endforeach
                </svg>

                {{-- Tooltip --}}
                <div x-show="tooltip !== null"
                     x-cloak
                     x-bind:style="'left:' + tooltipX + 'px; top:' + (tooltipY - 28) + 'px'"
                     class="absolute pointer-events-none bg-navy text-off text-[10px] font-medium
                            px-2 py-1 rounded shadow z-10 whitespace-nowrap -translate-x-1/2"
                     x-text="tooltip">
                </div>
            </div>
        @endif
    </x-card>

    {{-- Two-col breakdowns --}}
    @php
        $typeMeta = [
            'registration' => ['label' => 'Registration', 'class' => 'bg-[#1D4ED8]/10 text-[#1D4ED8]', 'bar' => 'bg-[#1D4ED8]'],
            'regular'      => ['label' => 'Regular',      'class' => 'bg-navy/10 text-navy',           'bar' => 'bg-navy'],
            'drop_in'      => ['label' => 'Drop-in',      'class' => 'bg-[#B45309]/10 text-[#B45309]', 'bar' => 'bg-[#B45309]'],
            'private'      => ['label' => 'Private',      'class' => 'bg-[#7C3AED]/10 text-[#7C3AED]', 'bar' => 'bg-[#7C3AED]'],
        ];
        $maxTypeRev     = $byType->max('revenue')     ?: 1;
        $maxLocationRev = $byLocation->max('revenue') ?: 1;
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        {{-- By package type --}}
        <x-card padding="p-4">
            <h3 class="text-sm font-bold text-navy uppercase tracking-wide mb-4">By Package Type</h3>
            @forelse ($byType as $type => $data)
                @php $meta = $typeMeta[$type] ?? ['label' => $type, 'class' => 'bg-line text-ink', 'bar' => 'bg-ink']; @endphp
                <div class="mb-3">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $meta['class'] }}">
                            {{ $meta['label'] }}
                        </span>
                        <div class="text-right">
                            <span class="text-xs font-bold text-ink tabular-nums">
                                Rp {{ number_format($data['revenue'], 0, ',', '.') }}
                            </span>
                            <span class="text-xs text-muted ml-1">({{ $data['count'] }})</span>
                        </div>
                    </div>
                    <div class="h-1.5 bg-line rounded-full overflow-hidden">
                        <div class="{{ $meta['bar'] }} h-full rounded-full"
                             style="width:{{ round($data['revenue'] / $maxTypeRev * 100) }}%">
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-xs text-muted">No data in this period.</p>
            @endforelse
        </x-card>

        {{-- By location --}}
        <x-card padding="p-4">
            <h3 class="text-sm font-bold text-navy uppercase tracking-wide mb-4">By Location</h3>
            @forelse ($byLocation as $locName => $data)
                <div class="mb-3">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-semibold text-ink truncate max-w-[55%]">{{ $locName }}</span>
                        <div class="text-right">
                            <span class="text-xs font-bold text-ink tabular-nums">
                                Rp {{ number_format($data['revenue'], 0, ',', '.') }}
                            </span>
                            <span class="text-xs text-muted ml-1">({{ $data['count'] }})</span>
                        </div>
                    </div>
                    <div class="h-1.5 bg-line rounded-full overflow-hidden">
                        <div class="bg-navy h-full rounded-full"
                             style="width:{{ round($data['revenue'] / $maxLocationRev * 100) }}%">
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-xs text-muted">No data in this period.</p>
            @endforelse
        </x-card>
    </div>

    {{-- Top packages table --}}
    <x-card padding="p-0" class="mb-6 overflow-hidden">
        <div class="px-4 pt-4 pb-3 border-b border-line">
            <h3 class="text-sm font-bold text-navy uppercase tracking-wide">Top Packages</h3>
            <p class="text-xs text-muted">Top 10 by revenue in period.</p>
        </div>
        @if ($topPackages->isEmpty())
            <div class="p-4"><x-empty-state>No paid transactions in this period.</x-empty-state></div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-line bg-faint">
                            <th class="px-4 py-2.5 text-left font-semibold text-muted uppercase tracking-wide">Package</th>
                            <th class="px-4 py-2.5 text-left font-semibold text-muted uppercase tracking-wide">Type</th>
                            <th class="px-4 py-2.5 text-left font-semibold text-muted uppercase tracking-wide">Location</th>
                            <th class="px-4 py-2.5 text-right font-semibold text-muted uppercase tracking-wide">Units Sold</th>
                            <th class="px-4 py-2.5 text-right font-semibold text-muted uppercase tracking-wide">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($topPackages as $pkg)
                            @php $meta = $typeMeta[$pkg['type']] ?? ['label' => $pkg['type'], 'class' => 'bg-line text-ink']; @endphp
                            <tr class="hover:bg-faint transition-colors">
                                <td class="px-4 py-3 font-medium text-ink">{{ $pkg['name'] }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $meta['class'] }}">
                                        {{ $meta['label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-muted">{{ $pkg['location'] }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-ink">{{ $pkg['units'] }}</td>
                                <td class="px-4 py-3 text-right tabular-nums font-bold text-ink">
                                    Rp {{ number_format($pkg['revenue'], 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-card>

    {{-- Payment funnel --}}
    <x-card padding="p-4" class="mb-6">
        <h3 class="text-sm font-bold text-navy uppercase tracking-wide mb-4">Payment Funnel
            <span class="font-normal normal-case text-muted">(by initiation date)</span>
        </h3>

        @php
            $funnelMeta = [
                'paid'     => ['label' => 'Paid',     'bg' => 'bg-green-500',  'text' => 'text-green-700',  'light' => 'bg-green-50'],
                'pending'  => ['label' => 'Pending',  'bg' => 'bg-amber-400',  'text' => 'text-amber-700',  'light' => 'bg-amber-50'],
                'rejected' => ['label' => 'Rejected', 'bg' => 'bg-red-500',    'text' => 'text-red-700',    'light' => 'bg-red-50'],
                'expired'  => ['label' => 'Expired',  'bg' => 'bg-slate-400',  'text' => 'text-slate-600',  'light' => 'bg-slate-50'],
            ];
        @endphp

        @if ($funnelTotal === 0)
            <p class="text-xs text-muted">No transactions initiated in this period.</p>
        @else
            <div class="flex h-4 rounded-full overflow-hidden mb-3">
                @foreach ($funnelMeta as $status => $meta)
                    @php $count = $funnel[$status] ?? 0; $pct = $funnelTotal > 0 ? round($count / $funnelTotal * 100) : 0; @endphp
                    @if ($pct > 0)
                        <div class="{{ $meta['bg'] }}" style="width:{{ $pct }}%" title="{{ $meta['label'] }}: {{ $count }}"></div>
                    @endif
                @endforeach
            </div>

            <div class="flex flex-wrap gap-3">
                @foreach ($funnelMeta as $status => $meta)
                    @php $count = $funnel[$status] ?? 0; $pct = $funnelTotal > 0 ? round($count / $funnelTotal * 100, 1) : 0; @endphp
                    <div class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg {{ $meta['light'] }}">
                        <span class="w-2 h-2 rounded-full {{ $meta['bg'] }} shrink-0"></span>
                        <span class="{{ $meta['text'] }} text-xs font-semibold">{{ $meta['label'] }}</span>
                        <span class="{{ $meta['text'] }} text-xs tabular-nums">{{ $count }} ({{ $pct }}%)</span>
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>
</div>
