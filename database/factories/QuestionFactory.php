<?php

namespace Database\Factories;

use App\Models\Chapter;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
#[UseModel(Question::class)]
class QuestionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chapter_id' => Chapter::factory(),
            'prompt' => $this->faker->sentence().'?',
            'reference_answer' => $this->faker->paragraph(),
            'keywords' => implode(', ', $this->faker->words(3)),
            'is_cloze' => false,
            'cloze_blank_indices' => null,
            'position' => 0,
        ];
    }

    /**
     * A cloze question with a deterministic reference answer and blank set so
     * tests don't depend on the random blank picker.
     */
    public function cloze(): static
    {
        return $this->state([
            'reference_answer' => 'Deus fez uma aliança eterna com Abraão',
            'is_cloze' => true,
            'cloze_blank_indices' => [3, 6],
        ]);
    }
}
