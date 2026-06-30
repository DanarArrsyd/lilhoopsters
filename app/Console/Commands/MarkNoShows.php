<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\CoachSession;
use App\Models\Enrollment;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkNoShows extends Command
{
    protected $signature = 'attendance:mark-no-shows
                            {--dry-run : Preview without writing}
                            {--date= : Process only this specific date (Y-m-d)}';

    protected $description = 'Auto-create no_show attendance records for enrolled students who missed a coached session';

    public function handle(): int
    {
        $dryRun  = $this->option('dry-run');
        $onlyDate = $this->option('date') ? Carbon::parse($this->option('date'))->toDateString() : null;

        $created = 0;
        $skipped = 0;

        // Only process sessions where coach actually checked in
        $sessionQuery = CoachSession::query()->whereDate('session_date', '<', today());
        if ($onlyDate) {
            $sessionQuery->whereDate('session_date', $onlyDate);
        }

        $coachSessions = $sessionQuery
            ->select('schedule_id', 'session_date')
            ->distinct()
            ->get();

        foreach ($coachSessions as $cs) {
            $scheduleId  = $cs->schedule_id;
            $sessionDate = Carbon::parse($cs->session_date)->toDateString();

            // All approved program enrollments that were active on this date
            $enrollments = Enrollment::where('schedule_id', $scheduleId)
                ->where('status', 'approved')
                ->where('type', 'program')
                ->where('started_at', '<=', $sessionDate)
                ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', $sessionDate))
                ->with('child')
                ->get();

            // Existing attendance records for this schedule+date (keyed by child_id)
            $existingIds = Attendance::where('schedule_id', $scheduleId)
                ->whereDate('attended_at', $sessionDate)
                ->pluck('child_id')
                ->flip();

            // Existing leave requests for this schedule+date (approved/pending)
            $leaveIds = LeaveRequest::where('schedule_id', $scheduleId)
                ->where('leave_date', $sessionDate)
                ->whereIn('status', ['approved', 'auto_approved', 'pending'])
                ->pluck('child_id')
                ->flip();

            foreach ($enrollments as $enrollment) {
                $childId = $enrollment->child_id;

                if (isset($existingIds[$childId]) || isset($leaveIds[$childId])) {
                    $skipped++;
                    continue;
                }

                if ($dryRun) {
                    $this->line("  [dry-run] Would mark no_show: child #{$childId} ({$enrollment->child->name}) schedule #{$scheduleId} on {$sessionDate}");
                    $created++;
                    continue;
                }

                Attendance::create([
                    'child_id'      => $childId,
                    'enrollment_id' => $enrollment->id,
                    'schedule_id'   => $scheduleId,
                    'coach_id'      => null,
                    'attended_at'   => $sessionDate,
                    'status'        => 'no_show',
                    'source'        => 'auto',
                    'ip_address'    => null,
                    'latitude'      => null,
                    'longitude'     => null,
                ]);

                $created++;
            }
        }

        $label = $dryRun ? 'Would create' : 'Created';
        $this->info("{$label} {$created} no_show record(s). Skipped {$skipped} (already have record or leave request).");

        return self::SUCCESS;
    }
}
