<?php

namespace Database\Factories;

use App\Enums\BloomLevel;
use App\Enums\ExerciseType;
use App\Enums\SelfRating;
use App\Models\AccessToken;
use App\Models\Principle;
use App\Models\PrincipleAttempt;
use App\Models\PrincipleSession;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrincipleAttempt>
 */
#[UseModel(PrincipleAttempt::class)]
class PrincipleAttemptFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'access_token_id' => AccessToken::factory(),
            'principle_session_id' => PrincipleSession::factory(),
            'principle_id' => Principle::factory(),
            'type' => ExerciseType::RecallTitleToStatement,
            'bloom_level' => BloomLevel::Lembrar,
            'self_rating' => SelfRating::Good,
            'duration_ms' => 4000,
            'answered_at' => now(),
        ];
    }
}
