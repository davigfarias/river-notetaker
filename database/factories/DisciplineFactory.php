<?php

namespace Database\Factories;

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
}
