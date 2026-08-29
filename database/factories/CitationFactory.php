<?php

namespace Database\Factories;

use App\Models\Citation;
use App\Models\ReferenceMaterial;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Citation>
 */
#[UseModel(Citation::class)]
class CitationFactory extends Factory
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
            'quote_text' => $this->faker->paragraph(),
            'location' => 'p. '.$this->faker->numberBetween(1, 400),
            'personal_note' => $this->faker->boolean() ? $this->faker->sentence() : null,
        ];
    }
}
