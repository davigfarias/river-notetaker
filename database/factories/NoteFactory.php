<?php

namespace Database\Factories;

use App\Models\Disciplines;
use App\Models\Notes;
use App\Models\Tags;
use Carbon\CarbonInterface;
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
            'discipline_id' => Disciplines::factory(),
            'impressions' => $this->faker->paragraph(),
            'life_experiences' => $this->faker->paragraph(),
            'review_stage' => 1,
        ];
    }

    /**
     * Nota que já está cobrando revisão na data informada.
     */
    public function dueOn(CarbonInterface|string $date, int $stage = 1): static
    {
        return $this->state(fn (array $attributes): array => [
            'review_stage' => $stage,
            'next_review_at' => $date instanceof CarbonInterface ? $date->toDateString() : $date,
            'consolidated_at' => null,
        ]);
    }

    /**
     * Nota que percorreu a escada inteira e saiu da fila.
     */
    public function consolidated(): static
    {
        return $this->state(fn (array $attributes): array => [
            'review_stage' => 5,
            'next_review_at' => null,
            'consolidated_at' => now(),
        ]);
    }
}
