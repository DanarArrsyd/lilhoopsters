<?php

namespace App\Livewire\Admin;

use App\Models\Attendance;
use App\Models\Child;
use App\Models\Coach;
use App\Models\CoachSession;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\Lead;
use App\Models\Schedule;
use App\Models\Transaction;
use App\Services\ReminderService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Owner Insights — business-health dashboard for the academy owner.
 *
 * Four read-only modules, all derived from existing tables:
 *   A. Renewal & Churn   (enrollments: expires_at / remaining_sessions / status)
 *   B. Outstanding (AR)  (transactions: status=pending, amount, age)
 *   C. Coach Payroll     (coach_sessions: sessions led + hours, per month)
 *   D. Capacity           (schedules.max_capacity vs approved enrollments)
 */
class Owner extends Component
{
    /** Month for coach-payroll module, format YYYY-MM. */
    public string $payrollMonth = '';

    public function mount(): void
    {
        $this->payrollMonth = now()->format('Y-m');
    }

    // ─── Manual reminder actions ─────────────────────────────────────

    public function sendRenewalReminder(int $enrollmentId): void
    {
        $e = Enrollment::with(['child.parent', 'package'])->find($enrollmentId);

        $ok = $e && ReminderService::sendRenewal($e, force: true);

        session()->flash('owner_flash', $ok
            ? 'Renewal reminder sent.'
            : 'Failed to send — parent not found.');
    }

    public function sendPaymentReminder(int $transactionId): void
    {
        $t = Transaction::with(['user', 'package'])->find($transactionId);

        $ok = $t && ReminderService::sendPayment($t, force: true);

        session()->flash('owner_flash', $ok
            ? 'Payment reminder sent.'
            : 'Failed to send — parent not found.');
    }

    public function remindAllExpiring(): void
    {
        $n = ReminderService::remindExpiring(14);
        session()->flash('owner_flash', "{$n} renewal reminders sent.");
    }

    public function remindAllOutstanding(): void
    {
        $n = ReminderService::remindOutstanding();
        session()->flash('owner_flash', "{$n} payment reminders sent.");
    }

    // ─── A. Renewal & Churn ──────────────────────────────────────────

    private function renewalData(): array
    {
        $today      = today();
        $soonCutoff = $today->copy()->addDays(14);

        $active = Enrollment::query()
            ->where('status', 'approved')
            ->where(fn($q) => $q->whereNull('expires_at')->orWhereDate('expires_at', '>=', $today))
            ->where(fn($q) => $q->whereNull('remaining_sessions')->orWhere('remaining_sessions', '>', 0))
            ->get();

        $activeMembers = $active->pluck('child_id')->unique()->count();

        // Expiring soon: active, but runs out within 14 days OR ≤2 sessions left.
        $expiring = $active
            ->filter(fn($e) =>
                ($e->expires_at !== null && $e->expires_at->lte($soonCutoff))
                || ($e->remaining_sessions !== null && $e->remaining_sessions <= 2)
            )
            ->sortBy(fn($e) => $e->expires_at?->timestamp ?? PHP_INT_MAX)
            ->values();

        $expiringList = Enrollment::query()
            ->whereIn('id', $expiring->pluck('id'))
            ->with(['child.parent', 'package'])
            ->get()
            ->sortBy(fn($e) => $e->expires_at?->timestamp ?? PHP_INT_MAX)
            ->map(fn($e) => [
                'id'        => $e->id,
                'child'     => $e->child?->name ?? '—',
                'parent'    => $e->child?->parent?->name ?? '—',
                'email'     => $e->child?->parent?->email ?? '—',
                'package'   => $e->package?->name ?? '—',
                'remaining' => $e->remaining_sessions,
                'expires'   => $e->expires_at,
                'days'      => $e->expires_at ? (int) $today->diffInDays($e->expires_at, false) : null,
            ])
            ->values();

        // Churn over the trailing 30 days: enrollments that lapsed and whose
        // child has no active enrollment now.
        $windowStart   = $today->copy()->subDays(30);
        $activeChildIds = $active->pluck('child_id')->unique()->all();

        $lapsedRecently = Enrollment::query()
            ->where('status', 'approved')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [$windowStart, $today])
            ->get();

        $churnedChildIds = $lapsedRecently->pluck('child_id')->unique()
            ->reject(fn($id) => in_array($id, $activeChildIds, true))
            ->values();

        $renewedChildIds = $lapsedRecently->pluck('child_id')->unique()
            ->filter(fn($id) => in_array($id, $activeChildIds, true))
            ->values();

