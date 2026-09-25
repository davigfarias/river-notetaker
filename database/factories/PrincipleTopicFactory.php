<?php

namespace Database\Factories;

use App\Models\PrincipleTopic;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PrincipleTopic>
 */
#[UseModel(PrincipleTopic::class)]
class PrincipleTopicFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(3);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
        ];
    }
}
