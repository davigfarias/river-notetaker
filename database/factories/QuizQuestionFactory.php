<?php

namespace Database\Factories;

use App\Models\Notes;
use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizQuestion>
 */
#[UseModel(QuizQuestion::class)]
class QuizQuestionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'note_id' => Notes::factory(),
            'question' => $this->faker->sentence().'?',
            'correct_answer' => $this->faker->words(3, true),
            'distractors' => [
                $this->faker->words(3, true),
                $this->faker->words(3, true),
                $this->faker->words(3, true),
            ],
            'content_hash' => hash('sha256', $this->faker->sentence()),
            'provider' => 'groq',
            'model' => null,
        ];
    }
}
