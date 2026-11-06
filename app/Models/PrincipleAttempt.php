<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AiVerdict;
use App\Enums\BloomLevel;
use App\Enums\ExerciseType;
use App\Enums\SelfRating;
use Carbon\CarbonImmutable;
use Database\Factories\PrincipleAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Um exercício respondido. O self_rating nasce nulo: o usuário escolhe depois do feedback.
 *
 * @property int $id
 * @property int $access_token_id
 * @property int $principle_session_id
 * @property int $principle_id
 * @property int|null $principle_exercise_id
 * @property ExerciseType $type
 * @property BloomLevel $bloom_level
 * @property string|null $user_answer
 * @property int|null $chosen_principle_id
 * @property array<string, mixed>|null $ai_evaluation
 * @property AiVerdict|null $ai_verdict
 * @property SelfRating|null $self_rating
 * @property SelfRating|null $ai_suggested_rating
 * @property int $duration_ms
 * @property CarbonImmutable $answered_at
 */
#[Fillable([
    'access_token_id', 'principle_session_id', 'principle_id', 'principle_exercise_id',
    'type', 'bloom_level', 'user_answer', 'chosen_principle_id', 'ai_evaluation', 'ai_verdict',
    'self_rating', 'ai_suggested_rating', 'duration_ms', 'answered_at',
])]
#[Table(name: 'principle_attempts')]
#[UseFactory(PrincipleAttemptFactory::class)]
class PrincipleAttempt extends Model
{
    /** @use HasFactory<PrincipleAttemptFactory> */
    use HasFactory;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'type' => ExerciseType::class,
            'bloom_level' => BloomLevel::class,
            'ai_evaluation' => 'array',
            'ai_verdict' => AiVerdict::class,
            'self_rating' => SelfRating::class,
            'ai_suggested_rating' => SelfRating::class,
            'duration_ms' => 'integer',
            'answered_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<PrincipleSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(PrincipleSession::class, 'principle_session_id');
    }

    /**
     * @return BelongsTo<Principle, $this>
     */
    public function principle(): BelongsTo
    {
        return $this->belongsTo(Principle::class);
    }
}
