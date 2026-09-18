<?php

namespace Database\Factories;

use App\Enums\Weekday;
use App\Models\Disciplines;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Disciplines>
 */
#[UseModel(Disciplines::class)]
class DisciplineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->randomElement(['Teologia Sistemática', 'História da Igreja', 'Hermenêutica']),
            'slug' => $this->faker->slug(),
        ];
    }

    /**
     * Disciplina que tem aula no dia informado e, por isso, entra na fila de
     * revisão desse dia.
     */
    public function onWeekday(Weekday $weekday): static
    {
        return $this->state(fn (array $attributes): array => [
            'class_weekday' => $weekday,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'completed_at' => now(),
        ]);
    }

    public function inPeriod(int $period): static
    {
        return $this->state(fn (array $attributes): array => [
            'period' => $period,
        ]);
    }
}
