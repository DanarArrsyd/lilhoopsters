<div class="relative max-w-6xl mx-auto">

    <x-admin.page-header :title="__('messages.admin.reports.title')" :subtitle="__('messages.admin.reports.subtitle')" />

    {{-- ══════════════════════════════════════════════
         FILTER BAR
    ══════════════════════════════════════════════ --}}
    <div class="bg-surface border border-line rounded-xl px-4 py-3 mb-5 flex flex-wrap gap-3 items-center">

        {{-- Preset pill group --}}
        <div class="flex items-center bg-off rounded-lg p-1 gap-0.5">
            @foreach ([
                'month' => __('messages.admin.reports.preset_month'),
                '30d'   => __('messages.admin.reports.preset_30d'),
                'year'  => __('messages.admin.reports.preset_year'),
            ] as $key => $label)
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
            <span class="text-xs text-muted hidden sm:inline">{{ __('messages.admin.reports.from_label') }}</span>
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
                <option value="">{{ __('messages.admin.reports.all_locations') }}</option>
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
            {{ __('messages.admin.reports.loading') }}
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
            {{ __('messages.admin.reports.export_csv') }}
        </button>
    </div>

    {{-- ══════════════════════════════════════════════
         TAB CONTROLLER (Alpine.js — no Livewire round-trip)
    ══════════════════════════════════════════════ --}}
    <div x-data="{ tab: 'revenue' }">

        {{-- Tab nav --}}
        <div class="bg-surface border border-line rounded-xl p-1.5 mb-5 flex items-center gap-1">
            <button @click="tab='revenue'"
                    :class="tab === 'revenue' ? 'bg-navy text-off shadow-sm' : 'text-muted hover:text-ink hover:bg-off'"
                    class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-150 text-sm font-bold">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 8v1m0 0c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Revenue
            </button>

            <button @click="tab='packages'"
                    :class="tab === 'packages' ? 'bg-navy text-off shadow-sm' : 'text-muted hover:text-ink hover:bg-off'"
                    class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-150 text-sm font-bold">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                Packages
            </button>

            <button @click="tab='payments'"
                    :class="tab === 'payments' ? 'bg-navy text-off shadow-sm' : 'text-muted hover:text-ink hover:bg-off'"
                    class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-150 text-sm font-bold">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                Payments
            </button>
        </div>

        {{-- Content fades while a filter reload is in flight --}}
        <div wire:loading.class="opacity-40 pointer-events-none"
             wire:target="setPreset,dateFrom,dateTo,filterLocation"
             class="transition-opacity duration-200">

            {{-- ══════════════════════════════════════════════
                 TAB: REVENUE — KPI cards + chart
            ══════════════════════════════════════════════ --}}
            <div x-show="tab === 'revenue'"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0">

                @php
                    $kpiCards = [
                        [
                            'key'     => 'total_revenue',
                            'label'   => __('messages.admin.reports.kpi_total_revenue'),
                            'value'   => 'Rp ' . number_format($kpis['total_revenue'], 0, ',', '.'),
                            'sub'     => __('messages.admin.reports.sub_paid_txn'),
                            'ib'      => 'bg-[#15803D]/10',
                            'it'      => 'text-[#15803D]',
                            'bar'     => 'bg-[#15803D]',
                            'good'    => 'up',
                            'icon'    => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 8v1m0 0c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                        ],
                        [
                            'key'     => 'paid_count',
                            'label'   => __('messages.admin.reports.kpi_paid_count'),
                            'value'   => number_format($kpis['paid_count']),
                            'sub'     => __('messages.admin.reports.sub_completed'),
                            'ib'      => 'bg-navy/10',
                            'it'      => 'text-navy',
                            'bar'     => 'bg-navy',
                            'good'    => 'up',
                            'icon'    => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                        ],
                        [
                            'key'     => 'avg_transaction',
                            'label'   => __('messages.admin.reports.kpi_avg_transaction'),
                            'value'   => 'Rp ' . number_format($kpis['avg_transaction'], 0, ',', '.'),
                            'sub'     => __('messages.admin.reports.sub_avg_value'),
                            'ib'      => 'bg-[#1D4ED8]/10',
                            'it'      => 'text-[#1D4ED8]',
                            'bar'     => 'bg-[#1D4ED8]',
                            'good'    => 'up',
                            'icon'    => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                        ],
                        [
                            'key'     => 'conversion_rate',
                            'label'   => __('messages.admin.reports.kpi_conv_rate'),
                            'value'   => $kpis['conversion_rate'] . '%',
                            'sub'     => __('messages.admin.reports.sub_paid_div_all'),
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
                                <p class="text-2xs font-semibold text-muted uppercase tracking-wide leading-tight">{{ $card['label'] }}</p>
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
                                        <span class="inline-flex items-center gap-0.5 text-3xs font-bold px-1.5 py-0.5 rounded-md border {{ $badge }}">
                                            @if (! $isFlat)
                                                <svg class="w-2.5 h-2.5 shrink-0 {{ $d['dir'] === 'down' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/>
                                                </svg>
                                            @endif
                                            {{ $d['pct'] }}%
                                        </span>
                                        <span class="text-3xs text-faint">{{ __('messages.admin.reports.vs_prev_period') }}</span>
                                    @else
                                        <p class="text-2xs text-muted">{{ $card['sub'] }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="h-0.5 w-10 {{ $card['bar'] }} rounded-full"></div>
                        </div>
                    @endforeach
                </div>

                {{-- REVENUE OVER TIME — CHART.JS --}}
                <div class="bg-surface border border-line rounded-xl overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-line flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-extrabold text-navy uppercase tracking-wide">{{ __('messages.admin.reports.revenue_over_time') }}</h3>
                            <p class="text-xs text-muted mt-0.5">
                                Based on paid_at · {{ $bucketMode === 'daily' ? __('messages.admin.reports.bucket_daily') : __('messages.admin.reports.bucket_monthly') }}
                            </p>
                        </div>
                        <div class="flex items-center gap-4 text-xs text-muted">
                            <span class="flex items-center gap-1.5">
                                <span class="inline-block w-4 h-3 rounded bg-navy"></span>
                                {{ __('messages.admin.reports.legend_above') }}
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span class="inline-block w-4 h-3 rounded bg-[#94a3b8]"></span>
                                {{ __('messages.admin.reports.legend_below') }}
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span class="inline-block w-4 border-t-2 border-dashed border-[#B45309]"></span>
                                {{ __('messages.admin.reports.legend_avg') }}
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
                                <p class="text-sm font-bold text-ink">{{ __('messages.admin.reports.no_data_title') }}</p>
                                <p class="text-xs text-muted mt-0.5">{{ __('messages.admin.reports.no_data_desc') }}</p>
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

            </div>{{-- /revenue tab --}}

            {{-- ══════════════════════════════════════════════
                 TAB: PACKAGES — breakdown + top packages
            ══════════════════════════════════════════════ --}}
            <div x-show="tab === 'packages'"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0">

                @php
                    $typeMeta = [
                        'registration' => ['label' => __('messages.admin.packages.type_reg'),    'class' => 'bg-[#1D4ED8]/10 text-[#1D4ED8]', 'bar' => 'bg-[#1D4ED8]'],
                        'regular'      => ['label' => __('messages.admin.packages.type_regular'),'class' => 'bg-navy/10 text-navy',           'bar' => 'bg-navy'],
                        'drop_in'      => ['label' => __('messages.admin.packages.type_drop_in'),'class' => 'bg-[#B45309]/10 text-[#B45309]', 'bar' => 'bg-[#B45309]'],
                        'private'      => ['label' => __('messages.admin.packages.type_private'),'class' => 'bg-[#7C3AED]/10 text-[#7C3AED]', 'bar' => 'bg-[#7C3AED]'],
                    ];
                    $maxTypeRev     = $byType->max('revenue')     ?: 1;
                    $maxLocationRev = $byLocation->max('revenue') ?: 1;
                    $totalTypeRev   = $byType->sum('revenue')     ?: 1;
                    $totalLocRev    = $byLocation->sum('revenue') ?: 1;
                @endphp

                {{-- BREAKDOWN: BY TYPE + BY LOCATION --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-5">

                    <div class="bg-surface border border-line rounded-xl overflow-hidden">
                        <div class="px-4 py-3 border-b border-line">
                            <h3 class="text-sm font-extrabold text-navy uppercase tracking-wide">{{ __('messages.admin.reports.by_type') }}</h3>
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
                                            <span class="px-2 py-0.5 rounded-full text-2xs font-semibold {{ $meta['class'] }}">
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
                                <p class="text-xs text-muted py-6 text-center">{{ __('messages.admin.reports.no_data_period') }}</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="bg-surface border border-line rounded-xl overflow-hidden">
                        <div class="px-4 py-3 border-b border-line">
                            <h3 class="text-sm font-extrabold text-navy uppercase tracking-wide">{{ __('messages.admin.reports.by_location') }}</h3>
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
                                <p class="text-xs text-muted py-6 text-center">{{ __('messages.admin.reports.no_data_period') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- TOP PACKAGES TABLE --}}
                <div class="bg-surface border border-line rounded-xl overflow-hidden">
                    <div class="px-5 py-3 border-b border-line">
                        <h3 class="text-sm font-extrabold text-navy uppercase tracking-wide">{{ __('messages.admin.reports.top_packages') }}</h3>
                        <p class="text-xs text-muted mt-0.5">{{ __('messages.admin.reports.top_packages_sub') }}</p>
                    </div>

                    @if ($topPackages->isEmpty())
                        <div class="py-12 flex flex-col items-center gap-3 text-center">
                            <div class="w-12 h-12 rounded-xl bg-off border border-line flex items-center justify-center">
                                <svg class="w-6 h-6 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-ink">{{ __('messages.admin.reports.no_packages_title') }}</p>
                                <p class="text-xs text-muted">{{ __('messages.admin.reports.no_packages_desc') }}</p>
                            </div>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="border-b border-line bg-off/50">
                                        <th class="px-5 py-2.5 text-left font-semibold text-muted uppercase tracking-wide text-3xs w-8">{{ __('messages.admin.reports.col_rank') }}</th>
                                        <th class="px-5 py-2.5 text-left font-semibold text-muted uppercase tracking-wide text-3xs">{{ __('messages.admin.reports.col_package') }}</th>
                                        <th class="px-5 py-2.5 text-left font-semibold text-muted uppercase tracking-wide text-3xs">{{ __('messages.admin.reports.col_type') }}</th>
                                        <th class="px-5 py-2.5 text-left font-semibold text-muted uppercase tracking-wide text-3xs hidden md:table-cell">{{ __('messages.admin.reports.col_location') }}</th>
                                        <th class="px-5 py-2.5 text-right font-semibold text-muted uppercase tracking-wide text-3xs">{{ __('messages.admin.reports.col_unit') }}</th>
                                        <th class="px-5 py-2.5 text-right font-semibold text-muted uppercase tracking-wide text-3xs">{{ __('messages.admin.reports.col_revenue') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line">
                                    @foreach ($topPackages as $idx => $pkg)
                                        @php $meta = $typeMeta[$pkg['type']] ?? ['label' => $pkg['type'], 'class' => 'bg-line text-ink']; @endphp
                                        <tr class="hover:bg-off/60 transition-colors group">
                                            <td class="px-5 py-3 text-muted tabular-nums font-semibold">{{ $idx + 1 }}</td>
                                            <td class="px-5 py-3 font-semibold text-ink group-hover:text-navy transition-colors">{{ $pkg['name'] }}</td>
                                            <td class="px-5 py-3">
                                                <span class="px-2 py-0.5 rounded-full text-3xs font-semibold {{ $meta['class'] }}">
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

            </div>{{-- /packages tab --}}

            {{-- ══════════════════════════════════════════════
                 TAB: PAYMENTS — funnel
            ══════════════════════════════════════════════ --}}
            <div x-show="tab === 'payments'"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0">

                @php
                    $funnelMeta = [
                        'paid'     => ['label' => __('messages.status.paid'),      'bg' => 'bg-green-500',  'text' => 'text-green-700',  'light' => 'bg-green-50',  'border' => 'border-green-200'],
                        'pending'  => ['label' => __('messages.status.pending'),   'bg' => 'bg-amber-400',  'text' => 'text-amber-700',  'light' => 'bg-amber-50',  'border' => 'border-amber-200'],
                        'rejected' => ['label' => __('messages.status.rejected'),  'bg' => 'bg-red-500',    'text' => 'text-red-700',    'light' => 'bg-red-50',    'border' => 'border-red-200'],
                        'expired'  => ['label' => __('messages.status.expired'),   'bg' => 'bg-slate-400',  'text' => 'text-slate-600',  'light' => 'bg-slate-50',  'border' => 'border-slate-200'],
                    ];
                @endphp

                <div class="bg-surface border border-line rounded-xl overflow-hidden">
                    <div class="px-5 py-3 border-b border-line">
                        <h3 class="text-sm font-extrabold text-navy uppercase tracking-wide">{{ __('messages.admin.reports.payment_funnel') }}</h3>
                        <p class="text-xs text-muted mt-0.5">{{ __('messages.admin.reports.funnel_sub') }}</p>
                    </div>

                    <div class="p-5">
                        @if ($funnelTotal === 0)
                            <div class="py-6 text-center">
                                <p class="text-sm font-bold text-ink">{{ __('messages.admin.reports.no_txn_title') }}</p>
                                <p class="text-xs text-muted mt-1">{{ __('messages.admin.reports.no_txn_desc') }}</p>
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
                                                <span class="text-white text-3xs font-bold select-none">{{ $pct }}%</span>
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
                                            <span class="{{ $meta['text'] }} text-2xs font-bold uppercase tracking-wide">{{ $meta['label'] }}</span>
                                        </div>
                                        <p class="{{ $meta['text'] }} text-3xl font-extrabold leading-none tabular-nums">{{ $count }}</p>
                                        <p class="{{ $meta['text'] }} text-xs mt-2 font-medium">{{ $pct }}{{ __('messages.admin.reports.pct_of_total') }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

            </div>{{-- /payments tab --}}

        </div>{{-- /content fade wrapper --}}
    </div>{{-- /tab controller --}}

</div>
