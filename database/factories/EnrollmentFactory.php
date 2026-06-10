<?php

namespace Database\Factories;

use App\Models\Child;
use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnrollmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'child_id'   => Child::factory(),
            'type'       => 'registration',
            'schedule_id'=> null,
            'package_id' => Package::factory()->registration(),
            'status'     => 'pending',
        ];
    }

    public function program(): static
    {
        return $this->state([
            'type'       => 'program',
            'package_id' => Package::factory()->regular(),
        ]);
    }

    public function approved(): static
    {
        return $this->state([
            'status'      => 'approved',
            'approved_at' => now(),
        ]);
    }
}
