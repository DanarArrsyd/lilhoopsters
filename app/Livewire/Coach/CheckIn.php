<?php

namespace App\Livewire\Coach;

use App\Models\CoachSession;
use App\Models\Schedule;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CheckIn extends Component
{
    public ?float $latitude  = null;
    public ?float $longitude = null;

    public function checkIn(int $scheduleId): void
    {
        $coach = Auth::user()->coach;
        if (!$coach) abort(403);

        $schedule = Schedule::where('is_active', true)->findOrFail($scheduleId);

        // Private schedules: only the assigned coach may check in
        if ($schedule->type === 'private' && $schedule->coach_id !== $coach->id) {
            session()->flash('error', 'This private session is assigned to a different coach.');
            return;
        }

        // Time gate: only allow check-in once near the schedule (30 min before)
        // and never after it has already ended.
        $state = $schedule->checkInState();
        if ($state === 'early') {
            [$opens] = $schedule->checkInWindow();
            session()->flash('error', "Too early — check-in opens at {$opens->format('H:i')}.");
            return;
        }
        if ($state === 'ended') {
            session()->flash('error', 'This session has already ended — check-in is closed.');
            return;
        }

        // Prevent duplicate check-in
        $already = CoachSession::where('schedule_id', $scheduleId)
            ->where('coach_id', $coach->id)
            ->whereDate('session_date', today())
            ->exists();

        if ($already) {
            session()->flash('error', 'You are already checked in to this session.');
            return;
        }

        // First coach = primary, subsequent = assistant
        $hasPrimary = CoachSession::where('schedule_id', $scheduleId)
            ->whereDate('session_date', today())
            ->where('role', 'primary')
            ->exists();

        $session = CoachSession::create([
            'schedule_id'   => $scheduleId,
            'coach_id'      => $coach->id,
            'session_date'  => today(),
            'role'          => $hasPrimary ? 'assistant' : 'primary',
            'ip_address'    => request()->ip(),
            'latitude'      => $this->latitude,
            'longitude'     => $this->longitude,
            'checked_in_at' => now(),
        ]);

        // Soft geofence: the check-in still succeeds, but warn when the GPS
        // position falls outside the venue radius so it can be reviewed.
        $session->setRelation('schedule', $schedule);
        if ($session->isLocationFlagged()) {
            session()->flash('warning', "Checked in, but you appear to be {$session->distanceMeters()}m from the venue.");
        } else {
            session()->flash('success', 'Checked in successfully.');
        }
    }

    public function checkOut(int $sessionId): void
    {
        $coach = Auth::user()->coach;
        if (!$coach) abort(403);

        CoachSession::where('id', $sessionId)
            ->where('coach_id', $coach->id)
            ->whereNull('checked_out_at')
            ->firstOrFail()
            ->update(['checked_out_at' => now()]);

        session()->flash('success', 'Checked out.');
    }

    public function render()
    {
        $coach = Auth::user()->coach;
        $today = strtolower(now()->format('l'));

        // Close any sessions whose schedule has already ended (coach forgot to
        // check out) at their scheduled end time.
        CoachSession::autoCloseStale();

        // Warn this coach if any of today's sessions were auto-closed.
        $autoClosedCount = $coach
            ? CoachSession::where('coach_id', $coach->id)
                ->whereDate('session_date', today())
                ->where('auto_closed', true)
                ->count()
            : 0;

        // Regular: all active schedules for today (any coach can join)
        $regularSchedules = Schedule::with(['location', 'program'])
            ->where('is_active', true)
            ->where('type', 'regular')
            ->where('day_of_week', $today)
            ->orderBy('start_time')
            ->get();

        // Private: only schedules assigned to this coach today
        $privateSchedules = $coach
            ? Schedule::with(['location', 'program'])
                ->where('is_active', true)
                ->where('type', 'private')
                ->where('coach_id', $coach->id)
                ->where('day_of_week', $today)
                ->orderBy('start_time')
                ->get()
            : collect();

        // Sessions this coach has already checked into today
        $mySessions = $coach
            ? CoachSession::where('coach_id', $coach->id)
                ->whereDate('session_date', today())
                ->pluck('schedule_id')
                ->toArray()
            : [];

        // All coach_sessions for today (to show how many coaches per schedule)
        $sessionsBySchedule = CoachSession::whereDate('session_date', today())
            ->with('coach.user')
            ->get()
            ->groupBy('schedule_id');

        // Today's active sessions for this coach (for check-out)
        $activeSessions = $coach
            ? CoachSession::where('coach_id', $coach->id)
                ->whereDate('session_date', today())
                ->whereNull('checked_out_at')
                ->with(['schedule.location', 'schedule.program'])
                ->get()
            : collect();

        // Upcoming sessions (next 7 days) — only shown when nothing is on today
        $upcomingDate      = null;
        $upcomingSchedules = collect();

        if ($regularSchedules->isEmpty() && $privateSchedules->isEmpty()) {
            $dayOrder = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
            $todayIdx = array_search($today, $dayOrder);

            for ($offset = 1; $offset <= 7; $offset++) {
                $nextDay      = $dayOrder[($todayIdx + $offset) % 7];
                $nextDate     = now()->addDays($offset);

                $nextSchedules = Schedule::with(['location', 'program'])
                    ->where('is_active', true)
                    ->where('day_of_week', $nextDay)
                    ->where(function ($q) use ($coach) {
                        $q->where('type', 'regular')
                          ->orWhere(function ($q2) use ($coach) {
                              $q2->where('type', 'private')->where('coach_id', $coach?->id);
                          });
                    })
                    ->orderBy('start_time')
                    ->get();

                if ($nextSchedules->isNotEmpty()) {
                    $upcomingDate      = $nextDate;
                    $upcomingSchedules = $nextSchedules;
                    break;
                }
            }
        }

        return view('livewire.coach.check-in', compact(
            'regularSchedules', 'privateSchedules',
            'mySessions', 'sessionsBySchedule', 'activeSessions',
            'upcomingDate', 'upcomingSchedules', 'autoClosedCount'
        ));
    }
}
