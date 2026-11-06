<?php

namespace Database\Factories;

use App\Models\Planner;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Planner>
 */
#[UseModel(Planner::class)]
class PlannerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'access_token_id' => null,
            'name' => $this->faker->sentence(3),
            'objective' => $this->faker->sentence(),
            'target_date' => null,
        ];
    }
}
