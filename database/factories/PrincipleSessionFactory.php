<?php

namespace Database\Factories;

use App\Models\AccessToken;
use App\Models\PrincipleSession;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrincipleSession>
 */
#[UseModel(PrincipleSession::class)]
class PrincipleSessionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'access_token_id' => AccessToken::factory(),
            'planned_items' => 0,
            'started_at' => now(),
            'composition' => [],
        ];
    }

    public function finished(): static
    {
        return $this->state(fn (): array => ['finished_at' => now()]);
    }
}
