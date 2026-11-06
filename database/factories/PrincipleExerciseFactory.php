<?php

namespace Database\Factories;

use App\Enums\BloomLevel;
use App\Enums\ExerciseStatus;
use App\Enums\ExerciseType;
use App\Models\AccessToken;
use App\Models\Principle;
use App\Models\PrincipleExercise;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrincipleExercise>
 */
#[UseModel(PrincipleExercise::class)]
class PrincipleExerciseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'access_token_id' => AccessToken::factory(),
            'principle_id' => Principle::factory(),
            'type' => ExerciseType::ApplyScenario,
            'bloom_level' => BloomLevel::Aplicar,
            'payload' => [
                'scenario' => $this->faker->paragraph(),
                'question' => 'Como este princípio se aplica aqui?',
                'expected_application' => $this->faker->sentence(),
                'key_points' => [$this->faker->sentence()],
            ],
            'source_hash' => hash('sha256', $this->faker->unique()->sentence()),
            'prompt_version' => 'v1',
            'model' => 'fake',
            'status' => ExerciseStatus::Ready,
            'times_served' => 0,
            'generated_at' => now(),
        ];
    }

    public function stale(): static
    {
        return $this->state(fn (): array => ['status' => ExerciseStatus::Stale]);
    }
}
