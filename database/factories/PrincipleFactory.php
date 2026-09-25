<?php

namespace Database\Factories;

use App\Enums\PrincipleType;
use App\Models\Principle;
use App\Models\PrincipleTopic;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Principle>
 */
#[UseModel(Principle::class)]
class PrincipleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'principle_topic_id' => PrincipleTopic::factory(),
            'type' => PrincipleType::Text,
            'title' => $this->faker->sentence(4),
            'body' => $this->faker->paragraph(),
        ];
    }

    public function forConcept(int $conceptId): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => PrincipleType::Concept,
            'concept_id' => $conceptId,
            'title' => null,
            'body' => null,
        ]);
    }
}
