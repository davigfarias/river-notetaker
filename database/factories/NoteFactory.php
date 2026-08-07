<?php

namespace Database\Factories;

use App\Models\Disciplines;
use App\Models\Notes;
use App\Models\Tags;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notes>
 */
#[UseModel(Notes::class)]
class NoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'tags' => $this->faker->randomElements(
                Tags::pluck('title')->toArray(),
                $this->faker->numberBetween(1, 4)
            ),
            'discipline_id' => Disciplines::factory()->create()->id,
            'concepts' => array_map(
                fn () => [
                    'term' => fake()->word(),
                    'definition' => fake()->sentence(),
                ],
                range(1, fake()->numberBetween(1, 10))
            ),
            'impressions' => $this->faker->paragraph(),
            'pastoral_advice' => $this->faker->paragraph(),
            'life_experiences' => $this->faker->paragraph(),
            'references' => $this->faker->url(),
        ];
    }
}
