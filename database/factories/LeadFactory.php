<?php

namespace Database\Factories;

use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'parent_name' => fake()->name(),
            'child_name'  => fake()->firstName(),
            'whatsapp'    => fake()->numerify('08##########'),
            'source'      => fake()->randomElement(Lead::SOURCES),
            'status'      => 'new',
            'notes'       => null,
        ];
    }

    public function status(string $status): static
    {
        return $this->state(['status' => $status]);
    }
}
