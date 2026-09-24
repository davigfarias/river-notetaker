<?php

namespace Database\Factories;

use App\Models\ReadingNote;
use App\Models\ReferenceMaterial;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadingNote>
 */
#[UseModel(ReadingNote::class)]
class ReadingNoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_material_id' => ReferenceMaterial::factory(),
            'access_token_id' => null,
            'title' => $this->faker->boolean() ? $this->faker->sentence(4) : null,
            'body' => $this->faker->paragraph(),
            'location' => $this->faker->boolean() ? 'p. '.$this->faker->numberBetween(1, 400) : null,
            'tags' => null,
            'page_snapshot' => null,
            'review_stage' => 1,
        ];
    }

    /**
     * A note with no location at all, which is the common case for video and audio material.
     */
    public function withoutLocation(): self
    {
        return $this->state(fn (): array => ['location' => null]);
    }

    /**
     * Anotação que já está cobrando revisão na data informada.
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
     * Anotação que percorreu a escada inteira e saiu da fila.
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
