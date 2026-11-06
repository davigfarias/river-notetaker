<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\GenerateExerciseForPrinciple;
use App\Actions\LoadStudyPrinciple;
use App\Enums\ExerciseType;
use App\Models\PrincipleExercise;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Repõe o estoque de exercícios gerados. Um job por (token, princípio, tipo, conteúdo-base):
 * não duplica e, se o princípio mudou desde o despacho, não gera nada.
 */
final class GenerateExerciseStockJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $uniqueFor = 300;

    public function __construct(
        public readonly int $accessTokenId,
        public readonly int $principleId,
        public readonly ExerciseType $type,
        public readonly string $sourceHash,
    ) {}

    public function uniqueId(): string
    {
        return "{$this->accessTokenId}:{$this->principleId}:{$this->type->value}:{$this->sourceHash}";
    }

    public function handle(LoadStudyPrinciple $load, GenerateExerciseForPrinciple $generate): void
    {
        $current = $load->handle($this->principleId)->sourceHash();

        if ($current !== $this->sourceHash) {
            return;
        }

        $unserved = PrincipleExercise::query()
            ->where('access_token_id', $this->accessTokenId)->where('principle_id', $this->principleId)
            ->where('type', $this->type)->where('status', 'ready')->where('source_hash', $current)
            ->where('times_served', 0)->count();

        if ($unserved < (int) config('principios.ai.stock_per_type')) {
            $generate->handle($this->accessTokenId, $this->principleId, $this->type);
        }
    }
}
