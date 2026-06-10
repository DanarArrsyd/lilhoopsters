<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'      => fake()->unique()->words(2, true),
            'address'   => fake()->streetAddress(),
            'city'      => fake()->randomElement(['Jakarta Selatan', 'Jakarta Pusat', 'Jakarta Barat']),
            'maps_url'  => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
