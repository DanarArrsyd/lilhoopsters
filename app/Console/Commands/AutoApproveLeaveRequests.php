<?php

namespace App\Console\Commands;

use App\Models\LeaveRequest;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class AutoApproveLeaveRequests extends Command
{
    protected $signature = 'leaves:auto-approve';

    protected $description = 'Auto-approve pending leave requests whose 72-hour review window has passed without admin action';

    public function handle(): int
    {
        $due = LeaveRequest::where('status', 'pending')
            ->where('auto_approve_at', '<=', now())
            ->with('child.user')
            ->get();

        foreach ($due as $leaveRequest) {
            $leaveRequest->update([
                'status'      => 'auto_approved',
                'reviewed_at' => now(),
            ]);

            $parentUser = $leaveRequest->child?->user;
            if ($parentUser) {
                $childName = $leaveRequest->child->name;
                $date      = $leaveRequest->leave_date?->format('d M Y') ?? '';

                NotificationService::send(
                    $parentUser->id,
                    'leave_approved',
                    'Leave Request Approved',
                    "Leave request for {$childName} on {$date} has been automatically approved.",
                    [],
                    email: true,
                );
            }
        }

        $this->info("Leave requests auto-approved: {$due->count()}");

        return self::SUCCESS;
    }
}
