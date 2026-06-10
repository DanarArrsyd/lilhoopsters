<?php

namespace Database\Factories;

use App\Models\Child;
use App\Models\Enrollment;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'child_id'      => Child::factory(),
            'enrollment_id' => Enrollment::factory()->approved(),
            'schedule_id'   => Schedule::factory(),
            'leave_date'    => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'type'          => fake()->randomElement(['sick', 'permit']),
            'reason'        => fake()->sentence(),
            'status'        => 'pending',
        ];
    }

    public function approved(): static
    {
        return $this->state(['status' => 'approved', 'reviewed_at' => now()]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status'      => 'rejected',
            'reviewed_at' => now(),
            'admin_notes' => fake()->sentence(),
        ]);
    }
}
