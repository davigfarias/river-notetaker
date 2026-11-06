<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BloomLevel;
use App\Enums\ExerciseStatus;
use App\Enums\ExerciseType;
use Carbon\CarbonImmutable;
use Database\Factories\PrincipleExerciseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Exercício gerado pela IA, guardado em cache. Vira Stale quando o princípio muda (source_hash).
 *
 * @property int $id
 * @property int $access_token_id
 * @property int $principle_id
 * @property ExerciseType $type
 * @property BloomLevel $bloom_level
 * @property array<string, mixed> $payload
 * @property string $source_hash
 * @property string $prompt_version
 * @property string|null $model
 * @property ExerciseStatus $status
 * @property int $times_served
 * @property CarbonImmutable|null $last_served_at
 * @property CarbonImmutable $generated_at
 */
#[Fillable([
    'access_token_id', 'principle_id', 'type', 'bloom_level', 'payload', 'source_hash',
    'prompt_version', 'model', 'status', 'times_served', 'last_served_at', 'generated_at',
])]
#[Table(name: 'principle_exercises')]
#[UseFactory(PrincipleExerciseFactory::class)]
class PrincipleExercise extends Model
{
    /** @use HasFactory<PrincipleExerciseFactory> */
    use HasFactory;

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'type' => ExerciseType::class,
            'bloom_level' => BloomLevel::class,
            'payload' => 'array',
            'status' => ExerciseStatus::class,
            'times_served' => 'integer',
            'last_served_at' => 'immutable_datetime',
            'generated_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Principle, $this>
     */
    public function principle(): BelongsTo
    {
        return $this->belongsTo(Principle::class);
    }
}
