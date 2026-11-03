<?php

namespace Database\Factories;

use App\Models\AccessToken;
use App\Models\ReferenceGroup;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReferenceGroup>
 */
#[UseModel(ReferenceGroup::class)]
class ReferenceGroupFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'access_token_id' => AccessToken::factory(),
            'name' => $this->faker->unique()->words(2, true),
        ];
    }
}