        $lapsedTotal = $churnedChildIds->count() + $renewedChildIds->count();
        $renewalRate = $lapsedTotal > 0
            ? round($renewedChildIds->count() / $lapsedTotal * 100, 1)
            : null;

        return [
            'active_members' => $activeMembers,
            'expiring_count' => $expiring->count(),
            'churned_count'  => $churnedChildIds->count(),
            'renewal_rate'   => $renewalRate,
            'expiring_list'  => $expiringList,
        ];
    }

    // ─── B. Outstanding payments (AR) ────────────────────────────────

    private function arData(): array
    {
        $now = now();

        $pending = Transaction::query()
            ->where('status', 'pending')
            ->with(['child', 'user', 'package'])
            ->orderBy('created_at')
            ->get();

        $aging = ['fresh' => 0, 'week' => 0, 'stale' => 0]; // ≤3d / 4–7d / >7d
        foreach ($pending as $t) {
            $days = $t->created_at->diffInDays($now);
            if ($days <= 3)      $aging['fresh']++;
            elseif ($days <= 7)  $aging['week']++;
            else                 $aging['stale']++;
        }

        $list = $pending->map(fn($t) => [
            'id'      => $t->id,
            'code'    => $t->transaction_code,
            'child'   => $t->child?->name ?? '—',
            'parent'  => $t->user?->name ?? '—',
            'package' => $t->package?->name ?? '—',
            'amount'  => $t->amount,
            'age'     => (int) $t->created_at->diffInDays($now),
            'overdue' => $t->expired_at !== null && $t->expired_at->isPast(),
        ])->values();

        return [
            'outstanding' => (int) $pending->sum('amount'),
            'count'       => $pending->count(),
            'overdue'     => $list->where('overdue', true)->count(),
            'aging'       => $aging,
            'list'        => $list,
        ];
    }

    // ─── C. Coach payroll / performance ──────────────────────────────

    private function payrollData(): Collection
    {
        $from = Carbon::createFromFormat('Y-m', $this->payrollMonth)->startOfMonth();
        $to   = $from->copy()->endOfMonth();

        return CoachSession::query()
            ->whereBetween('session_date', [$from->toDateString(), $to->toDateString()])
            ->with('coach.user')
            ->get()
            ->groupBy('coach_id')
            ->map(function ($rows) {
                $minutes = $rows->whereNotNull('checked_out_at')
                    ->sum(fn($r) => $r->checked_in_at->diffInMinutes($r->checked_out_at));

                $coach = $rows->first()->coach;

                return [
                    'coach'    => $coach?->user?->name ?? '—',
                    'sessions' => $rows->count(),
                    'days'     => $rows->pluck('session_date')->map->toDateString()->unique()->count(),
                    'hours'    => round($minutes / 60, 1),
                ];
            })
            ->sortByDesc('sessions')
            ->values();
    }

    // ─── D. Capacity / utilization ───────────────────────────────────

    private function capacityData(): array
    {
        $schedules = Schedule::query()
            ->where('is_active', true)
            ->with(['program', 'location'])
            ->withCount(['enrollments as booked' => fn($q) =>
                $q->where('status', 'approved')->where('type', 'program')
            ])
            ->get()
            ->map(fn($s) => [
                'label'    => trim(($s->program?->name ?? 'Program') . ' · ' . ($s->location?->name ?? '—')),
                'day'      => ucfirst($s->day_of_week),
                'time'     => Carbon::parse($s->start_time)->format('H:i'),
                'capacity' => $s->max_capacity,
                'booked'   => $s->booked,
                'fill'     => $s->max_capacity > 0 ? round($s->booked / $s->max_capacity * 100) : 0,
            ])
            ->sortBy('fill')
            ->values();

        $totalCap    = $schedules->sum('capacity');
        $totalBooked = $schedules->sum('booked');

        return [
            'overall'    => $totalCap > 0 ? round($totalBooked / $totalCap * 100, 1) : 0.0,
            'total_cap'  => $totalCap,
            'total_book' => $totalBooked,
            'underfilled'=> $schedules->where('fill', '<', 50)->count(),
            'schedules'  => $schedules,
        ];
    }

    // ─── E. Lead / trial funnel ──────────────────────────────────────

    private function leadData(): array
    {
        $counts = Lead::query()
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        $byStatus = [];
        foreach (Lead::STATUSES as $s) {
            $byStatus[$s] = $counts[$s] ?? 0;
        }

        $total     = array_sum($byStatus);
        $converted = $byStatus['converted'];
        $closed    = $converted + $byStatus['lost'];
        $open      = $total - $closed;

        return [
            'by_status'  => $byStatus,
            'total'      => $total,
            'open'       => $open,
            'converted'  => $converted,
            'conversion' => $closed > 0 ? round($converted / $closed * 100, 1) : null,
        ];
    }

    // ─── H. Attendance rate by program & location ────────────────────

    private function attendanceData(): array
    {
        $since = now()->subDays(30)->toDateString();

        $rows = Attendance::whereIn('status', ['present', 'no_show', 'sick', 'permit'])
            ->where('attended_at', '>=', $since)
            ->with(['schedule.program', 'schedule.location'])
            ->get();

        $rate = fn($items) => $items->count() > 0
            ? round($items->where('status', 'present')->count() / $items->count() * 100, 1)
            : null;

        $overall = $rate($rows);

        $byProgram = $rows->groupBy(fn($a) => $a->schedule?->program?->name ?? 'Private / Unassigned')
            ->map(fn($items) => ['rate' => $rate($items), 'total' => $items->count()])
            ->sortByDesc('total');

        $byLocation = $rows->groupBy(fn($a) => $a->schedule?->location?->name ?? 'Unknown')
            ->map(fn($items) => ['rate' => $rate($items), 'total' => $items->count()])
            ->sortByDesc('total');

        return [
            'overall'     => $overall,
            'present'     => $rows->where('status', 'present')->count(),
            'total'       => $rows->count(),
            'by_program'  => $byProgram,
            'by_location' => $byLocation,
        ];
    }

    // ─── Insights: health strip, 30-day movement, action center ──────

    private function pctDelta(float $current, float $previous): ?array
    {
        if ($previous == 0.0) {
            return $current == 0.0 ? null : ['dir' => 'up', 'pct' => 100.0];
        }
        $pct = ($current - $previous) / $previous * 100;
        return ['dir' => $pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'flat'), 'pct' => round(abs($pct), 1)];
    }

    private function rupiah(int $n): string
    {
        return 'Rp ' . number_format($n, 0, ',', '.');
    }

    /**
     * Builds the owner-facing insight layer: a colour-coded health strip,
     * 30-day event-based trends, and a money-ranked action list.
     */
    private function insightsData(array $renewal, array $ar, array $capacity, array $leads): array
    {
        // ── 30-day movement (event-based, cleanly comparable) ──────────
        $curFrom  = now()->subDays(29)->startOfDay();
        $curTo    = now()->endOfDay();
        $prevFrom = now()->subDays(59)->startOfDay();
        $prevTo   = now()->subDays(30)->endOfDay();

        $joinedCur  = Enrollment::where('status', 'approved')->where('type', 'program')
            ->whereBetween('approved_at', [$curFrom, $curTo])->count();
        $joinedPrev = Enrollment::where('status', 'approved')->where('type', 'program')
            ->whereBetween('approved_at', [$prevFrom, $prevTo])->count();

        $activeChildIds = Enrollment::where('status', 'approved')
            ->where(fn($q) => $q->whereNull('expires_at')->orWhereDate('expires_at', '>=', today()))
            ->where(fn($q) => $q->whereNull('remaining_sessions')->orWhere('remaining_sessions', '>', 0))
            ->pluck('child_id')->unique()->all();

        $churnCur  = Enrollment::where('status', 'approved')->whereNotNull('expires_at')
            ->whereBetween('expires_at', [$curFrom, $curTo])
            ->pluck('child_id')->unique()->reject(fn($id) => in_array($id, $activeChildIds, true))->count();
        $churnPrev = Enrollment::where('status', 'approved')->whereNotNull('expires_at')
            ->whereBetween('expires_at', [$prevFrom, $prevTo])
            ->pluck('child_id')->unique()->reject(fn($id) => in_array($id, $activeChildIds, true))->count();

        $revCur  = (int) Transaction::where('status', 'paid')->whereBetween('paid_at', [$curFrom, $curTo])->sum('amount');
        $revPrev = (int) Transaction::where('status', 'paid')->whereBetween('paid_at', [$prevFrom, $prevTo])->sum('amount');

        $trends = [
            'joined'  => ['value' => $joinedCur, 'delta' => $this->pctDelta($joinedCur, $joinedPrev), 'good' => 'up'],
            'churn'   => ['value' => $churnCur,  'delta' => $this->pctDelta($churnCur, $churnPrev),   'good' => 'down'],
            'revenue' => ['value' => $revCur,    'delta' => $this->pctDelta($revCur, $revPrev),       'good' => 'up', 'money' => true],
        ];

        // ── Money at risk from expiring packages (renewal value) ───────
        $expiringValue = (int) Enrollment::where('status', 'approved')
            ->where(fn($q) => $q->whereNull('expires_at')->orWhereDate('expires_at', '>=', today()))
            ->where(fn($q) => $q->whereNull('remaining_sessions')->orWhere('remaining_sessions', '>', 0))
            ->where(fn($q) => $q->whereDate('expires_at', '<=', today()->addDays(14))->orWhere('remaining_sessions', '<=', 2))
            ->with('package')
            ->get()
            ->sum(fn($e) => $e->package?->price ?? 0);

        // ── Health strip ───────────────────────────────────────────────
        $retentionStatus = $renewal['renewal_rate'] === null
            ? ($renewal['expiring_count'] === 0 ? 'good' : 'warn')
            : ($renewal['renewal_rate'] >= 70 ? 'good' : ($renewal['renewal_rate'] >= 40 ? 'warn' : 'bad'));

        $cashflowStatus = $ar['count'] === 0 ? 'good' : ($ar['overdue'] > 0 ? 'bad' : 'warn');

        $util = (float) $capacity['overall'];
        $capacityStatus = $util >= 70 ? 'good' : ($util >= 40 ? 'warn' : 'bad');

        $pipelineStatus = $leads['total'] === 0 ? 'neutral'
            : ($leads['conversion'] === null ? 'warn'
                : ($leads['conversion'] >= 60 ? 'good' : ($leads['conversion'] >= 30 ? 'warn' : 'bad')));

        $health = [
            ['key' => 'retention', 'label' => 'Retention', 'status' => $retentionStatus,
             'note' => $renewal['renewal_rate'] === null ? $renewal['active_members'] . ' active' : $renewal['renewal_rate'] . '% renewed'],
            ['key' => 'cashflow', 'label' => 'Cashflow', 'status' => $cashflowStatus,
             'note' => $ar['count'] === 0 ? 'All clear' : ($ar['overdue'] > 0 ? $ar['overdue'] . ' overdue' : $ar['count'] . ' pending')],
            ['key' => 'capacity', 'label' => 'Capacity', 'status' => $capacityStatus,
             'note' => $util . '% filled'],
            ['key' => 'pipeline', 'label' => 'Pipeline', 'status' => $pipelineStatus,
             'note' => $leads['total'] === 0 ? 'No leads' : ($leads['conversion'] === null ? $leads['open'] . ' open' : $leads['conversion'] . '% conv.')],
        ];

        // ── Action center (ranked by money, then severity) ─────────────
        $sev = ['danger' => 3, 'warning' => 2, 'neutral' => 1];
        $actions = [];

        if ($ar['outstanding'] > 0) {
            $actions[] = [
                'severity' => $ar['overdue'] > 0 ? 'danger' : 'warning',
                'money'    => $ar['outstanding'],
                'title'    => $this->rupiah($ar['outstanding']) . ' tied up in unpaid invoices',
                'detail'   => $ar['count'] . ' pending' . ($ar['overdue'] > 0 ? ' · ' . $ar['overdue'] . ' overdue' : ''),
                'action'   => 'remindAllOutstanding',
                'cta'      => 'Send reminders',
            ];
        }

        if ($renewal['expiring_count'] > 0) {
            $actions[] = [
                'severity' => 'warning',
                'money'    => $expiringValue,
                'title'    => $expiringValue > 0
                    ? $this->rupiah($expiringValue) . ' revenue at risk'
                    : $renewal['expiring_count'] . ' packages expiring soon',
                'detail'   => $renewal['expiring_count'] . ' packages expire within 14 days unless renewed',
                'action'   => 'remindAllExpiring',
                'cta'      => 'Send renewal reminders',
            ];
        }

        if ($capacity['underfilled'] > 0) {
            $actions[] = [
                'severity' => 'neutral',
                'money'    => 0,
                'title'    => $capacity['underfilled'] . ' classes under 50% filled',
                'detail'   => 'Idle capacity — consider merging classes or running a promo',
                'action'   => null,
                'cta'      => null,
            ];
        }

        if ($renewal['churned_count'] > 0) {
            $actions[] = [
                'severity' => 'neutral',
                'money'    => 0,
                'title'    => $renewal['churned_count'] . ' members lapsed in the last 30 days',
                'detail'   => 'Reach out for a win-back',
                'action'   => null,
                'cta'      => null,
            ];
        }

        if ($leads['open'] > 0) {
            $actions[] = [
                'severity' => 'neutral',
                'money'    => 0,
                'title'    => $leads['open'] . ' leads still in the pipeline',
                'detail'   => 'Follow up before they go cold',
                'action'   => null,
                'cta'      => null,
            ];
        }

        usort($actions, fn($a, $b) =>
            [$b['money'], $sev[$b['severity']]] <=> [$a['money'], $sev[$a['severity']]]
        );

        return ['health' => $health, 'trends' => $trends, 'actions' => $actions];
    }

    // ─── F. Events — revenue, participation, attendance ──────────────

    private function eventsData(): array
    {
        $events = Event::query()
            ->where('is_registerable', true)
            ->withCount([
                'registrations as confirmed_count' => fn($q) => $q->where('status', 'confirmed'),
                'registrations as pending_count'   => fn($q) => $q->where('status', 'pending'),
                'attendances as present_marks'     => fn($q) => $q->where('status', 'present'),
                'attendances as total_marks',
            ])
            ->with(['registrations.transaction'])
            ->orderByDesc('start_date')
            ->limit(8)
            ->get();

        $rows = $events->map(function ($e) {
            $revenue = (int) $e->registrations
                ->filter(fn($r) => $r->transaction && $r->transaction->status === 'paid')
                ->sum(fn($r) => $r->transaction->amount);

            $pendingValue = (int) $e->registrations
                ->filter(fn($r) => $r->transaction && $r->transaction->status === 'pending')
                ->sum(fn($r) => $r->transaction->amount);

            return [
                'name'         => $e->name,
                'period'       => $e->start_date->format('d M') . ' – ' . $e->end_date->format('d M Y'),
                'confirmed'    => $e->confirmed_count,
                'pending'      => $e->pending_count,
                'revenue'      => $revenue,
                'pending_val'  => $pendingValue,
                'attendance'   => $e->total_marks > 0 ? (int) round($e->present_marks / $e->total_marks * 100) : null,
                'is_paid'      => (int) $e->price > 0,
            ];
        });

        return [
            'rows'          => $rows,
            'total_revenue' => (int) $rows->sum('revenue'),
            'total_pending' => (int) $rows->sum('pending_val'),
            'total_people'  => (int) $rows->sum('confirmed'),
        ];
    }

    // ─── G. Leaderboard ──────────────────────────────────────────────

    private function leaderboardData(): array
    {
        $since = now()->subDays(30)->toDateString();

        // Top coaches: most sessions led in last 30 days
        $topCoaches = CoachSession::selectRaw('coach_id, COUNT(*) as sessions')
            ->where('session_date', '>=', $since)
            ->groupBy('coach_id')
            ->orderByDesc('sessions')
            ->limit(5)
            ->with('coach.user')
            ->get()
            ->map(fn($row) => [
                'name'    => $row->coach?->user?->name ?? 'Unknown',
                'count'   => $row->sessions,
                'sub'     => $row->sessions . ' ' . ($row->sessions === 1 ? 'session' : 'sessions'),
            ]);

        // Top members: most present attendance in last 30 days
        $topMembers = Attendance::selectRaw('child_id, COUNT(*) as sessions')
            ->where('status', 'present')
            ->where('attended_at', '>=', $since)
            ->groupBy('child_id')
            ->orderByDesc('sessions')
            ->limit(5)
            ->with('child')
            ->get()
            ->map(fn($row) => [
                'name'  => $row->child?->name ?? 'Unknown',
                'count' => $row->sessions,
                'sub'   => $row->sessions . ' ' . ($row->sessions === 1 ? 'attendance' : 'attendances'),
            ]);

        $coachMax  = $topCoaches->max('count') ?: 1;
        $memberMax = $topMembers->max('count') ?: 1;

        return compact('topCoaches', 'topMembers', 'coachMax', 'memberMax');
    }

    public function render()
    {
        $renewal  = $this->renewalData();
        $ar       = $this->arData();
        $capacity = $this->capacityData();
        $leads    = $this->leadData();

        return view('livewire.admin.owner', [
            'renewal'     => $renewal,
            'ar'          => $ar,
            'payroll'     => $this->payrollData(),
            'capacity'    => $capacity,
            'leads'       => $leads,
            'events'      => $this->eventsData(),
            'insights'    => $this->insightsData($renewal, $ar, $capacity, $leads),
            'leaderboard' => $this->leaderboardData(),
            'attendance'  => $this->attendanceData(),
        ]);
    }
}
