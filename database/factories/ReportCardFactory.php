<?php

namespace Database\Factories;

use App\Models\Child;
use App\Models\Coach;
use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportCardFactory extends Factory
{
    public function definition(): array
    {
        $enrollment = Enrollment::factory()->approved()->create();

        return [
            'child_id'      => $enrollment->child_id,
            'coach_id'      => Coach::factory(),
            'enrollment_id' => $enrollment->id,
            'period_label'  => fake()->randomElement(['Q1 2025', 'Q2 2025', 'Q3 2025', 'Q4 2025']),
            'period_start'  => fake()->dateTimeBetween('-6 months', '-3 months')->format('Y-m-d'),
            'period_end'    => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
            'overall_notes' => fake()->paragraph(),
            'status'        => 'draft',
            'published_at'  => null,
            'pdf_path'      => null,
        ];
    }

    public function published(): static
    {
        return $this->state([
            'status'       => 'published',
            'published_at' => now(),
        ]);
    }

    public function submitted(): static
    {
        return $this->state(['status' => 'submitted']);
    }
}
