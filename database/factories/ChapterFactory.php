<?php

namespace Database\Factories;

use App\Models\Chapter;
use App\Models\ReferenceMaterial;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Chapter>
 */
#[UseModel(Chapter::class)]
class ChapterFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_material_id' => ReferenceMaterial::factory(),
            'title' => $this->faker->sentence(4),
            'position' => 0,
        ];
    }

    /**
     * Capítulo com anotação, já na escada de revisão e cobrando na data informada.
     */
    public function withNote(string $body = 'Texto da anotação do capítulo.'): static
    {
        return $this->state(fn (): array => ['body' => $body, 'review_stage' => 1]);
    }

    public function dueOn(CarbonInterface|string $date, int $stage = 1): static
    {
        return $this->state(fn (): array => [
            'body' => 'Texto da anotação do capítulo.',
            'review_stage' => $stage,
            'next_review_at' => $date instanceof CarbonInterface ? $date->toDateString() : $date,
            'consolidated_at' => null,
        ]);
    }
}
