<?php

namespace Database\Factories;

use App\Models\ReportCard;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportCardScoreFactory extends Factory
{
    public function definition(): array
    {
        return [
            'report_card_id' => ReportCard::factory(),
            'category'       => fake()->randomElement(['dribbling', 'passing', 'shooting', 'defense', 'attitude', 'discipline']),
            'score'          => fake()->numberBetween(1, 5),
            'notes'          => fake()->optional()->sentence(),
        ];
    }
}
