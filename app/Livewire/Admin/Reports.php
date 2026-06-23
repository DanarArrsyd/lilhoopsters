<?php

namespace App\Livewire\Admin;

use App\Models\Location;
use App\Models\Transaction;
use Carbon\Carbon;
use Livewire\Component;

class Reports extends Component
{
    public string $preset         = 'month';
    public string $dateFrom       = '';
    public string $dateTo         = '';
    public ?int   $filterLocation = null;

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo   = now()->toDateString();
    }

    public function setPreset(string $preset): void
    {
        $this->preset = $preset;
        match ($preset) {
            'month' => [$this->dateFrom, $this->dateTo] = [
                now()->startOfMonth()->toDateString(),
                now()->toDateString(),
            ],
            '30d' => [$this->dateFrom, $this->dateTo] = [
                now()->subDays(29)->toDateString(),
                now()->toDateString(),
            ],
            'year' => [$this->dateFrom, $this->dateTo] = [
                now()->startOfYear()->toDateString(),
                now()->toDateString(),
            ],
            default => null,
        };
    }

    public function updatedDateFrom(): void { $this->preset = 'custom'; }
    public function updatedDateTo(): void   { $this->preset = 'custom'; }

    // ─── helpers ─────────────────────────────────────────────────────

    private function resolvedRange(): array
    {
        $from = Carbon::parse($this->dateFrom)->startOfDay();
        $to   = Carbon::parse($this->dateTo)->endOfDay();

        if ($to->lt($from)) {
            [$from, $to] = [$to->startOfDay(), $from->endOfDay()];
        }

        return [$from, $to];
    }

    // Previous period of equal length, immediately before [$from, $to].
    private function previousRange(Carbon $from, Carbon $to): array
    {
        $days     = (int) $from->diffInDays($to) + 1;
        $prevTo   = $from->copy()->subDay()->endOfDay();
        $prevFrom = $prevTo->copy()->subDays($days - 1)->startOfDay();

        return [$prevFrom, $prevTo];
    }

    // Percent change vs baseline. null = no baseline to compare against.
    private function delta(float $current, float $previous): ?array
    {
        if ($previous == 0.0) {
            return $current == 0.0 ? null : ['dir' => 'up', 'pct' => 100.0];
        }

        $pct = ($current - $previous) / $previous * 100;

        return [
            'dir' => $pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'flat'),
            'pct' => round(abs($pct), 1),
        ];
    }

    // Paid-transactions query for a range, honouring the location filter.
    private function paidQuery(Carbon $from, Carbon $to)
    {
        return Transaction::where('status', 'paid')
            ->whereBetween('paid_at', [$from, $to])
            ->when($this->filterLocation, fn($q) => $q->whereHas(
                'package', fn($p) => $p->where('location_id', $this->filterLocation)
            ));
    }

    // ─── export ──────────────────────────────────────────────────────

    public function export()
    {
        [$from, $to] = $this->resolvedRange();

        $rows = $this->paidQuery($from, $to)
            ->with(['package.location'])
            ->orderBy('paid_at')
            ->get();

        $filename = 'revenue_' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM so Excel reads Rupiah/accents correctly
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Tanggal Bayar', 'Paket', 'Tipe', 'Lokasi', 'Jumlah']);

            foreach ($rows as $t) {
                fputcsv($out, [
                    optional($t->paid_at)->format('Y-m-d H:i'),
                    $t->package?->name ?? '—',
                    $t->package?->type ?? '—',
                    $t->package?->location?->name ?? '—',
                    $t->amount,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    // ─── render ──────────────────────────────────────────────────────

    public function render()
    {
        [$from, $to] = $this->resolvedRange();

        // Query A — paid transactions (revenue)
        $paid = $this->paidQuery($from, $to)->with(['package.location'])->get();

        // Query B — all-status funnel (by created_at)
        $funnel = Transaction::whereBetween('created_at', [$from, $to])
            ->when($this->filterLocation, fn($q) => $q->whereHas(
                'package', fn($p) => $p->where('location_id', $this->filterLocation)
            ))
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        // KPIs
        $totalRevenue = $paid->sum('amount');
        $paidCount    = $paid->count();
        $funnelTotal  = array_sum($funnel);
        $kpis = [
            'total_revenue'   => $totalRevenue,
            'paid_count'      => $paidCount,
            'avg_transaction' => $paidCount > 0 ? intdiv($totalRevenue, $paidCount) : 0,
            'conversion_rate' => $funnelTotal > 0
                ? round(($funnel['paid'] ?? 0) / $funnelTotal * 100, 1)
                : 0.0,
        ];

        // Previous-period comparison (same length, immediately before)
        [$pFrom, $pTo]  = $this->previousRange($from, $to);
        $prevPaid       = $this->paidQuery($pFrom, $pTo)->get(['amount']);
        $prevRevenue    = (int) $prevPaid->sum('amount');
        $prevCount      = $prevPaid->count();
        $prevAvg        = $prevCount > 0 ? intdiv($prevRevenue, $prevCount) : 0;

        $prevFunnel     = Transaction::whereBetween('created_at', [$pFrom, $pTo])
            ->when($this->filterLocation, fn($q) => $q->whereHas(
                'package', fn($p) => $p->where('location_id', $this->filterLocation)
            ))
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();
        $prevFunnelTotal = array_sum($prevFunnel);
        $prevConversion  = $prevFunnelTotal > 0
            ? round(($prevFunnel['paid'] ?? 0) / $prevFunnelTotal * 100, 1)
            : 0.0;

        $deltas = [
            'total_revenue'   => $this->delta($totalRevenue, $prevRevenue),
            'paid_count'      => $this->delta($paidCount, $prevCount),
            'avg_transaction' => $this->delta($kpis['avg_transaction'], $prevAvg),
            'conversion_rate' => $this->delta($kpis['conversion_rate'], $prevConversion),
        ];

        // Revenue over time — daily or monthly buckets
        $rangeDays  = (int) $from->diffInDays($to) + 1;
        $bucketMode = $rangeDays <= 31 ? 'daily' : 'monthly';
        $chart      = $this->buildTimeChart($paid, $from, $to, $bucketMode);

        // By package type
        $byType = $paid->groupBy(fn($t) => $t->package?->type ?? 'unknown')
            ->map(fn($items) => ['revenue' => $items->sum('amount'), 'count' => $items->count()])
            ->sortByDesc('revenue');

        // By location
        $byLocation = $paid->groupBy(fn($t) => $t->package?->location?->name ?? 'Unknown')
            ->map(fn($items) => ['revenue' => $items->sum('amount'), 'count' => $items->count()])
            ->sortByDesc('revenue');

        // Top 10 packages
        $topPackages = $paid->groupBy('package_id')
            ->map(function ($items) {
                $pkg = $items->first()->package;
                return [
                    'name'     => $pkg?->name ?? '—',
                    'type'     => $pkg?->type ?? '—',
                    'location' => $pkg?->location?->name ?? '—',
                    'units'    => $items->count(),
                    'revenue'  => $items->sum('amount'),
                ];
            })
            ->sortByDesc('revenue')
            ->take(10)
            ->values();

        $chartLabels  = array_column($chart, 'label');
        $chartAmounts = array_column($chart, 'amount');
        $chartAvg     = count($chartAmounts) > 0
            ? (int) round(array_sum($chartAmounts) / count($chartAmounts))
            : 0;

        $this->dispatch('chart-data-ready',
            labels: $chartLabels,
            amounts: $chartAmounts,
            avg: $chartAvg,
        );

        return view('livewire.admin.reports', compact(
            'kpis', 'chart', 'byType', 'byLocation', 'topPackages', 'funnel', 'deltas',
        ))->with([
            'locations'    => Location::where('is_active', true)->orderBy('name')->get(),
            'bucketMode'   => $bucketMode,
            'funnelTotal'  => $funnelTotal,
            'chartLabels'  => $chartLabels,
            'chartAmounts' => $chartAmounts,
            'chartAvg'     => $chartAvg,
        ]);
    }

    // Build time-chart data: array of ['label'=>string, 'amount'=>int]
    private function buildTimeChart($paid, Carbon $from, Carbon $to, string $mode): array
    {
        if ($mode === 'daily') {
            // Index paid transactions by date
            $byDate = $paid->groupBy(fn($t) => Carbon::parse($t->paid_at)->toDateString())
                ->map(fn($items) => $items->sum('amount'));

            $buckets = [];
            for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
                $key       = $d->toDateString();
                $buckets[] = ['label' => $d->format('d M'), 'amount' => $byDate->get($key, 0)];
            }
            return $buckets;
        }

        // Monthly
        $byMonth = $paid->groupBy(fn($t) => Carbon::parse($t->paid_at)->format('Y-m'))
            ->map(fn($items) => $items->sum('amount'));

        $buckets = [];
        $cursor  = $from->copy()->startOfMonth();
        $end     = $to->copy()->startOfMonth();
        while ($cursor->lte($end)) {
            $key       = $cursor->format('Y-m');
            $buckets[] = ['label' => $cursor->format('M Y'), 'amount' => $byMonth->get($key, 0)];
            $cursor->addMonthNoOverflow();
        }
        return $buckets;
    }
}
