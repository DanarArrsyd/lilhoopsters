<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Child;
use App\Models\Coach;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\LeaveRequest;
use App\Models\Location;
use App\Models\MakeUpClass;
use App\Models\Package;
use App\Models\Program;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Fills a fresh install with a term's worth of believable parent-side history:
 * two recurring weekly classes per child, attendance stretching back over a
 * month, a leave that was approved, a make-up class booked against it, and an
 * upcoming event.
 *
 * Written because every parent-facing screen — the calendar most of all — reads
 * as empty on a clean database, which makes it impossible to judge or verify a
 * design. Idempotent: re-running matches existing rows instead of duplicating.
 *
 *   php artisan db:seed --class=DemoScheduleDataSeeder
 */
class DemoScheduleDataSeeder extends Seeder
{
    public function run(): void
    {
        $location = Location::where('is_active', true)->first();
        $program  = Program::where('is_active', true)->first();
        $coach    = Coach::first();
        $admin    = User::whereHas('role', fn ($q) => $q->whereIn('name', ['admin', 'super_admin']))->first();

        if (! $location || ! $program) {
            $this->command->warn('Need at least one active location and program first — run the base seeders.');
            return;
        }

        $schedules = $this->schedules($location, $program, $coach);
        $package   = $this->package($location);
        $children  = $this->children();

        if ($children->isEmpty()) {
            $this->command->warn('No parent accounts with children — nothing to enrol.');
            return;
        }

        foreach ($children as $child) {
            $this->enrolChild($child, $schedules, $package, $admin, $coach);
        }

        $this->upcomingEvent($location, $program, $children->first(), $admin);

        $this->command->info(sprintf(
            'Demo data ready — %d schedule(s), %d child(ren), %d enrolment(s), %d attendance(s), %d leave(s), %d make-up(s), %d event(s).',
            Schedule::count(), $children->count(), Enrollment::count(),
            Attendance::count(), LeaveRequest::count(), MakeUpClass::count(), Event::count()
        ));
    }

    /** Two weekday classes and a weekend one, so a week reads as a real timetable. */
    private function schedules(Location $location, Program $program, ?Coach $coach): array
    {
        $wanted = [
            ['day_of_week' => 'monday',   'start_time' => '15:00:00', 'end_time' => '16:30:00'],
            ['day_of_week' => 'wednesday','start_time' => '16:00:00', 'end_time' => '17:30:00'],
            ['day_of_week' => 'saturday', 'start_time' => '09:00:00', 'end_time' => '10:30:00'],
        ];

        return collect($wanted)->map(fn (array $slot) => Schedule::firstOrCreate(
            [
                'location_id' => $location->id,
                'program_id'  => $program->id,
                'day_of_week' => $slot['day_of_week'],
                'start_time'  => $slot['start_time'],
            ],
            [
                'end_time'     => $slot['end_time'],
                'coach_id'     => null,
                'type'         => 'regular',
                'max_capacity' => 20,
                'is_active'    => true,
            ]
        ))->all();
    }

    private function package(Location $location): Package
    {
        return Package::firstOrCreate(
            ['location_id' => $location->id, 'name' => 'Junior — 12 Sesi'],
            [
                // packages.type enum is registration|regular|drop_in|private
                'type'          => 'regular',
                'price'         => 1_200_000,
                'session_count' => 12,
                'validity_days' => 120,
                'description'   => 'Paket 12 sesi, berlaku 4 bulan.',
                'is_active'     => true,
                'is_popular'    => true,
            ]
        );
    }

    /** Every parent account gets a child, so both demo logins have something to look at. */
    private function children()
    {
        $parentRoleId = Role::where('name', 'parent')->value('id');

        return User::where('role_id', $parentRoleId)->get()->map(function (User $parent) {
            return Child::firstOrCreate(
                ['user_id' => $parent->id, 'name' => explode('@', $parent->email)[0] === 'parent' ? 'Bima' : 'Anak 1'],
                ['birth_date' => now()->subYears(9)->startOfYear(), 'gender' => 'male']
            );
        });
    }

    private function enrolChild(Child $child, array $schedules, Package $package, ?User $admin, ?Coach $coach): void
    {
        // Monday + Saturday: one weekday, one weekend, five weeks of history.
        $startedAt = now()->subWeeks(5)->startOfWeek();

        foreach ([$schedules[0], $schedules[2]] as $schedule) {
            $transaction = Transaction::firstOrCreate(
                ['child_id' => $child->id, 'package_id' => $package->id, 'transaction_code' => 'DEMO-'.$child->id.'-'.$schedule->id],
                [
                    'user_id'        => $child->user_id,
                    'amount'         => $package->price,
                    'status'         => 'paid',
                    'payment_method' => 'transfer',
                    'paid_at'        => $startedAt->copy()->subDay(),
                    'verified_by'    => $admin?->id,
                ]
            );

            $enrollment = Enrollment::firstOrCreate(
                ['child_id' => $child->id, 'schedule_id' => $schedule->id, 'type' => 'program'],
                [
                    'package_id'         => $package->id,
                    'transaction_id'     => $transaction->id,
                    'status'             => 'approved',
                    'approved_by'        => $admin?->id,
                    'approved_at'        => $startedAt->copy()->subDay(),
                    'started_at'         => $startedAt,
                    'expires_at'         => $startedAt->copy()->addDays($package->validity_days),
                    'total_sessions'     => $package->session_count,
                    'remaining_sessions' => $package->session_count,
                ]
            );

            $this->history($child, $enrollment, $schedule, $coach, $admin);
        }

        // A child with an approved enrolment is an active member. Without this
        // they stay 'unregistered', which hides them from the leave-request and
        // make-up forms that only offer active children.
        $child->update(['status' => 'active']);
    }

