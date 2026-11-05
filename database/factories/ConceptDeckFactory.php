<?php

namespace Database\Factories;

use App\Models\AccessToken;
use App\Models\ConceptDeck;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConceptDeck>
 */
#[UseModel(ConceptDeck::class)]
class ConceptDeckFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'access_token_id' => AccessToken::factory(),
            'name' => $this->faker->unique()->words(2, true),
        ];
    }
}
