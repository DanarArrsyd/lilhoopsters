<?php

namespace Database\Factories;

use App\Models\Child;
use App\Models\Enrollment;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class MakeUpClassFactory extends Factory
{
    public function definition(): array
    {
        $enrollment = Enrollment::factory()->approved()->create();
        $leaveRequest = LeaveRequest::factory()->create([
            'child_id'      => $enrollment->child_id,
            'enrollment_id' => $enrollment->id,
        ]);

        return [
            'child_id'          => $enrollment->child_id,
            'enrollment_id'     => $enrollment->id,
            'leave_request_id'  => $leaveRequest->id,
            'target_schedule_id'=> Schedule::factory(),
            'target_date'       => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'status'            => 'pending',
        ];
    }

    public function approved(): static
    {
        return $this->state(['status' => 'approved', 'approved_at' => now()]);
    }

    public function rejected(): static
    {
        return $this->state(['status' => 'rejected', 'admin_notes' => fake()->sentence()]);
    }
}
