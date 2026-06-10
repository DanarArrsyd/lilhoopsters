<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChildFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'       => function () {
                $role = Role::where('name', 'parent')->firstOrFail();
                return User::factory()->approved()->create(['role_id' => $role->id])->id;
            },
            'name'          => fake()->firstName() . ' ' . fake()->lastName(),
            'birth_date'    => fake()->dateTimeBetween('-7 years', '-1 year')->format('Y-m-d'),
            'gender'        => fake()->randomElement(['male', 'female']),
            'school'        => null,
            'medical_notes' => null,
            'status'        => 'unregistered',
        ];
    }

    public function active(): static
    {
        return $this->state([
            'status'       => 'active',
            'registered_at'=> now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }
}
