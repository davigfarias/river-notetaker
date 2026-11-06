<?php

namespace Database\Factories;

use App\Models\Chapter;
use App\Models\ChapterQuizQuestion;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChapterQuizQuestion>
 */
#[UseModel(ChapterQuizQuestion::class)]
class ChapterQuizQuestionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chapter_id' => Chapter::factory(),
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
