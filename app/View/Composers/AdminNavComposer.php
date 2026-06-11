<?php

namespace App\View\Composers;

use App\Models\Enrollment;
use App\Models\LeaveRequest;
use App\Models\MakeUpClass;
use App\Models\ReportCard;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\View\View;

class AdminNavComposer
{
    public function compose(View $view): void
    {
        $parentRoleId = \App\Models\Role::where('name', 'parent')->value('id');

        $view->with('navBadges', [
            'parents'       => $parentRoleId
                ? User::where('role_id', $parentRoleId)
                      ->where('registration_status', 'pending')
                      ->count()
                : 0,
            'enrollments'   => Enrollment::where('status', 'pending')->count(),
            'payments'      => Transaction::where('status', 'pending')
                                  ->whereNotNull('payment_proof')
                                  ->count(),
            'leave_requests' => LeaveRequest::where('status', 'pending')->count(),
            'makeup_classes' => MakeUpClass::where('status', 'pending')->count(),
            'report_cards'   => ReportCard::where('status', 'submitted')->count(),
        ]);
    }
}
