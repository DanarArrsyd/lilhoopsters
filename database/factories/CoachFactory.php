<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CoachFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'        => function () {
                $role = Role::where('name', 'coach')->firstOrFail();
                return User::factory()->approved()->create(['role_id' => $role->id])->id;
            },
            'phone'          => fake()->phoneNumber(),
            'specialization' => fake()->randomElement(['Dribbling', 'Shooting', 'Defense', null]),
            'is_active'      => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
