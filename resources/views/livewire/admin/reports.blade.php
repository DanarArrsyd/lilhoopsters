<div class="relative">

    {{-- Header --}}
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h2 class="text-xl font-extrabold uppercase tracking-tight text-navy">Revenue Reports</h2>
            <p class="text-sm text-muted mt-0.5">Paid transaction analytics.</p>
        </div>
        <span class="text-xs font-medium text-muted bg-surface border border-line rounded-lg px-3 py-1.5 hidden sm:inline-flex items-center gap-1.5 shrink-0">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} – {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
        </span>
    </div>

    {{-- Filter bar --}}
    <div class="bg-surface border border-line rounded-xl p-3 mb-6 flex flex-wrap gap-3 items-center">
        {{-- Preset pill group --}}
        <div class="flex items-center bg-off rounded-lg p-1 gap-0.5">
            @foreach (['month' => 'Bulan Ini', '30d' => '30 Hari', 'year' => 'Tahun Ini'] as $key => $label)
                <button wire:click="setPreset('{{ $key }}')"
                        class="px-3 py-1 text-xs font-semibold rounded-md transition-all duration-150
                               {{ $preset === $key
                                   ? 'bg-navy text-off shadow-sm'
                                   : 'text-muted hover:text-ink hover:bg-line/60' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="w-px h-5 bg-line hidden sm:block"></div>

        {{-- Custom date range --}}
        <div class="flex items-center gap-1.5">
            <input type="date" wire:model.live="dateFrom"
                   class="text-xs border rounded-lg px-2.5 py-1.5 text-ink bg-off focus:outline-none
                          focus:ring-2 focus:ring-navy/20 focus:border-navy/40
                          {{ $preset === 'custom' ? 'border-navy/40' : 'border-line' }}" />
            <span class="text-xs text-muted">→</span>
            <input type="date" wire:model.live="dateTo"
                   class="text-xs border rounded-lg px-2.5 py-1.5 text-ink bg-off focus:outline-none
                          focus:ring-2 focus:ring-navy/20 focus:border-navy/40
                          {{ $preset === 'custom' ? 'border-navy/40' : 'border-line' }}" />
        </div>

        <div class="w-px h-5 bg-line hidden sm:block"></div>

        {{-- Location --}}
        <div class="min-w-40">
            <x-select wire:model.live="filterLocation">
                <option value="">All Locations</option>
                @foreach ($locations as $loc)
                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                @endforeach
            </x-select>
        </div>

        {{-- Loading spinner --}}
        <div wire:loading class="ml-auto flex items-center gap-1.5 text-xs text-muted">
            <svg class="w-3.5 h-3.5 animate-spin text-navy" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
            </svg>
            Updating…
        </div>
    </div>

    {{-- KPI Cards --}}
    @php
        $kpiCards = [
            [
                'label'    => 'Total Revenue',
                'value'    => 'Rp ' . number_format($kpis['total_revenue'], 0, ',', '.'),
                'sub'      => 'paid transactions only',
                'icon_bg'  => 'bg-[#15803D]/10',
                'icon_txt' => 'text-[#15803D]',
                'bar'      => 'bg-[#15803D]',
                'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 8v1m0 0c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
            ],
            [
                'label'    => 'Transactions Paid',
                'value'    => number_format($kpis['paid_count']),
                'sub'      => 'completed payments',
                'icon_bg'  => 'bg-navy/10',
                'icon_txt' => 'text-navy',
                'bar'      => 'bg-navy',
                'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
            ],
            [
                'label'    => 'Avg per Transaction',
                'value'    => 'Rp ' . number_format($kpis['avg_transaction'], 0, ',', '.'),
                'sub'      => 'average value',
                'icon_bg'  => 'bg-[#1D4ED8]/10',
                'icon_txt' => 'text-[#1D4ED8]',
                'bar'      => 'bg-[#1D4ED8]',
                'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
            ],
            [
                'label'    => 'Conversion Rate',
                'value'    => $kpis['conversion_rate'] . '%',
                'sub'      => 'paid ÷ all initiated',
                'icon_bg'  => 'bg-[#B45309]/10',
                'icon_txt' => 'text-[#B45309]',
                'bar'      => 'bg-[#B45309]',
                'icon'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>',
            ],
        ];
    @endphp

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        @foreach ($kpiCards as $card)
            <div class="bg-surface border border-line rounded-xl p-4 flex flex-col gap-3">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-xs font-medium text-muted leading-tight">{{ $card['label'] }}</p>
                    <div class="w-7 h-7 rounded-lg {{ $card['icon_bg'] }} flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5 {{ $card['icon_txt'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            {!! $card['icon'] !!}
                        </svg>
                    </div>
                </div>
                <div>
                    <p class="text-lg font-extrabold text-navy leading-none tracking-tight">{{ $card['value'] }}</p>
                    <p class="text-[11px] text-muted mt-1">{{ $card['sub'] }}</p>
                </div>
                <div class="h-0.5 w-8 {{ $card['bar'] }} rounded-full"></div>
            </div>
        @endforeach
    </div>

    {{-- Revenue Over Time Chart --}}
    @php
        $chartData  = $chart;
        $maxAmount  = collect($chartData)->max('amount') ?: 1;
        $chartH     = 160;
        $barCount   = count($chartData);
        $yLabelW    = 60;
        $svgW       = max($barCount * 36, 480);
        $barAreaW   = $svgW - $yLabelW;
        $barSlot    = $barAreaW / max($barCount, 1);
        $barW       = max(16, (int) ($barSlot - 8));
        $labelStep  = max(1, (int) ceil($barCount / 10));
    @endphp

    <div class="bg-surface border border-line rounded-xl mb-6 overflow-hidden">
        <div class="px-5 pt-4 pb-3 flex items-center justify-between border-b border-line">
            <div>
                <h3 class="text-sm font-bold text-navy">Revenue Over Time</h3>
                <p class="text-xs text-muted mt-0.5">
                    {{ $bucketMode === 'daily' ? 'Daily' : 'Monthly' }} buckets · based on paid_at
                </p>
            </div>
            <span class="text-[10px] font-semibold text-muted bg-off border border-line rounded-lg px-2.5 py-1 uppercase tracking-wide">
                {{ $bucketMode === 'daily' ? 'Daily' : 'Monthly' }}
            </span>
        </div>

        @if ($barCount === 0 || collect($chartData)->sum('amount') === 0)
            <div class="p-10 flex flex-col items-center gap-3 text-center">
                <div class="w-12 h-12 rounded-xl bg-off border border-line flex items-center justify-center">
                    <svg class="w-6 h-6 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-ink">No revenue data</p>
                    <p class="text-xs text-muted">No paid transactions in this period.</p>
                </div>
            </div>
        @else
            <div class="px-5 py-5 relative"
                 x-data="{ activeBar: null, tooltip: null, tooltipX: 0, tooltipY: 0 }"
                 x-on:mousemove="tooltipX = $event.offsetX; tooltipY = $event.offsetY">

                <div class="overflow-x-auto">
                    <svg viewBox="0 0 {{ $svgW }} {{ $chartH + 40 }}"
                         width="100%"
                         preserveAspectRatio="none"
                         style="min-width:{{ min($svgW, 360) }}px; height:{{ $chartH + 40 }}px;"
                         xmlns="http://www.w3.org/2000/svg">

                        {{-- Dashed gridlines + Y-axis labels --}}
                        @foreach ([0.25, 0.5, 0.75, 1.0] as $frac)
                            @php $gy = (int) round($chartH - ($frac * $chartH)); @endphp
                            <line x1="{{ $yLabelW }}" y1="{{ $gy }}" x2="{{ $svgW }}" y2="{{ $gy }}"
                                  stroke="#E6E3DC" stroke-width="1" stroke-dasharray="4 3"/>
                            <text x="{{ $yLabelW - 6 }}" y="{{ $gy + 3.5 }}"
                                  text-anchor="end" font-size="9" fill="#9AA0AC" font-family="Arial,sans-serif">
                                @php $val = $maxAmount * $frac; @endphp
                                @if ($val >= 1000000)
                                    Rp {{ number_format($val / 1000000, 1, ',', '.') }}jt
                                @else
                                    Rp {{ number_format($val / 1000, 0, ',', '.') }}rb
                                @endif
                            </text>
                        @endforeach

                        {{-- Baseline --}}
                        <line x1="{{ $yLabelW }}" y1="{{ $chartH }}" x2="{{ $svgW }}" y2="{{ $chartH }}"
                              stroke="#E6E3DC" stroke-width="1.5"/>

                        {{-- Bars --}}
                        @foreach ($chartData as $i => $bucket)
                            @php
                                $barH  = $bucket['amount'] > 0 ? max(3, (int) round($bucket['amount'] / $maxAmount * $chartH)) : 0;
                                $barX  = (int) round($yLabelW + $i * $barSlot + ($barSlot - $barW) / 2);
                                $barY  = $chartH - $barH;
                                $tipAmt = 'Rp ' . number_format($bucket['amount'], 0, ',', '.');
                            @endphp

                            <g x-on:mouseenter="activeBar = {{ $i }}; tooltip = '{{ addslashes($bucket['label']) }}||{{ addslashes($tipAmt) }}'"
                               x-on:mouseleave="activeBar = null; tooltip = null"
                               style="cursor:pointer">
                                {{-- Wide hover zone (invisible, full column height) --}}
                                <rect x="{{ $barX - 4 }}" y="0"
                                      width="{{ $barW + 8 }}" height="{{ $chartH }}"
                                      fill="transparent"/>
                                {{-- Actual bar --}}
                                <rect x="{{ $barX }}" y="{{ $barY }}"
                                      width="{{ $barW }}" height="{{ max($barH, 0) }}"
                                      rx="3" ry="3"
                                      :fill="activeBar === {{ $i }} ? '#1D4ED8' : '#0A0F1E'"
                                      :opacity="activeBar === null ? 0.85 : (activeBar === {{ $i }} ? 1 : 0.25)"/>
                            </g>

                            {{-- X-axis label --}}
                            @if ($i % $labelStep === 0)
                                <text x="{{ $barX + $barW / 2 }}" y="{{ $chartH + 16 }}"
                                      text-anchor="middle" font-size="8.5" fill="#9AA0AC" font-family="Arial,sans-serif">
                                    {{ $bucket['label'] }}
                                </text>
                            @endif
                        @endforeach
                    </svg>
                </div>

                {{-- Rich tooltip --}}
                <div x-show="tooltip !== null"
                     x-cloak
                     x-bind:style="'left:' + tooltipX + 'px; top:' + (tooltipY - 60) + 'px'"
                     class="absolute pointer-events-none z-20 -translate-x-1/2 bg-navy text-off
                            rounded-xl shadow-lg px-3 py-2.5 min-w-max">
                    <p class="text-[10px] text-off/50 leading-none mb-1.5 font-medium"
                       x-text="tooltip ? tooltip.split('||')[0] : ''"></p>
                    <p class="text-sm font-extrabold leading-none"
                       x-text="tooltip ? tooltip.split('||')[1] : ''"></p>
                    {{-- Caret --}}
                    <div class="absolute left-1/2 -translate-x-1/2 -bottom-1.5 w-3 h-1.5 overflow-hidden">
                        <div class="w-2 h-2 bg-navy rotate-45 translate-y-[-50%] translate-x-[1px] mx-auto"></div>
                    </div>
                </div>

                {{-- Chart legend --}}
                <div class="flex items-center gap-4 mt-3 pt-3 border-t border-line">
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 rounded bg-navy opacity-85"></div>
                        <span class="text-xs text-muted">Revenue</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 rounded bg-[#1D4ED8]"></div>
                        <span class="text-xs text-muted">Hovered</span>
                    </div>
                    <span class="text-xs text-muted ml-auto">Hover bar for detail</span>
                </div>
            </div>
        @endif
    </div>

    {{-- Breakdown section --}}
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

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">

        {{-- By package type --}}
        <div class="bg-surface border border-line rounded-xl overflow-hidden">
            <div class="px-4 py-3 border-b border-line">
                <h3 class="text-sm font-bold text-navy">By Package Type</h3>
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
                        <div class="h-2 bg-off rounded-full overflow-hidden border border-line">
                            <div class="{{ $meta['bar'] }} h-full rounded-full"
                                 style="width:{{ round($data['revenue'] / $maxTypeRev * 100) }}%">
                            </div>
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
                <h3 class="text-sm font-bold text-navy">By Location</h3>
            </div>
            <div class="p-4 space-y-4">
                @forelse ($byLocation as $locName => $data)
                    @php $pct = round($data['revenue'] / $totalLocRev * 100); @endphp
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <div class="w-1.5 h-1.5 rounded-full bg-navy shrink-0"></div>
                                <span class="text-xs font-semibold text-ink truncate max-w-[45%]">{{ $locName }}</span>
                                <span class="text-xs text-muted">{{ $data['count'] }} txn</span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-bold text-ink tabular-nums">
                                    Rp {{ number_format($data['revenue'], 0, ',', '.') }}
                                </span>
                                <span class="text-xs text-muted ml-1.5">{{ $pct }}%</span>
                            </div>
                        </div>
                        <div class="h-2 bg-off rounded-full overflow-hidden border border-line">
                            <div class="bg-navy h-full rounded-full"
                                 style="width:{{ round($data['revenue'] / $maxLocationRev * 100) }}%">
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-muted py-6 text-center">No data in this period.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Top Packages Table --}}
    <div class="bg-surface border border-line rounded-xl mb-6 overflow-hidden">
        <div class="px-5 py-3 border-b border-line flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-navy">Top Packages</h3>
                <p class="text-xs text-muted">Top 10 by revenue in period.</p>
            </div>
        </div>

        @if ($topPackages->isEmpty())
            <div class="p-10 flex flex-col items-center gap-3 text-center">
                <div class="w-12 h-12 rounded-xl bg-off border border-line flex items-center justify-center">
                    <svg class="w-6 h-6 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-ink">No packages sold</p>
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
                            <th class="px-5 py-2.5 text-left font-semibold text-muted uppercase tracking-wide text-[10px]">Type</th>
                            <th class="px-5 py-2.5 text-left font-semibold text-muted uppercase tracking-wide text-[10px] hidden md:table-cell">Location</th>
                            <th class="px-5 py-2.5 text-right font-semibold text-muted uppercase tracking-wide text-[10px]">Units</th>
                            <th class="px-5 py-2.5 text-right font-semibold text-muted uppercase tracking-wide text-[10px]">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($topPackages as $idx => $pkg)
                            @php $meta = $typeMeta[$pkg['type']] ?? ['label' => $pkg['type'], 'class' => 'bg-line text-ink']; @endphp
                            <tr class="hover:bg-off/60 transition-colors group">
                                <td class="px-5 py-3 text-muted font-medium tabular-nums">{{ $idx + 1 }}</td>
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

    {{-- Payment Funnel --}}
    @php
        $funnelMeta = [
            'paid'     => ['label' => 'Paid',     'bg' => 'bg-green-500',  'text' => 'text-green-700',  'light' => 'bg-green-50',  'border' => 'border-green-100'],
            'pending'  => ['label' => 'Pending',  'bg' => 'bg-amber-400',  'text' => 'text-amber-700',  'light' => 'bg-amber-50',  'border' => 'border-amber-100'],
            'rejected' => ['label' => 'Rejected', 'bg' => 'bg-red-500',    'text' => 'text-red-700',    'light' => 'bg-red-50',    'border' => 'border-red-100'],
            'expired'  => ['label' => 'Expired',  'bg' => 'bg-slate-400',  'text' => 'text-slate-600',  'light' => 'bg-slate-50',  'border' => 'border-slate-100'],
        ];
    @endphp

    <div class="bg-surface border border-line rounded-xl mb-6 overflow-hidden">
        <div class="px-5 py-3 border-b border-line">
            <h3 class="text-sm font-bold text-navy">Payment Funnel</h3>
            <p class="text-xs text-muted mt-0.5">By transaction initiation date (created_at).</p>
        </div>

        <div class="p-5">
            @if ($funnelTotal === 0)
                <div class="py-6 flex flex-col items-center gap-2 text-center">
                    <p class="text-sm font-semibold text-ink">No transactions yet</p>
                    <p class="text-xs text-muted">No transactions initiated in this period.</p>
                </div>
            @else
                {{-- Proportional bar --}}
                <div class="flex h-7 rounded-xl overflow-hidden mb-4" style="gap:1px; background:#E6E3DC;">
                    @foreach ($funnelMeta as $status => $meta)
                        @php $count = $funnel[$status] ?? 0; $pct = round($count / $funnelTotal * 100); @endphp
                        @if ($pct > 0)
                            <div class="{{ $meta['bg'] }} flex items-center justify-center transition-all"
                                 style="width:{{ $pct }}%"
                                 title="{{ $meta['label'] }}: {{ $count }} ({{ $pct }}%)">
                                @if ($pct >= 8)
                                    <span class="text-white text-[10px] font-bold select-none">{{ $pct }}%</span>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>

                {{-- Status cards --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    @foreach ($funnelMeta as $status => $meta)
                        @php $count = $funnel[$status] ?? 0; $pct = $funnelTotal > 0 ? round($count / $funnelTotal * 100, 1) : 0; @endphp
                        <div class="border rounded-xl p-3.5 {{ $meta['light'] }} {{ $meta['border'] }}">
                            <div class="flex items-center gap-1.5 mb-2">
                                <span class="w-2 h-2 rounded-full {{ $meta['bg'] }} shrink-0"></span>
                                <span class="{{ $meta['text'] }} text-[11px] font-semibold">{{ $meta['label'] }}</span>
                            </div>
                            <p class="{{ $meta['text'] }} text-2xl font-extrabold leading-none tabular-nums">{{ $count }}</p>
                            <p class="{{ $meta['text'] }} text-[10px] mt-1.5 opacity-70">{{ $pct }}% of total</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

</div>
