<?php

namespace Database\Factories;

use App\Enums\Era;
use App\Enums\HistoricalEventNature;
use App\Models\HistoricalEvent;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HistoricalEvent>
 */
#[UseModel(HistoricalEvent::class)]
class HistoricalEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'access_token_id' => null,
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->boolean() ? $this->faker->paragraph() : null,
            'nature' => $this->faker->randomElement(HistoricalEventNature::cases()),
            'start_year' => $this->faker->numberBetween(1, 2000),
            'start_era' => Era::AnnoDomini,
            'end_year' => null,
            'end_era' => null,
        ];
    }

    public function beforeChrist(): self
    {
        return $this->state(fn (): array => ['start_era' => Era::BeforeChrist]);
    }

    public function withRange(int $endYear, Era $endEra = Era::AnnoDomini): self
    {
        return $this->state(fn (): array => [
            'end_year' => $endYear,
            'end_era' => $endEra,
        ]);
    }
}
