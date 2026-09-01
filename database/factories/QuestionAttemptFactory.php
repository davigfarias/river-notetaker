<?php

namespace Database\Factories;

use App\Models\AccessToken;
use App\Models\Question;
use App\Models\QuestionAttempt;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuestionAttempt>
 */
#[UseModel(QuestionAttempt::class)]
class QuestionAttemptFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'access_token_id' => AccessToken::factory(),
            'answer_text' => $this->faker->sentence(),
            'score' => $this->faker->numberBetween(0, 100),
            'cloze_blanks' => null,
            'skipped' => false,
        ];
    }

    public function skipped(): static
    {
        return $this->state([
            'answer_text' => null,
            'score' => null,
            'skipped' => true,
        ]);
    }

    /**
     * A recorded cloze attempt.
     *
     * @param  array<int, array{index: int, expected: string, given: string, correct: bool}>  $blanks
     */
    public function cloze(array $blanks): static
    {
        $correct = count(array_filter($blanks, fn (array $blank): bool => $blank['correct']));

        return $this->state([
            'answer_text' => implode(', ', array_column($blanks, 'given')),
            'score' => $blanks === [] ? 100 : (int) round($correct / count($blanks) * 100),
            'cloze_blanks' => $blanks,
            'skipped' => false,
        ]);
    }
}
