<?php

namespace Database\Factories;

use App\Models\Child;
use App\Models\Coach;
use App\Models\Enrollment;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'child_id'      => Child::factory(),
            'enrollment_id' => Enrollment::factory()->approved(),
            'schedule_id'   => Schedule::factory(),
            'coach_id'      => null,
            'status'        => 'present',
            'source'        => 'manual',
            'attended_at'   => fake()->dateTimeBetween('-3 months', 'now'),
        ];
    }

    public function present(): static
    {
        return $this->state(['status' => 'present']);
    }

    public function absent(): static
    {
        return $this->state(['status' => 'no_show']);
    }

    public function sick(): static
    {
        return $this->state(['status' => 'sick']);
    }
}
