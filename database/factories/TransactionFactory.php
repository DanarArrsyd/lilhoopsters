<?php

namespace Database\Factories;

use App\Models\Child;
use App\Models\Package;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'    => function () {
                $role = Role::where('name', 'parent')->firstOrFail();
                return User::factory()->approved()->create(['role_id' => $role->id])->id;
            },
            'child_id'   => Child::factory(),
            'package_id' => Package::factory(),
            'amount'     => fake()->numberBetween(100000, 2000000),
            'status'     => 'pending',
        ];
    }

    public function paid(): static
    {
        return $this->state([
            'status'  => 'paid',
            'paid_at' => now(),
        ]);
    }
}
