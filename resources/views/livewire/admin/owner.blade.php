<div class="space-y-5">

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
            <span class="text-[11px] uppercase tracking-wide text-faint font-semibold self-center">Last 30 days</span>
            @php
                $trendMeta = [
                    'joined'  => 'Members joined',
                    'churn'   => 'Churn',
                    'revenue' => 'Revenue',
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
         ACTION CENTER — money-ranked, what to do now
    ══════════════════════════════════════════════ --}}
    @if (count($insights['actions']))
        <section>
            <div class="flex items-center gap-2 mb-3">
                <h2 class="text-sm font-extrabold text-navy uppercase tracking-wide">Needs Your Attention</h2>
                <span class="text-[11px] text-faint">ranked by impact</span>
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
         A. RENEWAL & CHURN
    ══════════════════════════════════════════════ --}}
    <section>
        <div class="flex items-center gap-2 mb-3">
            <h2 class="text-sm font-extrabold text-navy uppercase tracking-wide">Retention & Renewal</h2>
            <span class="text-[11px] text-faint">current snapshot</span>
        </div>

        @php
            $renewalCards = [
                ['label' => 'Active Members',     'value' => number_format($renewal['active_members']), 'sub' => 'active enrollment', 'bar' => 'bg-[#15803D]'],
                ['label' => 'Expiring Soon',     'value' => number_format($renewal['expiring_count']), 'sub' => '≤14 days / ≤2 sessions',   'bar' => 'bg-[#B45309]'],
                ['label' => 'Churn (30 days)',  'value' => number_format($renewal['churned_count']),  'sub' => 'lapsed, no renew',    'bar' => 'bg-[#B91C1C]'],
                ['label' => 'Renewal Rate',     'value' => $renewal['renewal_rate'] === null ? '—' : $renewal['renewal_rate'] . '%', 'sub' => 'renewed ÷ lapsed 30 days', 'bar' => 'bg-navy'],
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

        {{-- Expiring list — actionable --}}
        <div class="bg-surface border border-line rounded-xl overflow-hidden">
            <div class="px-5 py-3 border-b border-line flex items-center justify-between gap-3">
                <h3 class="text-xs font-extrabold text-navy uppercase tracking-wide">Needs Follow-up — Expiring Soon</h3>
                @if ($renewal['expiring_list']->isNotEmpty())
                    <button wire:click="remindAllExpiring" wire:loading.attr="disabled" wire:target="remindAllExpiring"
                            class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-off bg-navy hover:bg-navy/90 disabled:opacity-50 rounded-lg px-2.5 py-1.5 transition-colors shrink-0">
                        <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                        </svg>
                        Send All
                    </button>
                @endif
            </div>
            @if ($renewal['expiring_list']->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-muted">No members expiring soon. Great.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-[11px] uppercase tracking-wide text-faint border-b border-line">
                                <th class="text-left font-semibold px-5 py-2.5">Child</th>
                                <th class="text-left font-semibold px-3 py-2.5">Parent</th>
                                <th class="text-left font-semibold px-3 py-2.5 hidden md:table-cell">Package</th>
                                <th class="text-right font-semibold px-3 py-2.5">Sessions Left</th>
                                <th class="text-right font-semibold px-3 py-2.5">Expires</th>
                                <th class="text-right font-semibold px-5 py-2.5">Action</th>
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
                                                {{ $d === null ? '' : ($d < 0 ? abs($d) . ' days ago' : ($d === 0 ? 'today' : $d . ' days left')) }}
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
                                            Reminder
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

    {{-- ══════════════════════════════════════════════
         B. OUTSTANDING PAYMENTS (AR)
    ══════════════════════════════════════════════ --}}
    <section>
        <div class="flex items-center gap-2 mb-3">
            <h2 class="text-sm font-extrabold text-navy uppercase tracking-wide">Outstanding Payments</h2>
            <span class="text-[11px] text-faint">pending status</span>
        </div>

        @php
            $arCards = [
                ['label' => 'Total Outstanding', 'value' => 'Rp ' . number_format($ar['outstanding'], 0, ',', '.'), 'sub' => 'open receivables', 'bar' => 'bg-[#B45309]'],
                ['label' => 'Pending Transactions', 'value' => number_format($ar['count']),                          'sub' => 'unpaid',    'bar' => 'bg-navy'],
                ['label' => 'Overdue', 'value' => number_format($ar['overdue']),                        'sub' => 'expired_at passed',  'bar' => 'bg-[#B91C1C]'],
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
            <span class="text-[11px] uppercase tracking-wide text-faint font-semibold">Invoice Age</span>
            <span class="flex items-center gap-1.5 text-sm"><span class="w-2.5 h-2.5 rounded-full bg-[#15803D]"></span><span class="text-muted">≤3 days</span><span class="font-bold text-ink">{{ $ar['aging']['fresh'] }}</span></span>
            <span class="flex items-center gap-1.5 text-sm"><span class="w-2.5 h-2.5 rounded-full bg-[#B45309]"></span><span class="text-muted">4–7 days</span><span class="font-bold text-ink">{{ $ar['aging']['week'] }}</span></span>
            <span class="flex items-center gap-1.5 text-sm"><span class="w-2.5 h-2.5 rounded-full bg-[#B91C1C]"></span><span class="text-muted">&gt;7 days</span><span class="font-bold text-ink">{{ $ar['aging']['stale'] }}</span></span>
        </div>

        <div class="bg-surface border border-line rounded-xl overflow-hidden">
            @if ($ar['list']->isNotEmpty())
                <div class="px-5 py-3 border-b border-line flex items-center justify-between gap-3">
                    <h3 class="text-xs font-extrabold text-navy uppercase tracking-wide">Invoice List</h3>
                    <button wire:click="remindAllOutstanding" wire:loading.attr="disabled" wire:target="remindAllOutstanding"
                            class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-off bg-navy hover:bg-navy/90 disabled:opacity-50 rounded-lg px-2.5 py-1.5 transition-colors shrink-0">
                        <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                        </svg>
                        Send All
                    </button>
                </div>
            @endif
            @if ($ar['list']->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-muted">No outstanding invoices. Cash flow clean.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-[11px] uppercase tracking-wide text-faint border-b border-line">
                                <th class="text-left font-semibold px-5 py-2.5">Code</th>
                                <th class="text-left font-semibold px-3 py-2.5">Child</th>
                                <th class="text-left font-semibold px-3 py-2.5 hidden md:table-cell">Package</th>
                                <th class="text-right font-semibold px-3 py-2.5">Age</th>
                                <th class="text-right font-semibold px-3 py-2.5">Jumlah</th>
                                <th class="text-right font-semibold px-5 py-2.5">Action</th>
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
                                            {{ $row['age'] }}h{{ $row['overdue'] ? ' · jatuh tempo' : '' }}
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
                                            Reminder
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

    {{-- ══════════════════════════════════════════════
         C. COACH PAYROLL / PERFORMANCE
    ══════════════════════════════════════════════ --}}
    <section>
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-extrabold text-navy uppercase tracking-wide">Coach Performance</h2>
                <span class="text-[11px] text-faint">payroll basis — sessions & hours</span>
            </div>
            <input type="month" wire:model.live="payrollMonth"
                   class="text-xs border border-line rounded-lg px-2.5 py-1.5 text-ink bg-off
                          focus:outline-none focus:ring-2 focus:ring-navy/20 focus:border-navy/40" />
        </div>

        <div class="bg-surface border border-line rounded-xl overflow-hidden">
            @if ($payroll->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-muted">No coach sessions this month.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-[11px] uppercase tracking-wide text-faint border-b border-line">
                                <th class="text-left font-semibold px-5 py-2.5">Coach</th>
                                <th class="text-right font-semibold px-3 py-2.5">Sessions</th>
                                <th class="text-right font-semibold px-3 py-2.5">Active Days</th>
                                <th class="text-right font-semibold px-5 py-2.5">Total Hours</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($payroll as $row)
                                <tr class="hover:bg-off/60">
                                    <td class="px-5 py-2.5 font-semibold text-ink">{{ $row['coach'] }}</td>
                                    <td class="px-3 py-2.5 text-right font-bold text-navy">{{ $row['sessions'] }}</td>
                                    <td class="px-3 py-2.5 text-right text-muted">{{ $row['days'] }}</td>
                                    <td class="px-5 py-2.5 text-right font-semibold text-ink">{{ $row['hours'] }} hrs</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>

    {{-- ══════════════════════════════════════════════
         D. CAPACITY / UTILIZATION
    ══════════════════════════════════════════════ --}}
    <section>
        <div class="flex items-center gap-2 mb-3">
            <h2 class="text-sm font-extrabold text-navy uppercase tracking-wide">Class Utilization</h2>
            <span class="text-[11px] text-faint">booked ÷ capacity</span>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 mb-3">
            <div class="bg-surface border border-line rounded-xl px-4 pt-4 pb-3 flex flex-col gap-3">
                <p class="text-[11px] font-semibold text-muted uppercase tracking-wide leading-tight">Total Utilization</p>
                <div>
                    <p class="text-xl font-extrabold text-navy leading-none tracking-tight">{{ $capacity['overall'] }}%</p>
                    <p class="text-[11px] text-muted mt-1.5">{{ $capacity['total_book'] }} of {{ $capacity['total_cap'] }} slots</p>
                </div>
                <div class="h-0.5 w-10 bg-[#1D4ED8] rounded-full"></div>
            </div>
            <div class="bg-surface border border-line rounded-xl px-4 pt-4 pb-3 flex flex-col gap-3">
                <p class="text-[11px] font-semibold text-muted uppercase tracking-wide leading-tight">Underfilled Classes</p>
                <div>
                    <p class="text-xl font-extrabold text-navy leading-none tracking-tight">{{ $capacity['underfilled'] }}</p>
                    <p class="text-[11px] text-muted mt-1.5">filled &lt;50%</p>
                </div>
                <div class="h-0.5 w-10 bg-[#B45309] rounded-full"></div>
            </div>
            <div class="bg-surface border border-line rounded-xl px-4 pt-4 pb-3 flex flex-col gap-3">
                <p class="text-[11px] font-semibold text-muted uppercase tracking-wide leading-tight">Active Schedules</p>
                <div>
                    <p class="text-xl font-extrabold text-navy leading-none tracking-tight">{{ $capacity['schedules']->count() }}</p>
                    <p class="text-[11px] text-muted mt-1.5">weekly sessions</p>
                </div>
                <div class="h-0.5 w-10 bg-navy rounded-full"></div>
            </div>
        </div>

        <div class="bg-surface border border-line rounded-xl overflow-hidden">
            @if ($capacity['schedules']->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-muted">No active schedules.</p>
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

    {{-- ══════════════════════════════════════════════
         E. LEAD / TRIAL FUNNEL
    ══════════════════════════════════════════════ --}}
    <section>
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-extrabold text-navy uppercase tracking-wide">Lead Funnel</h2>
                <span class="text-[11px] text-faint">prospect → conversion</span>
            </div>
            <a href="{{ route('admin.leads') }}" class="text-[11px] font-semibold text-navy hover:underline">Manage Leads →</a>
        </div>

        @php
            $leadCards = [
                ['label' => 'Total Leads',     'value' => number_format($leads['total']),     'sub' => 'sepanjang waktu',  'bar' => 'bg-navy'],
                ['label' => 'Active Pipeline', 'value' => number_format($leads['open']),      'sub' => 'still open',      'bar' => 'bg-[#1D4ED8]'],
                ['label' => 'Converted',       'value' => number_format($leads['converted']), 'sub' => 'became members',      'bar' => 'bg-[#15803D]'],
                ['label' => 'Conversion Rate','value' => $leads['conversion'] === null ? '—' : $leads['conversion'] . '%', 'sub' => 'converted ÷ closed', 'bar' => 'bg-[#B45309]'],
            ];
            $leadStatusMeta = [
                'new'             => ['label' => 'New',              'bar' => 'bg-[#1D4ED8]'],
                'contacted'       => ['label' => 'Contacted',         'bar' => 'bg-[#B45309]'],
                'trial_scheduled' => ['label' => 'Trial Scheduled', 'bar' => 'bg-[#7C3AED]'],
                'trial_done'      => ['label' => 'Trial Done',     'bar' => 'bg-navy'],
                'converted'       => ['label' => 'Converted',          'bar' => 'bg-[#15803D]'],
                'lost'            => ['label' => 'Lost',            'bar' => 'bg-[#B91C1C]'],
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
                <p class="px-5 py-8 text-center text-sm text-muted">No leads yet. Add prospects in the Leads menu.</p>
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

    {{-- ══════════════════════════════════════════════
         F. EVENTS — revenue, participation, attendance
    ══════════════════════════════════════════════ --}}
    <section>
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-extrabold text-navy uppercase tracking-wide">Events</h2>
                <span class="text-[11px] text-faint">revenue & participation</span>
            </div>
            <a href="{{ route('admin.events') }}" class="text-[11px] font-semibold text-navy hover:underline">Manage Events →</a>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 mb-3">
            <div class="bg-surface border border-line rounded-xl px-4 pt-4 pb-3 flex flex-col gap-3">
                <p class="text-[11px] font-semibold text-muted uppercase tracking-wide leading-tight">Event Revenue</p>
                <div>
                    <p class="text-xl font-extrabold text-navy leading-none tracking-tight">Rp {{ number_format($events['total_revenue'], 0, ',', '.') }}</p>
                    <p class="text-[11px] text-muted mt-1.5">collected (paid)</p>
                </div>
                <div class="h-0.5 w-10 bg-[#15803D] rounded-full"></div>
            </div>
            <div class="bg-surface border border-line rounded-xl px-4 pt-4 pb-3 flex flex-col gap-3">
                <p class="text-[11px] font-semibold text-muted uppercase tracking-wide leading-tight">Awaiting Payment</p>
                <div>
                    <p class="text-xl font-extrabold text-navy leading-none tracking-tight">Rp {{ number_format($events['total_pending'], 0, ',', '.') }}</p>
                    <p class="text-[11px] text-muted mt-1.5">pending registrations</p>
                </div>
                <div class="h-0.5 w-10 bg-[#B45309] rounded-full"></div>
            </div>
            <div class="bg-surface border border-line rounded-xl px-4 pt-4 pb-3 flex flex-col gap-3">
                <p class="text-[11px] font-semibold text-muted uppercase tracking-wide leading-tight">Confirmed Participants</p>
                <div>
                    <p class="text-xl font-extrabold text-navy leading-none tracking-tight">{{ number_format($events['total_people']) }}</p>
                    <p class="text-[11px] text-muted mt-1.5">across events</p>
                </div>
                <div class="h-0.5 w-10 bg-navy rounded-full"></div>
            </div>
        </div>

        <div class="bg-surface border border-line rounded-xl overflow-hidden">
            @if ($events['rows']->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-muted">No registerable events yet.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-[11px] uppercase tracking-wide text-faint border-b border-line">
                                <th class="text-left font-semibold px-5 py-2.5">Event</th>
                                <th class="text-right font-semibold px-3 py-2.5">Confirmed</th>
                                <th class="text-right font-semibold px-3 py-2.5 hidden sm:table-cell">Pending</th>
                                <th class="text-right font-semibold px-3 py-2.5">Attendance</th>
                                <th class="text-right font-semibold px-5 py-2.5">Revenue</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($events['rows'] as $row)
                                <tr class="hover:bg-off/60">
                                    <td class="px-5 py-2.5">
                                        <span class="font-semibold text-ink">{{ $row['name'] }}</span>
                                        <span class="block text-[11px] text-faint">{{ $row['period'] }}{{ $row['is_paid'] ? '' : ' · Free' }}</span>
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

</div>
