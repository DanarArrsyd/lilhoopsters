<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'location_id'  => Location::factory(),
            'program_id'   => Program::factory(),
            'coach_id'     => null,
            'day_of_week'  => fake()->randomElement(['monday','tuesday','wednesday','thursday','friday','saturday','sunday']),
            'start_time'   => '09:00:00',
            'end_time'     => '10:00:00',
            'max_capacity' => 20,
            'is_active'    => true,
        ];
    }
}
