<?php

namespace Database\Factories;

use App\Models\PrincipleCategory;
use App\Models\PrincipleTopic;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrincipleCategory>
 */
#[UseModel(PrincipleCategory::class)]
class PrincipleCategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'principle_topic_id' => PrincipleTopic::factory(),
            'title' => $this->faker->unique()->words(2, true),
            'position' => 0,
        ];
    }
}
