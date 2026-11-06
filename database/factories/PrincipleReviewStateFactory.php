<?php

namespace Database\Factories;

use App\Models\AccessToken;
use App\Models\Principle;
use App\Models\PrincipleReviewState;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrincipleReviewState>
 */
#[UseModel(PrincipleReviewState::class)]
class PrincipleReviewStateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'access_token_id' => AccessToken::factory(),
            'principle_id' => Principle::factory(),
            'due_at' => now()->addDays(3),
            'last_reviewed_at' => now()->subDay(),
            'interval_days' => 3,
            'ease' => 2.5,
            'repetitions' => 2,
            'lapses' => 0,
        ];
    }

    public function due(): static
    {
        return $this->state(fn (): array => ['due_at' => now()->subDay()]);
    }
}
