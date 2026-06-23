<?php

namespace App\Livewire\Admin;

use App\Models\CoachSession;
use App\Models\Enrollment;
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

    public function render()
    {
        return view('livewire.admin.owner', [
            'renewal'  => $this->renewalData(),
            'ar'       => $this->arData(),
            'payroll'  => $this->payrollData(),
            'capacity' => $this->capacityData(),
            'leads'    => $this->leadData(),
        ]);
    }
}