    /**
     * Walks every past occurrence of the class and records what happened: mostly
     * attended, one missed outright, and one covered by an approved leave that a
     * make-up class was booked against.
     */
    private function history(Child $child, Enrollment $enrollment, Schedule $schedule, ?Coach $coach, ?User $admin): void
    {
        $date = $enrollment->started_at->copy();
        while (strtolower($date->format('l')) !== $schedule->day_of_week) {
            $date->addDay();
        }

        $occurrence = 0;
        $leaveDate  = null;

        while ($date->lt(now()->startOfDay())) {
            $occurrence++;

            // 3rd session: sick leave, approved. 5th: a plain no-show.
            $status = match ($occurrence) {
                3       => 'sick',
                5       => 'no_show',
                default => 'present',
            };

            if ($status === 'sick') {
                $leaveDate = $date->copy();

                $leave = LeaveRequest::firstOrCreate(
                    ['child_id' => $child->id, 'schedule_id' => $schedule->id, 'leave_date' => $leaveDate->toDateString()],
                    [
                        'enrollment_id' => $enrollment->id,
                        'type'          => 'sick',
                        'reason'        => 'Demam, istirahat di rumah.',
                        'status'        => 'approved',
                        'reviewed_by'   => $admin?->id,
                        'reviewed_at'   => $leaveDate->copy()->subDay(),
                    ]
                );

                Attendance::firstOrCreate(
                    ['child_id' => $child->id, 'schedule_id' => $schedule->id, 'attended_at' => $date->copy()->setTimeFromTimeString($schedule->start_time)],
                    [
                        'enrollment_id'     => $enrollment->id,
                        'coach_id'          => $coach?->id,
                        'leave_request_id'  => $leave->id,
                        'status'            => 'sick',
                        'source'            => 'manual',
                        'session_deducted'  => false,
                    ]
                );
            } else {
                Attendance::firstOrCreate(
                    ['child_id' => $child->id, 'schedule_id' => $schedule->id, 'attended_at' => $date->copy()->setTimeFromTimeString($schedule->start_time)],
                    [
                        'enrollment_id'    => $enrollment->id,
                        'coach_id'         => $coach?->id,
                        'status'           => $status,
                        'source'           => $status === 'present' ? 'qr' : 'system',
                        'session_deducted' => true,
                    ]
                );
            }

            $date->addWeek();
        }

        // Sessions actually consumed, so the remaining count isn't fiction.
        $used = Attendance::where('enrollment_id', $enrollment->id)->where('session_deducted', true)->count();
        $enrollment->update(['remaining_sessions' => max(0, $enrollment->total_sessions - $used)]);

        // A make-up booked against that approved leave, landing next week.
        if ($leaveDate) {
            $leave = LeaveRequest::where('child_id', $child->id)
                ->where('schedule_id', $schedule->id)
                ->whereDate('leave_date', $leaveDate)
                ->first();

            $target = now()->next($schedule->day_of_week)->startOfDay();

            MakeUpClass::firstOrCreate(
                ['child_id' => $child->id, 'leave_request_id' => $leave?->id],
                [
                    'enrollment_id'      => $enrollment->id,
                    'target_schedule_id' => $schedule->id,
                    'target_date'        => $target,
                    'status'             => 'approved',
                    'approved_by'        => $admin?->id,
                    'approved_at'        => now()->subDays(2),
                ]
            );
        }

        // One pending leave in the future, so the "waiting on admin" state is visible.
        $upcoming = now()->next($schedule->day_of_week)->addWeek()->startOfDay();

        LeaveRequest::firstOrCreate(
            ['child_id' => $child->id, 'schedule_id' => $schedule->id, 'leave_date' => $upcoming->toDateString()],
            [
                'enrollment_id' => $enrollment->id,
                'type'          => 'permit',
                'reason'        => 'Acara keluarga di luar kota.',
                'status'        => 'pending',
            ]
        );
    }

    private function upcomingEvent(Location $location, Program $program, ?Child $child, ?User $admin): void
    {
        $event = Event::firstOrCreate(
            ['name' => 'Fun Game Day'],
            [
                'description'     => 'Pertandingan santai antar kelas. Orang tua boleh menonton.',
                'is_registerable' => true,
                'price'           => 150_000,
                'capacity'        => 40,
                'start_date'      => now()->addWeeks(2)->next('saturday')->setTime(9, 0),
                'end_date'        => now()->addWeeks(2)->next('saturday')->setTime(12, 0),
                'location_id'     => $location->id,
                'program_id'      => $program->id,
                'is_active'       => true,
                'created_by'      => $admin?->id,
            ]
        );

        if ($child) {
            EventRegistration::firstOrCreate(
                ['event_id' => $event->id, 'child_id' => $child->id],
                // event_registrations.status enum is pending|confirmed|cancelled
                ['status' => 'confirmed', 'registered_at' => now()->subDays(3)]
            );
        }
    }
}
