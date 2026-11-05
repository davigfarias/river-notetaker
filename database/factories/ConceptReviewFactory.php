<?php

namespace Database\Factories;

use App\Enums\ConceptReviewResult;
use App\Models\AccessToken;
use App\Models\ConceptReview;
use App\Models\Concepts;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConceptReview>
 */
#[UseModel(ConceptReview::class)]
class ConceptReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'access_token_id' => AccessToken::factory(),
            'concept_id' => fn (): int => Concepts::create([
                'term' => $this->faker->unique()->words(2, true),
                'definition' => $this->faker->sentence(),
            ])->id,
            'result' => ConceptReviewResult::Remembered,
            'reviewed_at' => now(),
        ];
    }
}
