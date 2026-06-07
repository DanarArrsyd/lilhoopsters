<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'role_id'             => Role::factory(),
            'name'                => fake()->name(),
            'email'               => fake()->unique()->safeEmail(),
            'password'            => 'password', // hashed by model cast
            'registration_status' => 'pending',
            'is_active'           => true,
            'remember_token'      => Str::random(10),
        ];
    }

    public function approved(): static
    {
        return $this->state(['registration_status' => 'approved']);
    }

    public function withRole(string $roleName): static
    {
        return $this->state(function () use ($roleName) {
            $role = Role::where('name', $roleName)->firstOrFail();
            return ['role_id' => $role->id];
        });
    }
}
