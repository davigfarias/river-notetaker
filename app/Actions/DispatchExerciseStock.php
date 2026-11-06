<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ExerciseType;
use App\Jobs\GenerateExerciseStockJob;

/**
 * Pede a geração de um exercício em segundo plano. Só despacha quando existe fila de verdade:
 * com a fila "sync" (sem worker) o app geraria na hora, travando a tela; aí vale a geração sob demanda.
 */
final readonly class DispatchExerciseStock
{
    public function handle(int $accessTokenId, int $principleId, ExerciseType $type, string $sourceHash): bool
    {
        if (! config('principios.ai.enabled') || config('queue.default') === 'sync') {
            return false;
        }

        GenerateExerciseStockJob::dispatch($accessTokenId, $principleId, $type, $sourceHash);

        return true;
    }
}
