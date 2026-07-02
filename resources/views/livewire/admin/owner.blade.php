<div class="max-w-6xl mx-auto space-y-5">

    <x-admin.page-header :title="__('messages.admin.owner.title')" :subtitle="__('messages.admin.owner.subtitle')" />

    @if (session('owner_flash'))
        <div class="bg-[#15803D]/10 border border-[#15803D]/20 text-[#15803D] text-sm font-semibold rounded-xl px-4 py-3 flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('owner_flash') }}
        </div>
    @endif

    {{-- ══════════════════════════════════════════════
         AT-A-GLANCE — health strip + 30-day movement
         (always visible above tabs)
    ══════════════════════════════════════════════ --}}
    @php
        $statusDot = [
            'good'    => 'bg-[#15803D]',
            'warn'    => 'bg-[#D97706]',
            'bad'     => 'bg-[#B91C1C]',
            'neutral' => 'bg-faint',
        ];
    @endphp

    <section class="bg-surface border border-line rounded-xl px-5 py-4">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            @foreach ($insights['health'] as $h)
                <div class="flex flex-col gap-1">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0 {{ $statusDot[$h['status']] ?? 'bg-faint' }}"></span>
                        <span class="text-sm font-semibold text-ink">{{ $h['label'] }}</span>
                    </div>
                    <span class="text-xs text-muted pl-[18px]">{{ $h['note'] }}</span>
                </div>
            @endforeach
        </div>

        <div class="border-t border-line mt-4 pt-3 flex flex-wrap gap-x-8 gap-y-3">
            <span class="text-[11px] uppercase tracking-wide text-faint font-semibold self-center">{{ __('messages.admin.owner.last_30_days') }}</span>
            @php
                $trendMeta = [
                    'joined'  => __('messages.admin.owner.trend_joined'),
                    'churn'   => __('messages.admin.owner.trend_churn'),
                    'revenue' => __('messages.admin.owner.trend_revenue'),
                ];
            @endphp
            @foreach ($trendMeta as $key => $label)
                @php $t = $insights['trends'][$key]; $d = $t['delta']; @endphp
                <div>
                    <span class="block text-[11px] text-faint">{{ $label }}</span>
                    <span class="text-lg font-extrabold text-navy leading-none">
                        {{ ($t['money'] ?? false) ? 'Rp ' . number_format($t['value'], 0, ',', '.') : number_format($t['value']) }}
                    </span>
                    @if ($d)
                        @php
                            $isGood = $d['dir'] === $t['good'];
                            $isFlat = $d['dir'] === 'flat';
                            $cls = $isFlat ? 'text-muted' : ($isGood ? 'text-[#15803D]' : 'text-[#B91C1C]');
                        @endphp
                        <span class="inline-flex items-center gap-0.5 text-[11px] font-bold {{ $cls }} ml-1">
                            @unless ($isFlat)
                                <svg class="w-2.5 h-2.5 {{ $d['dir'] === 'down' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                            @endunless
                            {{ $d['pct'] }}%
                        </span>
                    @else
                        <span class="text-[11px] text-faint ml-1">—</span>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    {{-- ══════════════════════════════════════════════
         ACTION CENTER — always visible
    ══════════════════════════════════════════════ --}}
    @if (count($insights['actions']))
        <section>
            <div class="flex items-center gap-2 mb-3">
                <h2 class="text-sm font-extrabold text-navy uppercase tracking-wide">{{ __('messages.admin.owner.needs_attention') }}</h2>
                <span class="text-[11px] text-faint">{{ __('messages.admin.owner.ranked_by_impact') }}</span>
            </div>
            <div class="space-y-2">
                @php
                    $accent = ['danger' => 'border-l-[#B91C1C]', 'warning' => 'border-l-[#D97706]', 'neutral' => 'border-l-line'];
                @endphp
                @foreach ($insights['actions'] as $a)
                    <div class="bg-surface border border-line border-l-[3px] {{ $accent[$a['severity']] ?? 'border-l-line' }} rounded-r-xl px-4 py-3 flex items-center gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold text-ink">{{ $a['title'] }}</p>
                            <p class="text-xs text-muted mt-0.5">{{ $a['detail'] }}</p>
                        </div>
                        @if ($a['action'])
                            <button wire:click="{{ $a['action'] }}" wire:loading.attr="disabled" wire:target="{{ $a['action'] }}"
                                    class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-off bg-navy hover:bg-navy/90 disabled:opacity-50 rounded-lg px-3 py-1.5 transition-colors shrink-0">
                                {{ $a['cta'] }}
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ══════════════════════════════════════════════
         TAB CONTROLLER (Alpine.js — no Livewire round-trip)
    ══════════════════════════════════════════════ --}}
    <div x-data="{ tab: 'members' }">

        {{-- Tab nav --}}
        <div class="bg-surface border border-line rounded-xl p-1.5 flex items-center gap-1">
            <button @click="tab='members'"
                    :class="tab === 'members' ? 'bg-navy text-off shadow-sm' : 'text-muted hover:text-ink hover:bg-off'"
                    class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-150 text-sm font-bold">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Members
            </button>

            <button @click="tab='finance'"
                    :class="tab === 'finance' ? 'bg-navy text-off shadow-sm' : 'text-muted hover:text-ink hover:bg-off'"
                    class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-150 text-sm font-bold">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 8v1m0 0c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Finance
            </button>

            <button @click="tab='coaches'"
                    :class="tab === 'coaches' ? 'bg-navy text-off shadow-sm' : 'text-muted hover:text-ink hover:bg-off'"
                    class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-150 text-sm font-bold">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                </svg>
                Coaches
            </button>

            <button @click="tab='pipeline'"
                    :class="tab === 'pipeline' ? 'bg-navy text-off shadow-sm' : 'text-muted hover:text-ink hover:bg-off'"
                    class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-150 text-sm font-bold">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
                Pipeline
            </button>
        </div>

        {{-- ══════════════════════════════════════════════
             TAB: MEMBERS — Renewal & Churn + Capacity
        ══════════════════════════════════════════════ --}}
        <div x-show="tab === 'members'"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="mt-5 space-y-5">

            {{-- A. RENEWAL & CHURN --}}
            <section>
                <div class="flex items-center gap-2 mb-3">
                    <h2 class="text-sm font-extrabold text-navy uppercase tracking-wide">{{ __('messages.admin.owner.retention_renewal') }}</h2>
                    <span class="text-[11px] text-faint">{{ __('messages.admin.owner.current_snapshot') }}</span>
                </div>

                @php
                    $renewalCards = [
                        ['label' => __('messages.admin.owner.active_members_label'), 'value' => number_format($renewal['active_members']), 'sub' => __('messages.admin.owner.sub_active_enroll'),    'bar' => 'bg-[#15803D]'],
                        ['label' => __('messages.admin.owner.expiring_soon_label'),  'value' => number_format($renewal['expiring_count']), 'sub' => __('messages.admin.owner.sub_expiring_soon'),   'bar' => 'bg-[#B45309]'],
                        ['label' => __('messages.admin.owner.churn_label'),          'value' => number_format($renewal['churned_count']),  'sub' => __('messages.admin.owner.sub_lapsed_no_renew'), 'bar' => 'bg-[#B91C1C]'],
                        ['label' => __('messages.admin.owner.renewal_rate_label'),   'value' => $renewal['renewal_rate'] === null ? '—' : $renewal['renewal_rate'] . '%', 'sub' => __('messages.admin.owner.sub_renewed_div_lapsed'), 'bar' => 'bg-navy'],
                    ];
                @endphp

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-3">
                    @foreach ($renewalCards as $c)
                        <div class="bg-surface border border-line rounded-xl px-4 pt-4 pb-3 flex flex-col gap-3">
                            <p class="text-[11px] font-semibold text-muted uppercase tracking-wide leading-tight">{{ $c['label'] }}</p>
                            <div>
                                <p class="text-xl font-extrabold text-navy leading-none tracking-tight">{{ $c['value'] }}</p>
                                <p class="text-[11px] text-muted mt-1.5">{{ $c['sub'] }}</p>
                            </div>
                            <div class="h-0.5 w-10 {{ $c['bar'] }} rounded-full"></div>
                        </div>
                    @endforeach
                </div>

                <div class="bg-surface border border-line rounded-xl overflow-hidden">
                    <div class="px-5 py-3 border-b border-line flex items-center justify-between gap-3">
                        <h3 class="text-xs font-extrabold text-navy uppercase tracking-wide">{{ __('messages.admin.owner.expiring_followup') }}</h3>
                        @if ($renewal['expiring_list']->isNotEmpty())
                            <button wire:click="remindAllExpiring" wire:loading.attr="disabled" wire:target="remindAllExpiring"
                                    class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-off bg-navy hover:bg-navy/90 disabled:opacity-50 rounded-lg px-2.5 py-1.5 transition-colors shrink-0">
                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                                </svg>
                                {{ __('messages.admin.owner.send_all') }}
                            </button>
                        @endif
                    </div>
                    @if ($renewal['expiring_list']->isEmpty())
                        <p class="px-5 py-8 text-center text-sm text-muted">{{ __('messages.admin.owner.no_expiring') }}</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-[11px] uppercase tracking-wide text-faint border-b border-line">
                                        <th class="text-left font-semibold px-5 py-2.5">{{ __('messages.admin.owner.col_child') }}</th>
                                        <th class="text-left font-semibold px-3 py-2.5">{{ __('messages.admin.owner.col_parent') }}</th>
                                        <th class="text-left font-semibold px-3 py-2.5 hidden md:table-cell">{{ __('messages.admin.owner.col_package') }}</th>
                                        <th class="text-right font-semibold px-3 py-2.5">{{ __('messages.admin.owner.col_sessions_left') }}</th>
                                        <th class="text-right font-semibold px-3 py-2.5">{{ __('messages.admin.owner.col_expires') }}</th>
                                        <th class="text-right font-semibold px-5 py-2.5">{{ __('messages.admin.owner.col_action') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line">
                                    @foreach ($renewal['expiring_list'] as $row)
                                        <tr class="hover:bg-off/60" wire:key="exp-{{ $row['id'] }}">
                                            <td class="px-5 py-2.5 font-semibold text-ink">{{ $row['child'] }}</td>
                                            <td class="px-3 py-2.5 text-muted">
                                                <span class="text-ink">{{ $row['parent'] }}</span>
                                                <span class="block text-[11px] text-faint">{{ $row['email'] }}</span>
                                            </td>
                                            <td class="px-3 py-2.5 text-muted hidden md:table-cell">{{ $row['package'] }}</td>
                                            <td class="px-3 py-2.5 text-right">
                                                @if ($row['remaining'] !== null)
                                                    <span class="font-bold {{ $row['remaining'] <= 2 ? 'text-[#B91C1C]' : 'text-ink' }}">{{ $row['remaining'] }}</span>
                                                @else
                                                    <span class="text-faint">—</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2.5 text-right">
                                                @if ($row['expires'])
                                                    <span class="text-ink">{{ $row['expires']->format('d M Y') }}</span>
                                                    @php $d = $row['days']; @endphp
                                                    <span class="block text-[11px] font-semibold {{ $d !== null && $d < 0 ? 'text-[#B91C1C]' : ($d !== null && $d <= 7 ? 'text-[#B45309]' : 'text-faint') }}">
                                                        @if ($d === null)
                                                        @elseif ($d < 0)
                                                            {{ __('messages.admin.owner.days_ago', ['count' => abs($d)]) }}
                                                        @elseif ($d === 0)
                                                            {{ __('messages.admin.owner.today') }}
                                                        @else
                                                            {{ __('messages.admin.owner.days_left', ['count' => $d]) }}
                                                        @endif
                                                    </span>
                                                @else
                                                    <span class="text-faint">—</span>
                                                @endif
                                            </td>
                                            <td class="px-5 py-2.5 text-right">
                                                <button wire:click="sendRenewalReminder({{ $row['id'] }})"
                                                        wire:loading.attr="disabled" wire:target="sendRenewalReminder({{ $row['id'] }})"
                                                        class="inline-flex items-center gap-1 text-[11px] font-semibold text-navy border border-line hover:border-navy/40 hover:bg-off disabled:opacity-50 rounded-lg px-2 py-1 transition-colors">
                                                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                                                    </svg>
                                                    {{ __('messages.admin.owner.reminder_btn') }}
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>

            {{-- D. CAPACITY / UTILIZATION --}}
            <section>
                <div class="flex items-center gap-2 mb-3">
                    <h2 class="text-sm font-extrabold text-navy uppercase tracking-wide">{{ __('messages.admin.owner.class_utilization') }}</h2>
                    <span class="text-[11px] text-faint">{{ __('messages.admin.owner.booked_div_cap') }}</span>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 mb-3">
                    <div class="bg-surface border border-line rounded-xl px-4 pt-4 pb-3 flex flex-col gap-3">
                        <p class="text-[11px] font-semibold text-muted uppercase tracking-wide leading-tight">{{ __('messages.admin.owner.total_util_label') }}</p>
                        <div>
                            <p class="text-xl font-extrabold text-navy leading-none tracking-tight">{{ $capacity['overall'] }}%</p>
                            <p class="text-[11px] text-muted mt-1.5">{{ __('messages.admin.owner.sub_slots', ['booked' => $capacity['total_book'], 'cap' => $capacity['total_cap']]) }}</p>
                        </div>
                        <div class="h-0.5 w-10 bg-[#1D4ED8] rounded-full"></div>
                    </div>
                    <div class="bg-surface border border-line rounded-xl px-4 pt-4 pb-3 flex flex-col gap-3">
                        <p class="text-[11px] font-semibold text-muted uppercase tracking-wide leading-tight">{{ __('messages.admin.owner.underfilled_label') }}</p>
                        <div>
                            <p class="text-xl font-extrabold text-navy leading-none tracking-tight">{{ $capacity['underfilled'] }}</p>
                            <p class="text-[11px] text-muted mt-1.5">{{ __('messages.admin.owner.sub_filled') }}</p>
                        </div>
                        <div class="h-0.5 w-10 bg-[#B45309] rounded-full"></div>
                    </div>
                    <div class="bg-surface border border-line rounded-xl px-4 pt-4 pb-3 flex flex-col gap-3">
                        <p class="text-[11px] font-semibold text-muted uppercase tracking-wide leading-tight">{{ __('messages.admin.owner.active_sched_label') }}</p>
                        <div>
                            <p class="text-xl font-extrabold text-navy leading-none tracking-tight">{{ $capacity['schedules']->count() }}</p>
                            <p class="text-[11px] text-muted mt-1.5">{{ __('messages.admin.owner.sub_weekly') }}</p>
                        </div>
                        <div class="h-0.5 w-10 bg-navy rounded-full"></div>
                    </div>
                </div>

                <div class="bg-surface border border-line rounded-xl overflow-hidden">
                    @if ($capacity['schedules']->isEmpty())
                        <p class="px-5 py-8 text-center text-sm text-muted">{{ __('messages.admin.owner.no_active_schedules') }}</p>
                    @else
                        <div class="divide-y divide-line">
                            @foreach ($capacity['schedules'] as $s)
                                @php
                                    $fill = $s['fill'];
                                    $barColor = $fill >= 100 ? 'bg-[#B91C1C]' : ($fill >= 50 ? 'bg-[#15803D]' : 'bg-[#B45309]');
                                @endphp
                                <div class="px-5 py-3 flex items-center gap-4">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold text-ink truncate">{{ $s['label'] }}</p>
                                        <p class="text-[11px] text-faint">{{ $s['day'] }} · {{ $s['time'] }}</p>
                                    </div>
                                    <div class="w-40 shrink-0 hidden sm:block">
                                        <div class="h-2 bg-off rounded-full overflow-hidden">
                                            <div class="h-full {{ $barColor }} rounded-full" style="width: {{ min($fill, 100) }}%"></div>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0 w-20">
                                        <p class="text-sm font-bold text-navy">{{ $s['booked'] }}/{{ $s['capacity'] }}</p>
                                        <p class="text-[11px] text-muted">{{ $fill }}%</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

        </div>{{-- /members tab --}}

        {{-- ══════════════════════════════════════════════
             TAB: FINANCE — Outstanding payments (AR)
        ══════════════════════════════════════════════ --}}
        <div x-show="tab === 'finance'"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="mt-5 space-y-5">

            {{-- B. OUTSTANDING PAYMENTS (AR) --}}
            <section>
                <div class="flex items-center gap-2 mb-3">
                    <h2 class="text-sm font-extrabold text-navy uppercase tracking-wide">{{ __('messages.admin.owner.outstanding_payments') }}</h2>
                    <span class="text-[11px] text-faint">{{ __('messages.admin.owner.pending_status') }}</span>
                </div>

                @php
                    $arCards = [
                        ['label' => __('messages.admin.owner.total_outstanding'),    'value' => 'Rp ' . number_format($ar['outstanding'], 0, ',', '.'), 'sub' => __('messages.admin.owner.sub_open_recv'),  'bar' => 'bg-[#B45309]'],
                        ['label' => __('messages.admin.owner.pending_transactions'), 'value' => number_format($ar['count']),                           'sub' => __('messages.admin.owner.sub_unpaid'),      'bar' => 'bg-navy'],
                        ['label' => __('messages.admin.owner.overdue_label'),        'value' => number_format($ar['overdue']),                         'sub' => __('messages.admin.owner.sub_expired_at'),  'bar' => 'bg-[#B91C1C]'],
                    ];
                @endphp

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 mb-3">
                    @foreach ($arCards as $c)
                        <div class="bg-surface border border-line rounded-xl px-4 pt-4 pb-3 flex flex-col gap-3">
                            <p class="text-[11px] font-semibold text-muted uppercase tracking-wide leading-tight">{{ $c['label'] }}</p>
                            <div>
                                <p class="text-xl font-extrabold text-navy leading-none tracking-tight">{{ $c['value'] }}</p>
                                <p class="text-[11px] text-muted mt-1.5">{{ $c['sub'] }}</p>
                            </div>
                            <div class="h-0.5 w-10 {{ $c['bar'] }} rounded-full"></div>
                        </div>
                    @endforeach
                </div>

                {{-- Aging strip --}}
                <div class="bg-surface border border-line rounded-xl px-5 py-3 mb-3 flex flex-wrap items-center gap-x-6 gap-y-2">
                    <span class="text-[11px] uppercase tracking-wide text-faint font-semibold">{{ __('messages.admin.owner.invoice_age') }}</span>
                    <span class="flex items-center gap-1.5 text-sm"><span class="w-2.5 h-2.5 rounded-full bg-[#15803D]"></span><span class="text-muted">{{ __('messages.admin.owner.age_fresh') }}</span><span class="font-bold text-ink">{{ $ar['aging']['fresh'] }}</span></span>
                    <span class="flex items-center gap-1.5 text-sm"><span class="w-2.5 h-2.5 rounded-full bg-[#B45309]"></span><span class="text-muted">{{ __('messages.admin.owner.age_week') }}</span><span class="font-bold text-ink">{{ $ar['aging']['week'] }}</span></span>
                    <span class="flex items-center gap-1.5 text-sm"><span class="w-2.5 h-2.5 rounded-full bg-[#B91C1C]"></span><span class="text-muted">{{ __('messages.admin.owner.age_stale') }}</span><span class="font-bold text-ink">{{ $ar['aging']['stale'] }}</span></span>
                </div>

                <div class="bg-surface border border-line rounded-xl overflow-hidden">
                    @if ($ar['list']->isNotEmpty())
                        <div class="px-5 py-3 border-b border-line flex items-center justify-between gap-3">
                            <h3 class="text-xs font-extrabold text-navy uppercase tracking-wide">{{ __('messages.admin.owner.invoice_list') }}</h3>
                            <button wire:click="remindAllOutstanding" wire:loading.attr="disabled" wire:target="remindAllOutstanding"
                                    class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-off bg-navy hover:bg-navy/90 disabled:opacity-50 rounded-lg px-2.5 py-1.5 transition-colors shrink-0">
                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                                </svg>
                                {{ __('messages.admin.owner.send_all') }}
                            </button>
                        </div>
                    @endif
                    @if ($ar['list']->isEmpty())
                        <p class="px-5 py-8 text-center text-sm text-muted">{{ __('messages.admin.owner.no_outstanding') }}</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-[11px] uppercase tracking-wide text-faint border-b border-line">
                                        <th class="text-left font-semibold px-5 py-2.5">{{ __('messages.admin.owner.col_code') }}</th>
                                        <th class="text-left font-semibold px-3 py-2.5">{{ __('messages.admin.owner.col_child') }}</th>
                                        <th class="text-left font-semibold px-3 py-2.5 hidden md:table-cell">{{ __('messages.admin.owner.col_package') }}</th>
                                        <th class="text-right font-semibold px-3 py-2.5">{{ __('messages.admin.owner.col_age') }}</th>
                                        <th class="text-right font-semibold px-3 py-2.5">{{ __('messages.admin.owner.col_amount') }}</th>
                                        <th class="text-right font-semibold px-5 py-2.5">{{ __('messages.admin.owner.col_action') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line">
                                    @foreach ($ar['list'] as $row)
                                        <tr class="hover:bg-off/60" wire:key="ar-{{ $row['id'] }}">
                                            <td class="px-5 py-2.5 font-mono text-[12px] text-muted">{{ $row['code'] }}</td>
                                            <td class="px-3 py-2.5">
                                                <span class="font-semibold text-ink">{{ $row['child'] }}</span>
                                                <span class="block text-[11px] text-faint">{{ $row['parent'] }}</span>
                                            </td>
                                            <td class="px-3 py-2.5 text-muted hidden md:table-cell">{{ $row['package'] }}</td>
                                            <td class="px-3 py-2.5 text-right">
                                                <span class="font-semibold {{ $row['overdue'] ? 'text-[#B91C1C]' : ($row['age'] > 7 ? 'text-[#B45309]' : 'text-muted') }}">
                                                    {{ $row['age'] }}{{ __('messages.admin.owner.hrs_suffix') }}{{ $row['overdue'] ? ' ' . __('messages.admin.owner.overdue_suffix') : '' }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2.5 text-right font-bold text-navy">Rp {{ number_format($row['amount'], 0, ',', '.') }}</td>
                                            <td class="px-5 py-2.5 text-right">
                                                <button wire:click="sendPaymentReminder({{ $row['id'] }})"
                                                        wire:loading.attr="disabled" wire:target="sendPaymentReminder({{ $row['id'] }})"
                                                        class="inline-flex items-center gap-1 text-[11px] font-semibold text-navy border border-line hover:border-navy/40 hover:bg-off disabled:opacity-50 rounded-lg px-2 py-1 transition-colors">
                                                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                                                    </svg>
                                                    {{ __('messages.admin.owner.reminder_btn') }}
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>

        </div>{{-- /finance tab --}}

        {{-- ══════════════════════════════════════════════
             TAB: COACHES — Payroll + Leaderboard
        ══════════════════════════════════════════════ --}}
        <div x-show="tab === 'coaches'"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="mt-5 space-y-5">

            {{-- C. COACH PAYROLL / PERFORMANCE --}}
            <section>
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <div class="flex items-center gap-2">
                        <h2 class="text-sm font-extrabold text-navy uppercase tracking-wide">{{ __('messages.admin.owner.coach_performance') }}</h2>
                        <span class="text-[11px] text-faint">{{ __('messages.admin.owner.payroll_basis') }}</span>
                    </div>
                    <input type="month" wire:model.live="payrollMonth"
                           class="text-xs border border-line rounded-lg px-2.5 py-1.5 text-ink bg-off
                                  focus:outline-none focus:ring-2 focus:ring-navy/20 focus:border-navy/40" />
                </div>

                <div class="bg-surface border border-line rounded-xl overflow-hidden">
                    @if ($payroll->isEmpty())
                        <p class="px-5 py-8 text-center text-sm text-muted">{{ __('messages.admin.owner.no_coach_sessions') }}</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-[11px] uppercase tracking-wide text-faint border-b border-line">
                                        <th class="text-left font-semibold px-5 py-2.5">{{ __('messages.admin.owner.col_coach') }}</th>
                                        <th class="text-right font-semibold px-3 py-2.5">{{ __('messages.admin.owner.col_sessions') }}</th>
                                        <th class="text-right font-semibold px-3 py-2.5">{{ __('messages.admin.owner.col_active_days') }}</th>
                                        <th class="text-right font-semibold px-5 py-2.5">{{ __('messages.admin.owner.col_total_hours') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line">
                                    @foreach ($payroll as $row)
                                        <tr class="hover:bg-off/60">
                                            <td class="px-5 py-2.5 font-semibold text-ink">{{ $row['coach'] }}</td>
                                            <td class="px-3 py-2.5 text-right font-bold text-navy">{{ $row['sessions'] }}</td>
                                            <td class="px-3 py-2.5 text-right text-muted">{{ $row['days'] }}</td>
                                            <td class="px-5 py-2.5 text-right font-semibold text-ink">{{ $row['hours'] }} {{ __('messages.admin.owner.hrs_suffix') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>

            {{-- G. LEADERBOARD —top coaches + top members --}}
            <section>
                <div class="flex items-center gap-2 mb-3">
                    <h2 class="text-sm font-extrabold text-navy uppercase tracking-wide">Leaderboard</h2>
                    <span class="text-[11px] text-faint">Last 30 days</span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                    {{-- Top Coaches --}}
                    <div class="bg-surface border border-line rounded-xl overflow-hidden">
                        <div class="px-5 py-3 border-b border-line flex items-center gap-2">
                            <svg class="w-4 h-4 text-navy shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                            <span class="text-xs font-extrabold text-navy uppercase tracking-wide">Top Coaches</span>
                            <span class="text-[11px] text-faint">by sessions led</span>
                        </div>
                        @if ($leaderboard['topCoaches']->isEmpty())
                            <p class="px-5 py-8 text-center text-sm text-muted">No coach sessions recorded this period.</p>
                        @else
                            <div class="divide-y divide-line">
                                @foreach ($leaderboard['topCoaches'] as $i => $row)
                                    <div class="px-5 py-3 flex items-center gap-3">
                                        <span @class([
                                            'w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-extrabold shrink-0',
                                            'bg-[#F59E0B] text-white' => $i === 0,
                                            'bg-[#9CA3AF] text-white' => $i === 1,
                                            'bg-[#B45309] text-white' => $i === 2,
                                            'bg-off text-faint'       => $i > 2,
                                        ])>{{ $i + 1 }}</span>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-semibold text-ink truncate">{{ $row['name'] }}</p>
                                            <div class="mt-1 h-1.5 bg-off rounded-full overflow-hidden">
                                                <div class="h-full bg-navy rounded-full transition-all"
                                                     style="width: {{ round($row['count'] / $leaderboard['coachMax'] * 100) }}%"></div>
                                            </div>
                                        </div>
                                        <span class="text-sm font-extrabold text-navy shrink-0">{{ $row['count'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Top Members --}}
                    <div class="bg-surface border border-line rounded-xl overflow-hidden">
                        <div class="px-5 py-3 border-b border-line flex items-center gap-2">
                            <svg class="w-4 h-4 text-navy shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="text-xs font-extrabold text-navy uppercase tracking-wide">Top Members</span>
                            <span class="text-[11px] text-faint">by attendances</span>
                        </div>
                        @if ($leaderboard['topMembers']->isEmpty())
                            <p class="px-5 py-8 text-center text-sm text-muted">No attendance recorded this period.</p>
                        @else
                            <div class="divide-y divide-line">
                                @foreach ($leaderboard['topMembers'] as $i => $row)
                                    <div class="px-5 py-3 flex items-center gap-3">
                                        <span @class([
                                            'w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-extrabold shrink-0',
                                            'bg-[#F59E0B] text-white' => $i === 0,
                                            'bg-[#9CA3AF] text-white' => $i === 1,
                                            'bg-[#B45309] text-white' => $i === 2,
                                            'bg-off text-faint'       => $i > 2,
                                        ])>{{ $i + 1 }}</span>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-semibold text-ink truncate">{{ $row['name'] }}</p>
                                            <div class="mt-1 h-1.5 bg-off rounded-full overflow-hidden">
                                                <div class="h-full bg-[#15803D] rounded-full transition-all"
                                                     style="width: {{ round($row['count'] / $leaderboard['memberMax'] * 100) }}%"></div>
                                            </div>
                                        </div>
                                        <span class="text-sm font-extrabold text-[#15803D] shrink-0">{{ $row['count'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </section>

        </div>{{-- /coaches tab --}}

        {{-- ══════════════════════════════════════════════
             TAB: PIPELINE — Lead funnel + Events
        ══════════════════════════════════════════════ --}}
        <div x-show="tab === 'pipeline'"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="mt-5 space-y-5">

            {{-- E. LEAD / TRIAL FUNNEL --}}
            <section>
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <div class="flex items-center gap-2">
                        <h2 class="text-sm font-extrabold text-navy uppercase tracking-wide">{{ __('messages.admin.owner.lead_funnel') }}</h2>
                        <span class="text-[11px] text-faint">{{ __('messages.admin.owner.prospect_conversion') }}</span>
                    </div>
                    <a href="{{ route('admin.leads') }}" class="text-[11px] font-semibold text-navy hover:underline">{{ __('messages.admin.owner.manage_leads') }}</a>
                </div>

                @php
                    $leadCards = [
                        ['label' => __('messages.admin.owner.total_leads_label'),     'value' => number_format($leads['total']),     'sub' => __('messages.admin.owner.sub_all_time'),       'bar' => 'bg-navy'],
                        ['label' => __('messages.admin.owner.active_pipeline_label'), 'value' => number_format($leads['open']),      'sub' => __('messages.admin.owner.sub_still_open'),     'bar' => 'bg-[#1D4ED8]'],
                        ['label' => __('messages.admin.owner.converted_label'),       'value' => number_format($leads['converted']), 'sub' => __('messages.admin.owner.sub_became_members'), 'bar' => 'bg-[#15803D]'],
                        ['label' => __('messages.admin.owner.conv_rate_label'),       'value' => $leads['conversion'] === null ? '—' : $leads['conversion'] . '%', 'sub' => __('messages.admin.owner.sub_conv_div_closed'), 'bar' => 'bg-[#B45309]'],
                    ];
                    $leadStatusMeta = [
                        'new'             => ['label' => __('messages.admin.owner.lead_new'),              'bar' => 'bg-[#1D4ED8]'],
                        'contacted'       => ['label' => __('messages.admin.owner.lead_contacted'),        'bar' => 'bg-[#B45309]'],
                        'trial_scheduled' => ['label' => __('messages.admin.owner.lead_trial_scheduled'), 'bar' => 'bg-[#7C3AED]'],
                        'trial_done'      => ['label' => __('messages.admin.owner.lead_trial_done'),      'bar' => 'bg-navy'],
                        'converted'       => ['label' => __('messages.admin.owner.lead_converted'),       'bar' => 'bg-[#15803D]'],
                        'lost'            => ['label' => __('messages.admin.owner.lead_lost'),            'bar' => 'bg-[#B91C1C]'],
                    ];
                    $leadMax = max(1, max($leads['by_status']));
                @endphp

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-3">
                    @foreach ($leadCards as $c)
                        <div class="bg-surface border border-line rounded-xl px-4 pt-4 pb-3 flex flex-col gap-3">
                            <p class="text-[11px] font-semibold text-muted uppercase tracking-wide leading-tight">{{ $c['label'] }}</p>
                            <div>
                                <p class="text-xl font-extrabold text-navy leading-none tracking-tight">{{ $c['value'] }}</p>
                                <p class="text-[11px] text-muted mt-1.5">{{ $c['sub'] }}</p>
                            </div>
                            <div class="h-0.5 w-10 {{ $c['bar'] }} rounded-full"></div>
                        </div>
                    @endforeach
                </div>

                <div class="bg-surface border border-line rounded-xl overflow-hidden">
                    @if ($leads['total'] === 0)
                        <p class="px-5 py-8 text-center text-sm text-muted">{{ __('messages.admin.owner.no_leads') }}</p>
                    @else
                        <div class="divide-y divide-line">
                            @foreach ($leadStatusMeta as $key => $meta)
                                @php $n = $leads['by_status'][$key]; @endphp
                                <div class="px-5 py-2.5 flex items-center gap-4">
                                    <span class="text-sm text-ink w-36 shrink-0">{{ $meta['label'] }}</span>
                                    <div class="flex-1 h-2 bg-off rounded-full overflow-hidden">
                                        <div class="h-full {{ $meta['bar'] }} rounded-full" style="width: {{ round($n / $leadMax * 100) }}%"></div>
                                    </div>
                                    <span class="text-sm font-bold text-navy w-10 text-right shrink-0">{{ $n }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

            {{-- F. EVENTS — revenue, participation, attendance --}}
            <section>
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <div class="flex items-center gap-2">
                        <h2 class="text-sm font-extrabold text-navy uppercase tracking-wide">{{ __('messages.admin.owner.events_section') }}</h2>
                        <span class="text-[11px] text-faint">{{ __('messages.admin.owner.events_subtitle') }}</span>
                    </div>
                    <a href="{{ route('admin.events') }}" class="text-[11px] font-semibold text-navy hover:underline">{{ __('messages.admin.owner.manage_events') }}</a>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 mb-3">
                    <div class="bg-surface border border-line rounded-xl px-4 pt-4 pb-3 flex flex-col gap-3">
                        <p class="text-[11px] font-semibold text-muted uppercase tracking-wide leading-tight">{{ __('messages.admin.owner.event_revenue_label') }}</p>
                        <div>
                            <p class="text-xl font-extrabold text-navy leading-none tracking-tight">Rp {{ number_format($events['total_revenue'], 0, ',', '.') }}</p>
                            <p class="text-[11px] text-muted mt-1.5">{{ __('messages.admin.owner.sub_collected') }}</p>
                        </div>
                        <div class="h-0.5 w-10 bg-[#15803D] rounded-full"></div>
                    </div>
                    <div class="bg-surface border border-line rounded-xl px-4 pt-4 pb-3 flex flex-col gap-3">
                        <p class="text-[11px] font-semibold text-muted uppercase tracking-wide leading-tight">{{ __('messages.admin.owner.awaiting_payment_label') }}</p>
                        <div>
                            <p class="text-xl font-extrabold text-navy leading-none tracking-tight">Rp {{ number_format($events['total_pending'], 0, ',', '.') }}</p>
                            <p class="text-[11px] text-muted mt-1.5">{{ __('messages.admin.owner.sub_pending_reg') }}</p>
                        </div>
                        <div class="h-0.5 w-10 bg-[#B45309] rounded-full"></div>
                    </div>
                    <div class="bg-surface border border-line rounded-xl px-4 pt-4 pb-3 flex flex-col gap-3">
                        <p class="text-[11px] font-semibold text-muted uppercase tracking-wide leading-tight">{{ __('messages.admin.owner.confirmed_part_label') }}</p>
                        <div>
                            <p class="text-xl font-extrabold text-navy leading-none tracking-tight">{{ number_format($events['total_people']) }}</p>
                            <p class="text-[11px] text-muted mt-1.5">{{ __('messages.admin.owner.sub_across_events') }}</p>
                        </div>
                        <div class="h-0.5 w-10 bg-navy rounded-full"></div>
                    </div>
                </div>

                <div class="bg-surface border border-line rounded-xl overflow-hidden">
                    @if ($events['rows']->isEmpty())
                        <p class="px-5 py-8 text-center text-sm text-muted">{{ __('messages.admin.owner.no_events') }}</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-[11px] uppercase tracking-wide text-faint border-b border-line">
                                        <th class="text-left font-semibold px-5 py-2.5">{{ __('messages.admin.owner.col_event') }}</th>
                                        <th class="text-right font-semibold px-3 py-2.5">{{ __('messages.admin.owner.col_confirmed') }}</th>
                                        <th class="text-right font-semibold px-3 py-2.5 hidden sm:table-cell">{{ __('messages.admin.owner.col_pending') }}</th>
                                        <th class="text-right font-semibold px-3 py-2.5">{{ __('messages.admin.owner.col_attendance') }}</th>
                                        <th class="text-right font-semibold px-5 py-2.5">{{ __('messages.admin.owner.col_revenue') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line">
                                    @foreach ($events['rows'] as $row)
                                        <tr class="hover:bg-off/60">
                                            <td class="px-5 py-2.5">
                                                <span class="font-semibold text-ink">{{ $row['name'] }}</span>
                                                <span class="block text-[11px] text-faint">{{ $row['period'] }}{{ $row['is_paid'] ? '' : ' ' . __('messages.admin.owner.free_suffix') }}</span>
                                            </td>
                                            <td class="px-3 py-2.5 text-right font-bold text-navy">{{ $row['confirmed'] }}</td>
                                            <td class="px-3 py-2.5 text-right text-muted hidden sm:table-cell">{{ $row['pending'] }}</td>
                                            <td class="px-3 py-2.5 text-right text-muted">{{ $row['attendance'] === null ? '—' : $row['attendance'] . '%' }}</td>
                                            <td class="px-5 py-2.5 text-right font-semibold text-ink">Rp {{ number_format($row['revenue'], 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>

        </div>{{-- /pipeline tab --}}

    </div>{{-- /tab controller --}}

</div>
