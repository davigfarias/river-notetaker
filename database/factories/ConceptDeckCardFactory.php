<?php

namespace Database\Factories;

use App\Models\ConceptDeck;
use App\Models\ConceptDeckCard;
use App\Models\Concepts;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConceptDeckCard>
 */
#[UseModel(ConceptDeckCard::class)]
class ConceptDeckCardFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'concept_deck_id' => ConceptDeck::factory(),
            'concept_id' => fn (): int => Concepts::create([
                'term' => $this->faker->unique()->words(2, true),
                'definition' => $this->faker->sentence(),
            ])->id,
            'is_active' => true,
            'is_starred' => false,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function starred(): static
    {
        return $this->state(['is_starred' => true]);
    }
}
