<?php

namespace Database\Factories;

use App\Models\ReadingNote;
use App\Models\ReadingNoteQuizQuestion;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadingNoteQuizQuestion>
 */
#[UseModel(ReadingNoteQuizQuestion::class)]
class ReadingNoteQuizQuestionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reading_note_id' => ReadingNote::factory(),
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
