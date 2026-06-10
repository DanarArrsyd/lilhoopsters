<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class PackageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'location_id'   => Location::factory(),
            'name'          => fake()->words(3, true),
            'type'          => fake()->randomElement(['registration', 'regular', 'drop_in', 'private']),
            'price'         => fake()->numberBetween(100000, 2000000),
            'session_count' => null,
            'validity_days' => null,
            'period_start'  => null,
            'period_end'    => null,
            'description'   => null,
            'is_active'     => true,
            'is_popular'    => false,
        ];
    }

    public function registration(): static
    {
        return $this->state(['type' => 'registration', 'price' => 350000]);
    }

    public function regular(): static
    {
        return $this->state([
            'type'         => 'regular',
            'session_count'=> 8,
            'period_start' => now()->startOfMonth(),
            'period_end'   => now()->endOfMonth()->addMonth(),
        ]);
    }
}
