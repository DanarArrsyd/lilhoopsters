<div class="relative">

    {{-- ══════════════════════════════════════════════
         FILTER BAR
    ══════════════════════════════════════════════ --}}
    <div class="bg-surface border border-line rounded-xl px-4 py-3 mb-5 flex flex-wrap gap-3 items-center">

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

        <div class="w-px h-5 bg-line hidden sm:block shrink-0"></div>

        {{-- Date range --}}
        <div class="flex items-center gap-2">
            <span class="text-xs text-muted hidden sm:inline">Dari</span>
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
                <option value="">Semua Lokasi</option>
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
        <div wire:loading class="flex items-center gap-1.5 text-xs text-muted">
            <svg class="w-3.5 h-3.5 animate-spin text-navy" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
            </svg>
            Memuat…
        </div>
    </div>

    {{-- ══════════════════════════════════════════════
         KPI CARDS
    ══════════════════════════════════════════════ --}}
    @php
        $kpiCards = [
            [
                'label'   => 'Total Revenue',
                'value'   => 'Rp ' . number_format($kpis['total_revenue'], 0, ',', '.'),
                'sub'     => 'transaksi terbayar',
                'ib'      => 'bg-[#15803D]/10',
                'it'      => 'text-[#15803D]',
                'bar'     => 'bg-[#15803D]',
                'icon'    => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 8v1m0 0c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'label'   => 'Transaksi Dibayar',
                'value'   => number_format($kpis['paid_count']),
                'sub'     => 'transaksi selesai',
                'ib'      => 'bg-navy/10',
                'it'      => 'text-navy',
                'bar'     => 'bg-navy',
                'icon'    => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'label'   => 'Rata-rata Transaksi',
                'value'   => 'Rp ' . number_format($kpis['avg_transaction'], 0, ',', '.'),
                'sub'     => 'nilai rata-rata',
                'ib'      => 'bg-[#1D4ED8]/10',
                'it'      => 'text-[#1D4ED8]',
                'bar'     => 'bg-[#1D4ED8]',
                'icon'    => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
            ],
            [
                'label'   => 'Conversion Rate',
                'value'   => $kpis['conversion_rate'] . '%',
                'sub'     => 'dibayar ÷ semua inisiasi',
                'ib'      => 'bg-[#B45309]/10',
                'it'      => 'text-[#B45309]',
                'bar'     => 'bg-[#B45309]',
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
                    <p class="text-[11px] text-muted mt-1.5">{{ $card['sub'] }}</p>
                </div>
                <div class="h-0.5 w-10 {{ $card['bar'] }} rounded-full"></div>
            </div>
        @endforeach
    </div>

    {{-- ══════════════════════════════════════════════
         REVENUE OVER TIME — CHART HERO
    ══════════════════════════════════════════════ --}}
    @php
        $chartData  = $chart;
        $maxAmount  = collect($chartData)->max('amount') ?: 1;
        $sumAmount  = collect($chartData)->sum('amount');
        $barCount   = count($chartData);
        $avgAmount  = $barCount > 0 ? (int) round($sumAmount / $barCount) : 0;

        // SVG geometry
        $padTop    = 30;   // space above bars for value labels
        $chartH    = 220;  // bar drawing area height
        $padBot    = 48;   // x-axis label space
        $yLabelW   = 68;   // y-axis label column width
        $totalSvgH = $padTop + $chartH + $padBot;
        $svgW      = max($barCount * 40, 520);
        $barAreaW  = $svgW - $yLabelW;
        $barSlot   = $barAreaW / max($barCount, 1);
        $barW      = (int) max(18, $barSlot - 10);
        $labelStep = max(1, (int) ceil($barCount / 10));

        // Average line Y position (within bar area, offset by padTop)
        $avgLineY  = $avgAmount > 0
            ? $padTop + (int) round($chartH - ($avgAmount / $maxAmount * $chartH))
            : -1;
    @endphp

    <div class="bg-surface border border-line rounded-xl mb-5 overflow-hidden">
        {{-- Chart header --}}
        <div class="px-5 py-3.5 border-b border-line flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="text-sm font-extrabold text-navy uppercase tracking-wide">Revenue Over Time</h3>
                <p class="text-xs text-muted mt-0.5">
                    Berdasarkan paid_at ·
                    {{ $bucketMode === 'daily' ? 'Harian' : 'Bulanan' }}
                </p>
            </div>

            {{-- Chart legend --}}
            <div class="flex items-center gap-4 text-xs text-muted">
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-5 h-2.5 rounded bg-navy"></span>
                    Revenue
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-5 h-0 border-t-2 border-dashed border-[#B45309]"></span>
                    Rata-rata
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-5 h-2.5 rounded bg-[#94a3b8]"></span>
                    Di bawah rata-rata
                </span>
            </div>
        </div>

        @if ($barCount === 0 || $sumAmount == 0)
            <div class="py-20 flex flex-col items-center gap-3 text-center">
                <div class="w-14 h-14 rounded-xl bg-off border border-line flex items-center justify-center">
                    <svg class="w-7 h-7 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-ink">Belum ada data</p>
                    <p class="text-xs text-muted mt-0.5">Tidak ada transaksi terbayar di periode ini.</p>
                </div>
            </div>
        @else
            <div class="px-4 pt-4 pb-2 relative"
                 x-data="{ activeBar: null, tooltip: null, tooltipX: 0, tooltipY: 0 }"
                 x-on:mousemove="tooltipX = $event.offsetX; tooltipY = $event.offsetY">

                <div class="overflow-x-auto">
                    <svg viewBox="0 0 {{ $svgW }} {{ $totalSvgH }}"
                         width="100%"
                         preserveAspectRatio="none"
                         style="min-width:{{ min($svgW, 400) }}px; height:{{ $totalSvgH }}px;"
                         xmlns="http://www.w3.org/2000/svg">

                        {{-- ── Y-axis gridlines + labels ── --}}
                        @foreach ([0, 0.25, 0.5, 0.75, 1.0] as $frac)
                            @php $gy = $padTop + (int) round($chartH - $frac * $chartH); @endphp
                            <line x1="{{ $yLabelW }}" y1="{{ $gy }}" x2="{{ $svgW }}" y2="{{ $gy }}"
                                  stroke="{{ $frac === 0 ? '#E6E3DC' : '#E6E3DC' }}"
                                  stroke-width="{{ $frac === 0 ? 1.5 : 1 }}"
                                  stroke-dasharray="{{ $frac === 0 ? '' : '4 3' }}"/>
                            @if ($frac > 0)
                                @php $val = $maxAmount * $frac; @endphp
                                <text x="{{ $yLabelW - 6 }}" y="{{ $gy + 3.5 }}"
                                      text-anchor="end" font-size="9" fill="#9AA0AC" font-family="Arial,sans-serif">
                                    @if ($val >= 1000000)Rp {{ number_format($val/1000000, 1, ',', '.') }}jt
                                    @else Rp {{ number_format($val/1000, 0, ',', '.') }}rb @endif
                                </text>
                            @endif
                        @endforeach

                        {{-- ── Average dashed line ── --}}
                        @if ($avgLineY >= $padTop && $avgLineY <= $padTop + $chartH)
                            <line x1="{{ $yLabelW }}" y1="{{ $avgLineY }}"
                                  x2="{{ $svgW }}" y2="{{ $avgLineY }}"
                                  stroke="#B45309" stroke-width="1.5" stroke-dasharray="6 3"/>
                            {{-- Avg label pill --}}
                            <rect x="{{ $yLabelW + 4 }}" y="{{ $avgLineY - 12 }}"
                                  width="42" height="11" rx="3" fill="#B45309"/>
                            <text x="{{ $yLabelW + 25 }}" y="{{ $avgLineY - 4 }}"
                                  text-anchor="middle" font-size="7.5" fill="white"
                                  font-family="Arial,sans-serif" font-weight="bold">rata-rata</text>
                        @endif

                        {{-- ── Bars ── --}}
                        @foreach ($chartData as $i => $bucket)
                            @php
                                $barH    = $bucket['amount'] > 0
                                    ? max(4, (int) round($bucket['amount'] / $maxAmount * $chartH))
                                    : 0;
                                $barX    = (int) round($yLabelW + $i * $barSlot + ($barSlot - $barW) / 2);
                                $barY    = $padTop + $chartH - $barH;
                                $aboveAvg = $bucket['amount'] >= $avgAmount;

                                // Short label above bar
                                $val = $bucket['amount'];
                                if ($val >= 1000000) {
                                    $shortLabel = 'Rp ' . number_format($val/1000000, 1, ',', '.') . 'jt';
                                } elseif ($val >= 1000) {
                                    $shortLabel = 'Rp ' . number_format($val/1000, 0, ',', '.') . 'rb';
                                } elseif ($val > 0) {
                                    $shortLabel = 'Rp ' . $val;
                                } else {
                                    $shortLabel = '';
                                }

                                $tipLabel = addslashes($bucket['label']);
                                $tipAmt   = 'Rp ' . number_format($val, 0, ',', '.');
                            @endphp

                            <g x-on:mouseenter="activeBar = {{ $i }}; tooltip = '{{ $tipLabel }}||{{ addslashes($tipAmt) }}'"
                               x-on:mouseleave="activeBar = null; tooltip = null"
                               style="cursor:pointer">

                                {{-- Invisible wide hit zone --}}
                                <rect x="{{ $barX - 4 }}" y="{{ $padTop }}"
                                      width="{{ $barW + 8 }}" height="{{ $chartH }}"
                                      fill="transparent"/>

                                {{-- Bar body --}}
                                <rect x="{{ $barX }}" y="{{ $barY }}"
                                      width="{{ $barW }}" height="{{ max($barH, 0) }}"
                                      rx="3" ry="3"
                                      :fill="activeBar === {{ $i }} ? '#1D4ED8' : '{{ $aboveAvg ? '#0A0F1E' : '#94a3b8' }}'"
                                      :opacity="activeBar === null ? 1 : (activeBar === {{ $i }} ? 1 : 0.35)"/>

                                {{-- Value label above bar --}}
                                @if ($shortLabel && $barH > 0)
                                    <text x="{{ $barX + $barW / 2 }}" y="{{ $barY - 5 }}"
                                          text-anchor="middle" font-size="8" font-family="Arial,sans-serif"
                                          font-weight="bold"
                                          :fill="activeBar === {{ $i }} ? '#1D4ED8' : '{{ $aboveAvg ? '#0A0F1E' : '#94a3b8' }}'">
                                        {{ $shortLabel }}
                                    </text>
                                @endif
                            </g>

                            {{-- X-axis label --}}
                            @if ($i % $labelStep === 0)
                                <text x="{{ $barX + $barW / 2 }}" y="{{ $padTop + $chartH + 16 }}"
                                      text-anchor="middle" font-size="8.5" fill="#9AA0AC"
                                      font-family="Arial,sans-serif">
                                    {{ $bucket['label'] }}
                                </text>
                            @endif
                        @endforeach

                    </svg>
                </div>

                {{-- ── Rich tooltip ── --}}
                <div x-show="tooltip !== null"
                     x-cloak
                     x-bind:style="`left:${tooltipX}px; top:${tooltipY - 70}px`"
                     class="absolute pointer-events-none z-30 -translate-x-1/2
                            bg-navy text-off rounded-xl shadow-xl px-4 py-3 min-w-max">
                    <p class="text-[10px] text-off/50 leading-none mb-1.5 font-medium uppercase tracking-wide"
                       x-text="tooltip ? tooltip.split('||')[0] : ''"></p>
                    <p class="text-base font-extrabold leading-none tracking-tight"
                       x-text="tooltip ? tooltip.split('||')[1] : ''"></p>
                    {{-- Caret --}}
                    <div class="absolute left-1/2 -translate-x-1/2 -bottom-1.5 border-4 border-transparent border-t-navy"></div>
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
                    <p class="text-xs text-muted py-6 text-center">Tidak ada data di periode ini.</p>
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
                    <p class="text-xs text-muted py-6 text-center">Tidak ada data di periode ini.</p>
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
            <p class="text-xs text-muted mt-0.5">10 paket tertinggi berdasarkan revenue.</p>
        </div>

        @if ($topPackages->isEmpty())
            <div class="py-12 flex flex-col items-center gap-3 text-center">
                <div class="w-12 h-12 rounded-xl bg-off border border-line flex items-center justify-center">
                    <svg class="w-6 h-6 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-ink">Belum ada paket terjual</p>
                    <p class="text-xs text-muted">Tidak ada transaksi terbayar di periode ini.</p>
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-line bg-off/50">
                            <th class="px-5 py-2.5 text-left font-semibold text-muted uppercase tracking-wide text-[10px] w-8">#</th>
                            <th class="px-5 py-2.5 text-left font-semibold text-muted uppercase tracking-wide text-[10px]">Paket</th>
                            <th class="px-5 py-2.5 text-left font-semibold text-muted uppercase tracking-wide text-[10px]">Tipe</th>
                            <th class="px-5 py-2.5 text-left font-semibold text-muted uppercase tracking-wide text-[10px] hidden md:table-cell">Lokasi</th>
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
            <p class="text-xs text-muted mt-0.5">Berdasarkan tanggal inisiasi transaksi (created_at).</p>
        </div>

        <div class="p-5">
            @if ($funnelTotal === 0)
                <div class="py-6 text-center">
                    <p class="text-sm font-bold text-ink">Belum ada transaksi</p>
                    <p class="text-xs text-muted mt-1">Tidak ada transaksi di periode ini.</p>
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
                            <p class="{{ $meta['text'] }} text-xs mt-2 font-medium">{{ $pct }}% dari total</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

</div>
