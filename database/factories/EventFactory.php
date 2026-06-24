<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'name'        => fake()->randomElement(['Summer Champ', 'Winter Camp', 'School Holiday', 'Tournament Week']),
            'description' => null,
            'start_date'  => today()->addWeek(),
            'end_date'    => today()->addWeeks(3),
            'location_id' => null,
            'program_id'  => null,
            'is_active'   => true,
        ];
    }
}
